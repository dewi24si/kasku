<?php

namespace Tests\Feature\Chat;

use App\AiFunctionStatus;
use App\Livewire\Chat;
use App\Models\AiFunctionLog;
use App\Models\Budget;
use App\Models\Category;
use App\Models\ChatMessage;
use App\Models\Transaction;
use App\Models\User;
use App\TransactionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\FakesClaude;
use Tests\TestCase;

class ChatSendMessageTest extends TestCase
{
    use FakesClaude, RefreshDatabase;

    public function test_plain_text_reply_is_stored_without_function_log(): void
    {
        $user = User::factory()->create();
        $this->fakeClaudeText('Halo, ada yang bisa saya bantu?');

        Livewire::actingAs($user)->test(Chat::class)
            ->set('message', 'halo')
            ->call('sendMessage');

        $this->assertDatabaseCount('ai_function_logs', 0);
        $this->assertDatabaseHas('chat_messages', ['role' => 'assistant', 'content' => 'Halo, ada yang bisa saya bantu?']);
    }

    public function test_tambah_transaksi_creates_transaction_and_logs_success(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);

        $this->fakeClaudeToolUse('tambah_transaksi', [
            'items' => [[
                'type' => 'pengeluaran', 'category_id' => $category->id, 'amount' => 35000,
                'transaction_date' => '2026-11-02',
            ]],
        ]);

