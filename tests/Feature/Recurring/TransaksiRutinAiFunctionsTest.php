<?php

namespace Tests\Feature\Recurring;

use App\AiFunctionStatus;
use App\Livewire\Chat;
use App\Models\AiFunctionLog;
use App\Models\Category;
use App\Models\ChatMessage;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Ai\FunctionRegistry;
use App\Services\Ai\Functions\AturTransaksiRutinFunction;
use App\Services\Ai\Functions\LihatTransaksiRutinFunction;
use App\Services\Ai\Functions\NonaktifkanTransaksiRutinFunction;
use App\Services\Ai\PromptBuilder;
use App\TransactionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\FakesClaude;
use Tests\TestCase;

class TransaksiRutinAiFunctionsTest extends TestCase
{
    use FakesClaude, RefreshDatabase;

    private User $user;

    private Category $expenseCategory;

    private Category $incomeCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-09 10:00:00');
        $this->user = User::factory()->create();
        $this->expenseCategory = Category::factory()->create(['user_id' => null, 'name' => 'Tagihan', 'type' => TransactionType::Pengeluaran]);
        $this->incomeCategory = Category::factory()->create(['user_id' => null, 'name' => 'Gaji', 'type' => TransactionType::Pemasukan]);
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function sendToolCall(string $function, array $args, string $message = 'pesan'): void
    {
        $this->fakeClaudeToolUse($function, $args);

        Livewire::actingAs($this->user)->test(Chat::class)
            ->set('message', $message)
            ->call('sendMessage');
    }

    public function test_new_functions_are_registered(): void
    {
        $registry = app(FunctionRegistry::class);

        $this->assertInstanceOf(AturTransaksiRutinFunction::class, $registry->resolve('atur_transaksi_rutin'));
        $this->assertInstanceOf(LihatTransaksiRutinFunction::class, $registry->resolve('lihat_transaksi_rutin'));
        $this->assertInstanceOf(NonaktifkanTransaksiRutinFunction::class, $registry->resolve('nonaktifkan_transaksi_rutin'));
    }

    public function test_atur_transaksi_rutin_creates_rule_from_chat_and_shows_rutin_card(): void
    {
        $this->sendToolCall('atur_transaksi_rutin', [
            'type' => 'pengeluaran',
            'category_id' => $this->expenseCategory->id,
            'amount' => 800_000,
            'description' => 'Bayar kos',
            'day_of_month' => 1,
        ], 'tiap tanggal 1 catat bayar kos 800rb');

        $rule = RecurringTransaction::query()->sole();
        $this->assertSame($this->user->id, $rule->user_id);
        $this->assertSame($this->expenseCategory->id, $rule->category_id);
        $this->assertSame(TransactionType::Pengeluaran, $rule->type);
        $this->assertSame(800_000, $rule->amount);
        $this->assertSame('Bayar kos', $rule->description);
        $this->assertSame(1, $rule->day_of_month);
        $this->assertTrue($rule->is_active);
        $this->assertSame('2026-12-01', $rule->next_run_date->toDateString());
        $this->assertSame(0, Transaction::query()->count());

        $log = AiFunctionLog::query()->sole();
        $this->assertSame('atur_transaksi_rutin', $log->function_name);
        $this->assertSame(AiFunctionStatus::Success, $log->status);

        $reply = ChatMessage::query()->where('role', 'assistant')->sole();
        $this->assertSame('recurring', $reply->attachment['type']);
        $this->assertSame('created', $reply->attachment['action']);

        Livewire::actingAs($this->user)->test(Chat::class)
            ->assertSee('Rutin')
            ->assertSeeInOrder(['Bayar kos', '-Rp800.000', 'Tiap tanggal 1 · berikutnya 01 Des'])
            ->assertSeeHtml('data-lucide="repeat"');
    }

