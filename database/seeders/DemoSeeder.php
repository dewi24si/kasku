<?php

namespace Database\Seeders;

use App\AiFunctionStatus;
use App\ChatRole;
use App\Models\AiFunctionLog;
use App\Models\Budget;
use App\Models\Category;
use App\Models\ChatMessage;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Ai\Contracts\AiFunction;
use App\Services\Ai\Functions\CekBudgetFunction;
use App\Services\Ai\Functions\RingkasanFunction;
use App\Services\Ai\Functions\TambahTransaksiFunction;
use App\TransactionSource;
use App\TransactionType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Mengisi akun demo@kasku.test dengan data realistis untuk presentasi seminar.
 *
 * Jalankan manual: php artisan db:seed --class=DemoSeeder (tidak dipanggil dari DatabaseSeeder).
 * Aman dijalankan berulang: data lama akun demo dihapus dulu, data pengguna lain tidak disentuh.
 * Periode data: awal bulan dua bulan lalu sampai hari ini (tiga bulan kalender).
 */
class DemoSeeder extends Seeder
{
    public const DEMO_EMAIL = 'demo@kasku.test';

    /**
     * Seed tetap supaya data demo sama setiap kali seeder dijalankan pada tanggal yang sama.
     */
    private const RANDOM_SEED = 2026;

    private const ALERT_THRESHOLD = 80;

    private Randomizer $random;

    /** @var Collection<string, Category> */
    private Collection $expenseCategories;

    /** @var Collection<string, Category> */
    private Collection $incomeCategories;

    /** @var list<array<string, mixed>> */
    private array $rows = [];

    public function run(): void
    {
        $this->random = new Randomizer(new Mt19937(self::RANDOM_SEED));
        $this->loadDefaultCategories();

        $today = Carbon::today();

        DB::transaction(function () use ($today): void {
            $user = $this->resetDemoUser();

            $this->seedHistory($user, $today);
            $this->seedWeeklySpike($user, $today);
            $this->insertRows();
            $this->seedBudgets($user, $today);
            $this->seedChatConversation($user, $today);
        });
    }

    private function loadDefaultCategories(): void
    {
        if (Category::query()->whereNull('user_id')->doesntExist()) {
            $this->call(CategorySeeder::class);
        }

        $defaults = Category::query()->whereNull('user_id')->get();

        $this->expenseCategories = $defaults->where('type', TransactionType::Pengeluaran)->keyBy('name');
        $this->incomeCategories = $defaults->where('type', TransactionType::Pemasukan)->keyBy('name');
    }

    private function resetDemoUser(): User
    {
        $password = config('app.demo_password');
        $isGeneratedPassword = blank($password);

        if ($isGeneratedPassword) {
            $password = Str::password(12, symbols: false);
        }

        $user = User::query()->updateOrCreate(
            ['email' => self::DEMO_EMAIL],
            ['name' => 'Demo Kasku', 'password' => $password],
        );
        $user->forceFill(['email_verified_at' => now()])->save();

        ChatMessage::query()->where('user_id', $user->id)->delete();
        AiFunctionLog::query()->where('user_id', $user->id)->delete();
        Transaction::withTrashed()->where('user_id', $user->id)->forceDelete();
        Budget::query()->where('user_id', $user->id)->delete();
        Category::query()->where('user_id', $user->id)->delete();

        if ($isGeneratedPassword) {
            $this->command?->warn('DEMO_PASSWORD kosong, password acak dibuat untuk '.self::DEMO_EMAIL.': '.$password);
        } else {
            $this->command?->info('Akun demo '.self::DEMO_EMAIL.' memakai password dari DEMO_PASSWORD.');
        }

        return $user;
    }

    /**
     * Transaksi harian sampai kemarin. Transaksi hari ini dibuat lewat percakapan chat di bawah.
     */
    private function seedHistory(User $user, Carbon $today): void
    {
        $date = $today->copy()->subMonthsNoOverflow(2)->startOfMonth();
        $middleMonth = $today->copy()->subMonthNoOverflow()->format('Y-m');

        while ($date->lt($today)) {
            $this->seedMonthlyItems($user, $date, $middleMonth);
            $this->seedMeals($user, $date);
            $this->seedTransport($user, $date);
            $this->seedLifestyle($user, $date);

            $date->addDay();
        }
    }

