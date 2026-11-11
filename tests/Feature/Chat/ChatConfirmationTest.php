<?php

namespace Tests\Feature\Chat;

use App\AiFunctionStatus;
use App\Livewire\Chat;
use App\Models\AiFunctionLog;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\TransactionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\FakesClaude;
use Tests\TestCase;

class ChatConfirmationTest extends TestCase
{
    use FakesClaude, RefreshDatabase;

    public function test_ubah_transaksi_requires_confirmation_before_mutating(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);
        $transaction = Transaction::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'amount' => 20000, 'type' => TransactionType::Pengeluaran]);

        $this->fakeClaudeToolUse('ubah_transaksi', ['transaction_id' => $transaction->id, 'changes' => ['amount' => 50000]]);
        Livewire::actingAs($user)->test(Chat::class)->set('message', 'ubah jadi 50000')->call('sendMessage');

        $this->assertSame(20000, $transaction->refresh()->amount);
        $log = AiFunctionLog::first();
        $this->assertSame(AiFunctionStatus::PendingConfirmation, $log->status);

        Livewire::actingAs($user)->test(Chat::class)->call('confirmFunction', $log->id);

        $this->assertSame(50000, $transaction->refresh()->amount);
        $this->assertSame(AiFunctionStatus::Confirmed, $log->refresh()->status);
    }

    public function test_ubah_transaksi_rejects_moving_date_to_the_future(): void
    {
        $this->travelTo('2026-11-06 10:00:00');
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'type' => TransactionType::Pengeluaran, 'transaction_date' => '2026-11-02',
        ]);

        $this->fakeClaudeToolUse('ubah_transaksi', ['transaction_id' => $transaction->id, 'changes' => ['transaction_date' => '2026-12-05']]);
        Livewire::actingAs($user)->test(Chat::class)->set('message', 'pindahkan ke 5 desember')->call('sendMessage');

        $this->assertSame('2026-11-02', $transaction->refresh()->transaction_date->toDateString());
        $this->assertSame(AiFunctionStatus::Failed, AiFunctionLog::first()->status);
        $this->assertDatabaseMissing('ai_function_logs', ['status' => AiFunctionStatus::PendingConfirmation->value]);
    }

    public function test_hapus_transaksi_soft_deletes_only_after_confirmation(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);
        $transaction = Transaction::factory()->create(['user_id' => $user->id, 'category_id' => $category->id]);

        $this->fakeClaudeToolUse('hapus_transaksi', ['transaction_id' => $transaction->id]);
        Livewire::actingAs($user)->test(Chat::class)->set('message', 'hapus itu')->call('sendMessage');

        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'deleted_at' => null]);

        $log = AiFunctionLog::first();
        Livewire::actingAs($user)->test(Chat::class)->call('confirmFunction', $log->id);

        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
        $this->assertSame(AiFunctionStatus::Confirmed, $log->refresh()->status);
    }

    public function test_cancel_leaves_transaction_untouched(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);
        $transaction = Transaction::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'amount' => 20000]);

        $this->fakeClaudeToolUse('ubah_transaksi', ['transaction_id' => $transaction->id, 'changes' => ['amount' => 50000]]);
        Livewire::actingAs($user)->test(Chat::class)->set('message', 'ubah')->call('sendMessage');

        $log = AiFunctionLog::first();
        Livewire::actingAs($user)->test(Chat::class)->call('cancelFunction', $log->id);

        $this->assertSame(20000, $transaction->refresh()->amount);
        $this->assertSame(AiFunctionStatus::Cancelled, $log->refresh()->status);
    }

    public function test_cannot_target_another_users_transaction_for_update(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id, 'type' => TransactionType::Pengeluaran]);
        $otherTransaction = Transaction::factory()->create(['user_id' => $otherUser->id, 'category_id' => $otherCategory->id, 'amount' => 20000]);

        $this->fakeClaudeToolUse('ubah_transaksi', ['transaction_id' => $otherTransaction->id, 'changes' => ['amount' => 1]]);
        Livewire::actingAs($user)->test(Chat::class)->set('message', 'ubah')->call('sendMessage');

        $this->assertSame(20000, $otherTransaction->refresh()->amount);
        $this->assertDatabaseMissing('ai_function_logs', ['status' => 'pending_confirmation']);
        $this->assertSame(AiFunctionStatus::Failed, AiFunctionLog::first()->status);
    }

    public function test_cannot_target_another_users_transaction_for_delete(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id, 'type' => TransactionType::Pengeluaran]);
        $otherTransaction = Transaction::factory()->create(['user_id' => $otherUser->id, 'category_id' => $otherCategory->id]);

        $this->fakeClaudeToolUse('hapus_transaksi', ['transaction_id' => $otherTransaction->id]);
        Livewire::actingAs($user)->test(Chat::class)->set('message', 'hapus')->call('sendMessage');

        $this->assertDatabaseHas('transactions', ['id' => $otherTransaction->id, 'deleted_at' => null]);
        $this->assertSame(AiFunctionStatus::Failed, AiFunctionLog::first()->status);
    }

    public function test_another_user_cannot_confirm_someone_elses_pending_log(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $owner->id, 'type' => TransactionType::Pengeluaran]);
        $transaction = Transaction::factory()->create(['user_id' => $owner->id, 'category_id' => $category->id, 'amount' => 20000]);

        $this->fakeClaudeToolUse('ubah_transaksi', ['transaction_id' => $transaction->id, 'changes' => ['amount' => 50000]]);
        Livewire::actingAs($owner)->test(Chat::class)->set('message', 'ubah')->call('sendMessage');

        $log = AiFunctionLog::first();

        // Livewire's test harness catches the exception thrown by the user_id-scoped
        // findOrFail internally rather than letting it bubble to PHPUnit. What matters
        // is that nothing mutates before that exception point.
        Livewire::actingAs($intruder)->test(Chat::class)->call('confirmFunction', $log->id);

        $this->assertSame(20000, $transaction->refresh()->amount);
        $this->assertSame(AiFunctionStatus::PendingConfirmation, $log->refresh()->status);
    }

    public function test_another_user_cannot_cancel_someone_elses_pending_log(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $owner->id, 'type' => TransactionType::Pengeluaran]);
        $transaction = Transaction::factory()->create(['user_id' => $owner->id, 'category_id' => $category->id]);

        $this->fakeClaudeToolUse('hapus_transaksi', ['transaction_id' => $transaction->id]);
        Livewire::actingAs($owner)->test(Chat::class)->set('message', 'hapus')->call('sendMessage');

        $log = AiFunctionLog::first();

        Livewire::actingAs($intruder)->test(Chat::class)->call('cancelFunction', $log->id);

        $this->assertSame(AiFunctionStatus::PendingConfirmation, $log->refresh()->status);
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'deleted_at' => null]);
    }

    public function test_update_confirmation_card_shows_old_and_new_values_only_for_changed_fields(): void
    {
        $this->travelTo('2026-11-11 10:00:00');
        $user = User::factory()->create();
        $food = Category::factory()->create(['user_id' => $user->id, 'name' => 'Makan & Minum', 'type' => TransactionType::Pengeluaran]);
        $shopping = Category::factory()->create(['user_id' => $user->id, 'name' => 'Belanja', 'type' => TransactionType::Pengeluaran]);
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $food->id,
            'type' => TransactionType::Pengeluaran,
            'amount' => 40000,
            'description' => 'Toko Sinar',
            'transaction_date' => '2026-11-10',
        ]);

        $this->fakeClaudeToolUse('ubah_transaksi', [
            'transaction_id' => $transaction->id,
            'changes' => ['amount' => 16000, 'category_id' => $shopping->id],
        ]);

        Livewire::actingAs($user)->test(Chat::class)
            ->set('message', 'ubah jadi 16 ribu, kategori belanja')
            ->call('sendMessage')
            ->assertSee('Konfirmasi ubah')
            ->assertSee('Toko Sinar')
            ->assertSee('10 Nov 2026')
            ->assertSeeInOrder(['Nominal', 'Rp40.000', 'Rp16.000'])
            ->assertSeeInOrder(['Kategori', 'Makan', 'Belanja'])
            ->assertSeeHtml('<span class="line-through text-neutral rupiah">Rp40.000</span>')
            ->assertSeeHtml('<span class="font-semibold rupiah">Rp16.000</span>')
            ->assertDontSeeHtml('>Tanggal</p>')
            ->assertDontSeeHtml('>Deskripsi</p>')
            ->assertDontSeeHtml('>Tipe</p>');
    }

    public function test_update_confirmation_card_hides_category_row_when_only_amount_changes(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'name' => 'Transport', 'type' => TransactionType::Pengeluaran]);
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'type' => TransactionType::Pengeluaran, 'amount' => 20000,
        ]);

        $this->fakeClaudeToolUse('ubah_transaksi', ['transaction_id' => $transaction->id, 'changes' => ['amount' => 25000]]);

        Livewire::actingAs($user)->test(Chat::class)
            ->set('message', 'ubah jadi 25 ribu')
            ->call('sendMessage')
            ->assertSeeInOrder(['Nominal', 'Rp20.000', 'Rp25.000'])
            ->assertDontSeeHtml('>Kategori</p>');
    }

    public function test_delete_confirmation_card_shows_description_category_date_and_amount(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'name' => 'Transport', 'type' => TransactionType::Pengeluaran]);
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'type' => TransactionType::Pengeluaran,
            'amount' => 32000,
            'description' => 'Warung Maju',
            'transaction_date' => '2026-11-03',
        ]);

        $this->fakeClaudeToolUse('hapus_transaksi', ['transaction_id' => $transaction->id]);

        Livewire::actingAs($user)->test(Chat::class)
            ->set('message', 'hapus yang warung maju')
            ->call('sendMessage')
            ->assertSee('Konfirmasi hapus')
            ->assertSee('Warung Maju')
            ->assertSee('Transport')
            ->assertSee('03 Nov 2026')
            ->assertSee('Rp32.000');
    }
}