    public function test_salary_rule_for_today_is_recorded_immediately(): void
    {
        $this->travelTo('2026-11-25 09:00:00');

        $this->sendToolCall('atur_transaksi_rutin', [
            'type' => 'pemasukan',
            'category_id' => $this->incomeCategory->id,
            'amount' => 3_500_000,
            'description' => 'Gaji',
            'day_of_month' => 25,
        ], 'gaji masuk tiap tanggal 25 3,5jt');

        $rule = RecurringTransaction::query()->sole();
        $transaction = Transaction::query()->sole();
        $this->assertSame($rule->id, $transaction->recurring_transaction_id);
        $this->assertSame(TransactionType::Pemasukan, $transaction->type);
        $this->assertSame('2026-11-25', $transaction->transaction_date->toDateString());
        $this->assertSame('2026-12-25', $rule->next_run_date->toDateString());

        Livewire::actingAs($this->user)->test(Chat::class)
            ->assertSee('Transaksi hari ini sudah dicatat.');
    }

    public function test_atur_transaksi_rutin_rejects_category_of_wrong_type(): void
    {
        $this->sendToolCall('atur_transaksi_rutin', [
            'type' => 'pengeluaran',
            'category_id' => $this->incomeCategory->id,
            'amount' => 800_000,
            'description' => 'Bayar kos',
            'day_of_month' => 1,
        ]);

        $this->assertSame(0, RecurringTransaction::query()->count());
        $this->assertSame(AiFunctionStatus::Failed, AiFunctionLog::query()->sole()->status);
    }

    public function test_atur_transaksi_rutin_rejects_invalid_day(): void
    {
        $this->sendToolCall('atur_transaksi_rutin', [
            'type' => 'pengeluaran',
            'category_id' => $this->expenseCategory->id,
            'amount' => 800_000,
            'description' => 'Bayar kos',
            'day_of_month' => 32,
        ]);

        $this->assertSame(0, RecurringTransaction::query()->count());
    }

    public function test_atur_transaksi_rutin_rejects_other_users_category(): void
    {
        $otherCategory = Category::factory()->create(['user_id' => User::factory()->create()->id, 'type' => TransactionType::Pengeluaran]);

        $this->sendToolCall('atur_transaksi_rutin', [
            'type' => 'pengeluaran',
            'category_id' => $otherCategory->id,
            'amount' => 800_000,
            'description' => 'Bayar kos',
            'day_of_month' => 1,
        ]);

        $this->assertSame(0, RecurringTransaction::query()->count());
    }

    public function test_atur_transaksi_rutin_ignores_user_id_injected_by_ai(): void
    {
        $otherUser = User::factory()->create();

        $this->sendToolCall('atur_transaksi_rutin', [
            'type' => 'pengeluaran',
            'category_id' => $this->expenseCategory->id,
            'amount' => 800_000,
            'description' => 'Bayar kos',
            'day_of_month' => 1,
            'user_id' => $otherUser->id,
        ]);
        $this->assertSame($this->user->id, RecurringTransaction::query()->sole()->user_id);
    }

    public function test_lihat_transaksi_rutin_lists_only_own_active_rules(): void
    {
        RecurringTransaction::factory()->create(['user_id' => $this->user->id, 'category_id' => $this->expenseCategory->id, 'description' => 'Bayar kos']);
        RecurringTransaction::factory()->inactive()->create(['user_id' => $this->user->id, 'category_id' => $this->expenseCategory->id, 'description' => 'Langganan lama']);
        RecurringTransaction::factory()->create(['user_id' => User::factory()->create()->id, 'category_id' => $this->expenseCategory->id, 'description' => 'Kos orang lain']);

        $this->sendToolCall('lihat_transaksi_rutin', []);

        $attachment = ChatMessage::query()->where('role', 'assistant')->sole()->attachment;
        $this->assertSame('list', $attachment['action']);
        $this->assertSame(['Bayar kos'], array_column($attachment['rules'], 'description'));
    }