    private function seedMonthlyItems(User $user, Carbon $date, string $middleMonth): void
    {
        switch ($date->day) {
            case 1:
                $this->income($user, $date, 'Uang Saku', 'Uang saku bulanan dari orang tua', 3_000_000, TransactionSource::Manual);
                $this->expense($user, $date, 'Tagihan', 'Bayar kos bulanan', 800_000, TransactionSource::Manual);
                break;
            case 5:
                $this->expense($user, $date, 'Tagihan', 'Paket internet bulanan', 100_000);
                break;
            case 8:
                $this->expense($user, $date, 'Pendidikan', $this->pick(['Beli buku referensi', 'Modul praktikum']), $this->amount(75_000, 120_000, 5_000));
                break;
            case 12:
                $this->income($user, $date, 'Lainnya', 'Freelance desain poster', $this->amount(450_000, 750_000, 50_000));
                break;
            case 15:
                $this->expense($user, $date, 'Hiburan', 'Langganan Spotify', 34_990);
                break;
            case 18:
                $this->expense($user, $date, 'Hiburan', 'Langganan Netflix', 54_000);
                break;
            case 20:
                $this->expense($user, $date, 'Tagihan', 'Isi pulsa', 50_000);

                if ($date->format('Y-m') === $middleMonth) {
                    $this->income($user, $date, 'Bonus', 'Hadiah juara lomba esai', 300_000, TransactionSource::Manual);
                }
                break;
            case 26:
                if ($date->month % 2 === 0) {
                    $this->income($user, $date, 'Lainnya', 'Jasa edit video', $this->amount(300_000, 500_000, 50_000));
                }
                break;
        }
    }

    private function seedMeals(User $user, Carbon $date): void
    {
        if ($this->chance(50)) {
            $this->expense($user, $date, 'Makan & Minum', $this->pick(['Nasi uduk', 'Bubur ayam', 'Roti dan susu', 'Lontong sayur']), $this->amount(7_000, 12_000));
        }

        if ($this->chance(90)) {
            $this->expense($user, $date, 'Makan & Minum', $this->pick(['Nasi padang', 'Ayam geprek', 'Makan di warteg', 'Mie ayam', 'Soto ayam']), $this->amount(13_000, 25_000));
        }

        if ($this->chance(75)) {
            $this->expense($user, $date, 'Makan & Minum', $this->pick(['Nasi goreng', 'Pecel lele', 'Bakso', 'Seblak', 'Martabak telur']), $this->amount(13_000, 28_000));
        }

        if ($this->chance(25)) {
            $this->expense($user, $date, 'Makan & Minum', $this->pick(['Kopi susu', 'Es teh dan camilan', 'Boba']), $this->amount(12_000, 22_000));
        }
    }

    private function seedTransport(User $user, Carbon $date): void
    {
        if ($date->isWeekday()) {
            $this->expense($user, $date, 'Transport', 'Ojek online ke kampus', $this->amount(8_000, 14_000));

            if ($this->chance(70)) {
                $this->expense($user, $date, 'Transport', 'Ojek online pulang', $this->amount(8_000, 14_000));
            }
        } elseif ($this->chance(40)) {
            $this->expense($user, $date, 'Transport', $this->pick(['Ojek online ke mal', 'Bus kota']), $this->amount(5_000, 25_000));
        }
    }

    private function seedLifestyle(User $user, Carbon $date): void
    {
        if ($date->isSunday()) {
            $this->expense($user, $date, 'Belanja', $this->pick(['Belanja mingguan di minimarket', 'Sabun, sampo, dan odol']), $this->amount(50_000, 120_000));
        }

        if ($date->isSaturday() && $this->chance(65)) {
            $this->expense($user, $date, 'Hiburan', $this->pick(['Nongkrong di kafe', 'Nonton bioskop', 'Karaoke bareng teman']), $this->amount(40_000, 85_000));
        }

        if ($date->isWednesday()) {
            $this->expense($user, $date, 'Lainnya', 'Laundry kiloan', $this->amount(25_000, 35_000));
        }

        if ($date->isWeekday() && $this->chance(20)) {
            $this->expense($user, $date, 'Pendidikan', 'Print dan fotokopi tugas', $this->amount(3_000, 15_000, 500));
        }

        if ($this->chance(4)) {
            $this->expense($user, $date, 'Kesehatan', $this->pick(['Obat flu', 'Vitamin C', 'Obat sakit kepala']), $this->amount(20_000, 45_000));
        }
    }

    /**
     * Belanja besar beberapa hari terakhir (masih di bulan ini), supaya insight "naik minggu ini"
     * dan peringatan budget Belanja muncul di dashboard.
     */
    private function seedWeeklySpike(User $user, Carbon $today): void
    {
        $spikeDate = $today->copy()->subDays(2)->max($today->copy()->startOfMonth());

        $this->expense($user, $spikeDate, 'Belanja', 'Sepatu kuliah baru', 349_000, TransactionSource::Manual);
        $this->expense($user, $spikeDate, 'Belanja', 'Checkout keranjang belanja online', 128_000, TransactionSource::Chat);
    }