        Livewire::actingAs($user)->test(Chat::class)
            ->set('message', 'catat makan siang 35000')
            ->call('sendMessage');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id, 'category_id' => $category->id, 'amount' => 35000, 'source' => 'chat',
        ]);
        $this->assertSame(AiFunctionStatus::Success, AiFunctionLog::first()->status);
    }

    public function test_tambah_transaksi_with_multiple_items_creates_multiple_transactions(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);

        $this->fakeClaudeToolUse('tambah_transaksi', [
            'items' => [
                ['type' => 'pengeluaran', 'category_id' => $category->id, 'amount' => 35000, 'transaction_date' => '2026-11-02'],
                ['type' => 'pengeluaran', 'category_id' => $category->id, 'amount' => 15000, 'transaction_date' => '2026-11-02'],
            ],
        ]);

        Livewire::actingAs($user)->test(Chat::class)
            ->set('message', 'catat makan 35000 dan ojek 15000')
            ->call('sendMessage');

        $this->assertSame(2, Transaction::where('user_id', $user->id)->count());
    }

    public function test_tambah_transaksi_ignores_unauthorized_fields_injected_by_ai(): void
    {
        $user = User::factory()->create();
        $intruder = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);

        $this->fakeClaudeToolUse('tambah_transaksi', [
            'items' => [[
                'type' => 'pengeluaran', 'category_id' => $category->id, 'amount' => 35000,
                'transaction_date' => '2026-11-02',
                // Fields outside the declared validation schema — must never reach the model.
                'user_id' => $intruder->id,
                'id' => 999999,
            ]],
        ]);

        Livewire::actingAs($user)->test(Chat::class)
            ->set('message', 'catat makan siang 35000')
            ->call('sendMessage');

        $transaction = Transaction::first();

        $this->assertSame($user->id, $transaction->user_id);
        $this->assertNotSame(999999, $transaction->id);
        $this->assertDatabaseMissing('transactions', ['user_id' => $intruder->id]);
    }

    public function test_tambah_transaksi_rejects_category_belonging_to_another_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id, 'type' => TransactionType::Pengeluaran]);

        $this->fakeClaudeToolUse('tambah_transaksi', [
            'items' => [['type' => 'pengeluaran', 'category_id' => $otherCategory->id, 'amount' => 35000, 'transaction_date' => '2026-11-02']],
        ]);

        Livewire::actingAs($user)->test(Chat::class)
            ->set('message', 'catat sesuatu')
            ->call('sendMessage');

        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame(AiFunctionStatus::Failed, AiFunctionLog::first()->status);
    }

    public function test_tambah_transaksi_rejects_category_type_mismatch(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pemasukan]);

        $this->fakeClaudeToolUse('tambah_transaksi', [
            'items' => [['type' => 'pengeluaran', 'category_id' => $category->id, 'amount' => 35000, 'transaction_date' => '2026-11-02']],
        ]);

        Livewire::actingAs($user)->test(Chat::class)
            ->set('message', 'catat sesuatu')
            ->call('sendMessage');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_tambah_transaksi_rejects_future_date_with_clear_message(): void
    {
        $this->travelTo('2026-11-06 10:00:00');
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);

        $this->fakeClaudeToolUse('tambah_transaksi', [
            'items' => [['type' => 'pengeluaran', 'category_id' => $category->id, 'amount' => 50000, 'description' => 'Beli buku', 'transaction_date' => '2026-12-05']],
        ]);

        Livewire::actingAs($user)->test(Chat::class)
            ->set('message', 'catat beli buku 50 ribu')
            ->call('sendMessage');

        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame(AiFunctionStatus::Failed, AiFunctionLog::first()->status);
        $this->assertDatabaseHas('chat_messages', [
            'role' => 'assistant',
            'content' => 'Tanggal 05 Des 2026 masih di masa depan. Transaksi hanya bisa dicatat sampai hari ini (06 Nov 2026).',
        ]);
    }

    public function test_tambah_transaksi_accepts_todays_date(): void
    {
        $this->travelTo('2026-11-06 23:30:00');
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);

        $this->fakeClaudeToolUse('tambah_transaksi', [
            'items' => [['type' => 'pengeluaran', 'category_id' => $category->id, 'amount' => 25000, 'transaction_date' => '2026-11-06']],
        ]);

        Livewire::actingAs($user)->test(Chat::class)
            ->set('message', 'catat makan malam 25 ribu')
            ->call('sendMessage');

        $this->assertDatabaseHas('transactions', ['user_id' => $user->id, 'amount' => 25000]);
        $this->assertSame(AiFunctionStatus::Success, AiFunctionLog::first()->status);
    }

    public function test_tambah_transaksi_attaches_budget_warning_when_threshold_exceeded(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);
        Budget::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'period' => '2026-11', 'amount' => 100000, 'alert_threshold' => 80]);

        $this->fakeClaudeToolUse('tambah_transaksi', [
            'items' => [['type' => 'pengeluaran', 'category_id' => $category->id, 'amount' => 90000, 'transaction_date' => '2026-11-02']],
        ]);

        Livewire::actingAs($user)->test(Chat::class)
            ->set('message', 'catat 90000')
            ->call('sendMessage');

        $log = AiFunctionLog::first();
        $this->assertNotEmpty($log->result['warnings']);
    }

    public function test_ringkasan_returns_correct_totals(): void
    {
        $user = User::factory()->create();
        $expenseCategory = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);
        $incomeCategory = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pemasukan]);

        Transaction::factory()->create(['user_id' => $user->id, 'category_id' => $expenseCategory->id, 'type' => TransactionType::Pengeluaran, 'amount' => 50000, 'transaction_date' => '2026-11-05']);
        Transaction::factory()->create(['user_id' => $user->id, 'category_id' => $incomeCategory->id, 'type' => TransactionType::Pemasukan, 'amount' => 200000, 'transaction_date' => '2026-11-10']);

        $this->fakeClaudeToolUse('ringkasan', ['period' => '2026-11']);

        Livewire::actingAs($user)->test(Chat::class)
            ->set('message', 'ringkasan bulan ini')
            ->call('sendMessage');

        $log = AiFunctionLog::first();
        $this->assertSame(150000, $log->result['saldo']);
    }

    public function test_cari_transaksi_only_returns_own_transactions(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id, 'type' => TransactionType::Pengeluaran]);

        Transaction::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'description' => 'kopi pagi', 'transaction_date' => '2026-11-02']);
        Transaction::factory()->create(['user_id' => $otherUser->id, 'category_id' => $otherCategory->id, 'description' => 'kopi pagi juga', 'transaction_date' => '2026-11-02']);

        $this->fakeClaudeToolUse('cari_transaksi', ['keyword' => 'kopi']);

        Livewire::actingAs($user)->test(Chat::class)
            ->set('message', 'cari transaksi kopi')
            ->call('sendMessage');

        $log = AiFunctionLog::first();
        $this->assertSame(1, $log->result['count']);
    }

    public function test_tampilkan_grafik_aggregates_per_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);
        Transaction::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'type' => TransactionType::Pengeluaran, 'amount' => 20000, 'transaction_date' => '2026-11-03']);
        Transaction::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'type' => TransactionType::Pengeluaran, 'amount' => 30000, 'transaction_date' => '2026-11-04']);

        $this->fakeClaudeToolUse('tampilkan_grafik', ['period' => '2026-11', 'type' => 'pengeluaran']);

        Livewire::actingAs($user)->test(Chat::class)
            ->set('message', 'grafik pengeluaran bulan ini')
            ->call('sendMessage');

        $last = ChatMessage::where('user_id', $user->id)->latest('id')->first();
        $this->assertSame([50000], $last->attachment['datasets'][0]['data']);
    }

    public function test_atur_budget_upserts_without_duplicate(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);

        $this->fakeClaudeToolUseSequence([
            ['atur_budget', ['category_id' => $category->id, 'amount' => 500000, 'period' => '2026-11']],
            ['atur_budget', ['category_id' => $category->id, 'amount' => 700000, 'period' => '2026-11']],
        ]);

        Livewire::actingAs($user)->test(Chat::class)->set('message', 'atur budget')->call('sendMessage');
        Livewire::actingAs($user)->test(Chat::class)->set('message', 'ubah budget jadi 700rb')->call('sendMessage');

        $this->assertDatabaseCount('budgets', 1);
        $this->assertDatabaseHas('budgets', [
            'user_id' => $user->id, 'category_id' => $category->id, 'period' => '2026-11', 'amount' => 700000,
        ]);
    }

    public function test_cek_budget_reports_warning_status(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);
        Budget::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'period' => '2026-11', 'amount' => 100000, 'alert_threshold' => 80]);
        Transaction::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'type' => TransactionType::Pengeluaran, 'amount' => 90000, 'transaction_date' => '2026-11-02']);

        $this->fakeClaudeToolUse('cek_budget', ['period' => '2026-11']);

        Livewire::actingAs($user)->test(Chat::class)->set('message', 'cek budget')->call('sendMessage');

        $log = AiFunctionLog::first();
        $this->assertSame(1, $log->result['warnings']);
    }

    public function test_cek_budget_with_no_budgets_returns_friendly_empty_state(): void
    {
        $user = User::factory()->create();

        $this->fakeClaudeToolUse('cek_budget', ['period' => '2026-11']);

        Livewire::actingAs($user)->test(Chat::class)->set('message', 'cek budget')->call('sendMessage');

        $last = ChatMessage::where('user_id', $user->id)->latest('id')->first();
        $this->assertSame([], $last->attachment['budgets']);
    }
}
