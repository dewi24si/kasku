<?php

namespace Tests\Feature;

use App\Models\AiFunctionLog;
use App\Models\Budget;
use App\Models\Category;
use App\Models\ChatMessage;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Insight\SpendingInsights;
use App\TransactionSource;
use App\TransactionType;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_demo_account_with_transactions_budgets_and_chat(): void
    {
        $this->travelTo('2026-11-20 10:00:00');

        $this->seed(DemoSeeder::class);

        $demo = User::query()->where('email', DemoSeeder::DEMO_EMAIL)->firstOrFail();
        $transactions = Transaction::query()->where('user_id', $demo->id)->get();

        $this->assertGreaterThan(150, $transactions->count());
        $this->assertSame('2026-09-01', $transactions->min(fn (Transaction $transaction) => $transaction->transaction_date->toDateString()));
        $this->assertTrue($transactions->contains('type', TransactionType::Pemasukan));
        $this->assertTrue($transactions->contains('type', TransactionType::Pengeluaran));
        $this->assertTrue($transactions->contains('source', TransactionSource::Chat));
        $this->assertTrue($transactions->contains('source', TransactionSource::Manual));

        $this->assertSame(4, Budget::query()->where('user_id', $demo->id)->where('period', '2026-11')->count());
        $this->assertSame(6, ChatMessage::query()->where('user_id', $demo->id)->count());
    }

    public function test_at_least_one_budget_is_past_its_alert_threshold_and_others_are_safe(): void
    {
        $this->travelTo('2026-11-20 10:00:00');

        $this->seed(DemoSeeder::class);

        $demo = User::query()->where('email', DemoSeeder::DEMO_EMAIL)->firstOrFail();
        $usage = Budget::query()->where('user_id', $demo->id)->with('category')->get()
            ->mapWithKeys(function (Budget $budget) use ($demo): array {
                $spent = Transaction::query()
                    ->where('user_id', $demo->id)
                    ->where('category_id', $budget->category_id)
                    ->where('type', TransactionType::Pengeluaran)
                    ->whereBetween('transaction_date', ['2026-11-01', '2026-11-30'])
                    ->sum('amount');

                return [$budget->category->name => $spent / $budget->amount * 100];
            });

        $this->assertGreaterThanOrEqual(80, $usage['Belanja']);
        $this->assertLessThan(80, $usage['Makan & Minum']);
        $this->assertLessThan(80, $usage['Transport']);
        $this->assertLessThan(80, $usage['Hiburan']);
    }

    public function test_running_twice_does_not_duplicate_data(): void
    {
        $this->travelTo('2026-11-20 10:00:00');

        $this->seed(DemoSeeder::class);
        $demo = User::query()->where('email', DemoSeeder::DEMO_EMAIL)->firstOrFail();
        $counts = $this->countsFor($demo);

        $this->seed(DemoSeeder::class);

        $this->assertSame(1, User::query()->where('email', DemoSeeder::DEMO_EMAIL)->count());
        $this->assertSame($counts, $this->countsFor($demo));
        $this->assertSame(12, Category::query()->whereNull('user_id')->count());
    }

    public function test_does_not_touch_other_users_data(): void
    {
        $this->travelTo('2026-11-20 10:00:00');
        $other = User::factory()->create(['password' => 'rahasia-lain']);
        $category = Category::factory()->create(['user_id' => $other->id, 'type' => TransactionType::Pengeluaran]);
        Transaction::factory()->count(3)->create(['user_id' => $other->id, 'category_id' => $category->id, 'transaction_date' => '2026-11-10']);
        Budget::query()->create(['user_id' => $other->id, 'category_id' => $category->id, 'amount' => 100_000, 'period' => '2026-11', 'alert_threshold' => 80]);
        ChatMessage::query()->create(['user_id' => $other->id, 'role' => 'user', 'content' => 'halo']);
        $before = $this->countsFor($other);
        $passwordHash = $other->password;

        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        $this->assertSame($before, $this->countsFor($other));
        $this->assertSame(1, Category::query()->where('user_id', $other->id)->count());
        $this->assertSame($passwordHash, $other->refresh()->password);
    }

    public function test_uses_password_from_demo_password_setting(): void
    {
        config(['app.demo_password' => 'demo-seminar-2026']);

        $this->seed(DemoSeeder::class);

        $demo = User::query()->where('email', DemoSeeder::DEMO_EMAIL)->firstOrFail();
        $this->assertTrue(Hash::check('demo-seminar-2026', $demo->password));
    }

    public function test_never_creates_future_transactions(): void
    {
        $this->travelTo('2026-11-20 10:00:00');

        $this->seed(DemoSeeder::class);

        $demo = User::query()->where('email', DemoSeeder::DEMO_EMAIL)->firstOrFail();
        $this->assertSame(0, Transaction::query()->where('user_id', $demo->id)->where('transaction_date', '>', '2026-11-20')->count());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function demoDates(): array
    {
        return [
            'awal bulan' => ['2026-11-02 09:00:00'],
            'tengah bulan' => ['2026-11-20 09:00:00'],
            'akhir bulan' => ['2026-11-30 09:00:00'],
        ];
    }

    #[DataProvider('demoDates')]
    public function test_dashboard_insights_show_budget_warning_and_weekly_increase(string $now): void
    {
        $this->travelTo($now);

        $this->seed(DemoSeeder::class);

        $demo = User::query()->where('email', DemoSeeder::DEMO_EMAIL)->firstOrFail();
        $messages = collect(app(SpendingInsights::class)->forUser($demo, Carbon::today(), 10))->pluck('message');

        $this->assertTrue($messages->contains(fn (string $message): bool => str_starts_with($message, 'Budget Belanja bulan ini')));
        $this->assertTrue($messages->contains(fn (string $message): bool => str_contains($message, '7 hari terakhir naik')));
    }

    /**
     * @return array{transactions: int, budgets: int, chat_messages: int, ai_function_logs: int}
     */
    private function countsFor(User $user): array
    {
        return [
            'transactions' => Transaction::withTrashed()->where('user_id', $user->id)->count(),
            'budgets' => Budget::query()->where('user_id', $user->id)->count(),
            'chat_messages' => ChatMessage::query()->where('user_id', $user->id)->count(),
            'ai_function_logs' => AiFunctionLog::query()->where('user_id', $user->id)->count(),
        ];
    }
}