    /**
     * Budget bulan ini. Belanja sengaja dibuat sudah lewat batas peringatan, sisanya aman.
     */
    private function seedBudgets(User $user, Carbon $today): void
    {
        $period = $today->format('Y-m');
        $spentPerCategory = Transaction::query()
            ->where('user_id', $user->id)
            ->where('type', TransactionType::Pengeluaran)
            ->whereBetween('transaction_date', [$today->copy()->startOfMonth()->toDateString(), $today->toDateString()])
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $plans = [
            'Makan & Minum' => ['usage' => 0.55, 'minimum' => 900_000],
            'Transport' => ['usage' => 0.5, 'minimum' => 400_000],
            'Hiburan' => ['usage' => 0.4, 'minimum' => 250_000],
            'Belanja' => ['usage' => 0.9, 'minimum' => 100_000],
        ];

        foreach ($plans as $categoryName => $plan) {
            $category = $this->expenseCategories->get($categoryName);
            $spent = (int) $spentPerCategory->get($category->id, 0);
            $amount = max($plan['minimum'], (int) (ceil($spent / $plan['usage'] / 10_000) * 10_000));

            Budget::query()->create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'amount' => $amount,
                'period' => $period,
                'alert_threshold' => self::ALERT_THRESHOLD,
            ]);
        }
    }

    /**
     * Percakapan chat hari ini. Kartunya dibuat oleh fungsi AI yang asli (tanpa memanggil API),
     * jadi bentuknya sama persis dengan hasil chat sungguhan.
     */
    private function seedChatConversation(User $user, Carbon $today): void
    {
        $this->askAssistant($user, 'catat makan siang nasi padang 25rb sama es teh 5rb', app(TambahTransaksiFunction::class), [
            'items' => [
                ['type' => 'pengeluaran', 'category_id' => $this->expenseCategories['Makan & Minum']->id, 'amount' => 25_000, 'description' => 'Nasi padang', 'transaction_date' => $today->toDateString()],
                ['type' => 'pengeluaran', 'category_id' => $this->expenseCategories['Makan & Minum']->id, 'amount' => 5_000, 'description' => 'Es teh', 'transaction_date' => $today->toDateString()],
            ],
        ]);

        $this->askAssistant($user, 'ringkasan bulan ini dong', app(RingkasanFunction::class), ['period' => $today->format('Y-m')]);

        $this->askAssistant($user, 'cek budget bulan ini', app(CekBudgetFunction::class), ['period' => $today->format('Y-m')]);
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function askAssistant(User $user, string $question, AiFunction $function, array $arguments): void
    {
        $userMessage = ChatMessage::query()->create(['user_id' => $user->id, 'role' => ChatRole::User, 'content' => $question]);

        $validated = Validator::make($arguments, $function->rules($user, $arguments))->validate();
        $result = $function->execute($user, $validated, $userMessage);

        AiFunctionLog::query()->create([
            'chat_message_id' => $userMessage->id,
            'user_id' => $user->id,
            'function_name' => $function->name(),
            'arguments' => $validated,
            'result' => $result->logPayload,
            'status' => $result->success ? AiFunctionStatus::Success : AiFunctionStatus::Failed,
        ]);

        ChatMessage::query()->create([
            'user_id' => $user->id,
            'role' => ChatRole::Assistant,
            'content' => $result->message,
            'attachment' => $result->attachment,
        ]);
    }

    private function expense(User $user, Carbon $date, string $categoryName, string $description, int $amount, ?TransactionSource $source = null): void
    {
        $this->queueTransaction($user, $this->expenseCategories->get($categoryName), TransactionType::Pengeluaran, $date, $description, $amount, $source);
    }

    private function income(User $user, Carbon $date, string $categoryName, string $description, int $amount, ?TransactionSource $source = null): void
    {
        $this->queueTransaction($user, $this->incomeCategories->get($categoryName), TransactionType::Pemasukan, $date, $description, $amount, $source);
    }

    private function queueTransaction(User $user, Category $category, TransactionType $type, Carbon $date, string $description, int $amount, ?TransactionSource $source): void
    {
        $source ??= $this->chance(40) ? TransactionSource::Chat : TransactionSource::Manual;
        $createdAt = $date->copy()->setTime($this->random->getInt(7, 21), $this->random->getInt(0, 59));

        $this->rows[] = [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'type' => $type->value,
            'amount' => $amount,
            'description' => $description,
            'transaction_date' => $date->toDateString(),
            'source' => $source->value,
            'chat_message_id' => null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    private function insertRows(): void
    {
        foreach (array_chunk($this->rows, 200) as $chunk) {
            Transaction::query()->insert($chunk);
        }

        $this->rows = [];
    }

    private function chance(int $percent): bool
    {
        return $this->random->getInt(1, 100) <= $percent;
    }

    private function amount(int $minimum, int $maximum, int $step = 1_000): int
    {
        return $this->random->getInt(intdiv($minimum, $step), intdiv($maximum, $step)) * $step;
    }

    /**
     * @param  list<string>  $options
     */
    private function pick(array $options): string
    {
        return $options[$this->random->getInt(0, count($options) - 1)];
    }
}