    public function test_lihat_transaksi_rutin_can_include_inactive_rules(): void
    {
        RecurringTransaction::factory()->inactive()->create(['user_id' => $this->user->id, 'category_id' => $this->expenseCategory->id, 'description' => 'Langganan lama', 'day_of_month' => 3]);

        $this->sendToolCall('lihat_transaksi_rutin', ['include_inactive' => true]);

        $attachment = ChatMessage::query()->where('role', 'assistant')->sole()->attachment;
        $this->assertSame(['Langganan lama'], array_column($attachment['rules'], 'description'));

        Livewire::actingAs($this->user)->test(Chat::class)
            ->assertSee('Tiap tanggal 3 · nonaktif');
    }

    public function test_nonaktifkan_transaksi_rutin_requires_confirmation(): void
    {
        $rule = RecurringTransaction::factory()->create(['user_id' => $this->user->id, 'category_id' => $this->expenseCategory->id, 'description' => 'Bayar kos', 'amount' => 800_000]);

        $this->sendToolCall('nonaktifkan_transaksi_rutin', ['recurring_transaction_id' => $rule->id], 'stop catat kos otomatis');

        $this->assertTrue($rule->refresh()->is_active);
        $log = AiFunctionLog::query()->sole();
        $this->assertSame(AiFunctionStatus::PendingConfirmation, $log->status);

        Livewire::actingAs($this->user)->test(Chat::class)
            ->assertSee('Konfirmasi nonaktifkan')
            ->assertSee('Bayar kos')
            ->call('confirmFunction', $log->id);

        $this->assertFalse($rule->refresh()->is_active);
        $this->assertSame(AiFunctionStatus::Confirmed, $log->refresh()->status);
        $this->assertSame('deactivated', ChatMessage::query()->where('role', 'assistant')->latest('id')->first()->attachment['action']);
    }

    public function test_cancelling_deactivation_keeps_rule_active(): void
    {
        $rule = RecurringTransaction::factory()->create(['user_id' => $this->user->id, 'category_id' => $this->expenseCategory->id]);

        $this->sendToolCall('nonaktifkan_transaksi_rutin', ['recurring_transaction_id' => $rule->id]);
        Livewire::actingAs($this->user)->test(Chat::class)->call('cancelFunction', AiFunctionLog::query()->sole()->id);

        $this->assertTrue($rule->refresh()->is_active);
    }

    public function test_cannot_deactivate_other_users_rule(): void
    {
        $otherRule = RecurringTransaction::factory()->create(['user_id' => User::factory()->create()->id, 'category_id' => $this->expenseCategory->id]);

        $this->sendToolCall('nonaktifkan_transaksi_rutin', ['recurring_transaction_id' => $otherRule->id]);

        $this->assertTrue($otherRule->refresh()->is_active);
        $this->assertSame(AiFunctionStatus::Failed, AiFunctionLog::query()->sole()->status);
    }

    public function test_prompt_lists_only_own_recurring_rules_and_mentions_new_functions(): void
    {
        $ownRule = RecurringTransaction::factory()->create(['user_id' => $this->user->id, 'category_id' => $this->expenseCategory->id, 'description' => 'Bayar kos']);
        RecurringTransaction::factory()->create(['user_id' => User::factory()->create()->id, 'category_id' => $this->expenseCategory->id, 'description' => 'Kos orang lain']);

        $prompt = app(PromptBuilder::class)->build($this->user);

        $this->assertStringContainsString('Daftar transaksi rutin bulanan milik user', $prompt);
        $this->assertStringContainsString('"id":'.$ownRule->id, $prompt);
        $this->assertStringContainsString('Bayar kos', $prompt);
        $this->assertStringNotContainsString('Kos orang lain', $prompt);
        $this->assertStringContainsString('atur_transaksi_rutin', $prompt);
        $this->assertStringContainsString('nonaktifkan_transaksi_rutin', $prompt);
    }
}
