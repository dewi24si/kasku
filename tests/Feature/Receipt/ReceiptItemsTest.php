<?php

namespace Tests\Feature\Receipt;

use App\AiFunctionStatus;
use App\Livewire\Chat;
use App\Livewire\TransactionForm;
use App\Livewire\TransactionHistory;
use App\Models\AiFunctionLog;
use App\Models\Category;
use App\Models\ChatMessage;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\TransactionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\FakesClaude;
use Tests\TestCase;

class ReceiptItemsTest extends TestCase
{
    use FakesClaude, RefreshDatabase;

    private User $user;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $this->user = User::factory()->create();
        $this->category = Category::factory()->create(['user_id' => $this->user->id, 'type' => TransactionType::Pengeluaran]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $receiptItems
     * @return array<string, mixed>
     */
    private function transactionArgs(int $amount, array $receiptItems, string $description = 'Toko Sinar'): array
    {
        return [
            'type' => 'pengeluaran',
            'category_id' => $this->category->id,
            'amount' => $amount,
            'description' => $description,
            'transaction_date' => now()->toDateString(),
            'receipt_items' => $receiptItems,
        ];
    }

    private function sendPhoto(): void
    {
        Livewire::actingAs($this->user)->test(Chat::class)
            ->set('photo', UploadedFile::fake()->image('struk.jpg'))
            ->call('sendMessage');
    }

    public function test_tambah_transaksi_from_photo_saves_receipt_items_and_receipt_path(): void
    {
        $this->fakeClaudeToolUse('tambah_transaksi', ['items' => [
            $this->transactionArgs(16000, [
                ['name' => 'Roti Coklat', 'quantity' => 2, 'unit_price' => 12000, 'subtotal' => 24000],
                ['name' => 'Kopi Susu', 'subtotal' => 16000],
                ['name' => 'Air Mineral', 'quantity' => 1.5, 'subtotal' => 8000],
            ]),
        ]]);

        $this->sendPhoto();

        $userMessage = ChatMessage::query()->where('user_id', $this->user->id)->where('role', 'user')->firstOrFail();
        $transaction = Transaction::query()->where('user_id', $this->user->id)->firstOrFail();

        $this->assertSame(16000, $transaction->amount);
        $this->assertSame($userMessage->attachment['path'], $transaction->receipt_path);
        $this->assertCount(3, $transaction->items);
        $this->assertDatabaseHas('transaction_items', [
            'transaction_id' => $transaction->id, 'name' => 'Roti Coklat', 'quantity' => 2, 'unit_price' => 12000, 'subtotal' => 24000,
        ]);
        $this->assertDatabaseHas('transaction_items', [
            'transaction_id' => $transaction->id, 'name' => 'Kopi Susu', 'quantity' => null, 'unit_price' => null, 'subtotal' => 16000,
        ]);

        $assistantCard = ChatMessage::query()->where('user_id', $this->user->id)->where('role', 'assistant')->firstOrFail();
        $this->assertSame(3, $assistantCard->attachment['transactions'][0]['receipt_items_count']);

        $this->actingAs($this->user)->get(route('chat'))->assertSee('3 barang');
    }

    public function test_split_receipt_transactions_all_point_to_the_same_photo(): void
    {
        $this->fakeClaudeToolUse('tambah_transaksi', ['items' => [
            $this->transactionArgs(20000, [['name' => 'Sabun', 'subtotal' => 20000]]),
            $this->transactionArgs(12000, [['name' => 'Roti', 'subtotal' => 12000]]),
        ]]);

        $this->sendPhoto();

        $transactions = Transaction::query()->where('user_id', $this->user->id)->get();

        $this->assertCount(2, $transactions);
        $this->assertCount(1, $transactions->pluck('receipt_path')->unique());
        $this->assertNotNull($transactions->first()->receipt_path);
        $this->assertSame(2, TransactionItem::query()->count());
    }

    public function test_receipt_items_are_ignored_when_the_message_has_no_photo(): void
    {
        $this->fakeClaudeToolUse('tambah_transaksi', ['items' => [
            $this->transactionArgs(15000, [['name' => 'Barang karangan', 'subtotal' => 15000]], 'Makan siang'),
        ]]);

        Livewire::actingAs($this->user)->test(Chat::class)
            ->set('message', 'makan siang 15rb')
            ->call('sendMessage');

        $transaction = Transaction::query()->where('user_id', $this->user->id)->firstOrFail();

        $this->assertNull($transaction->receipt_path);
        $this->assertSame(0, TransactionItem::query()->count());
    }

    public function test_rejects_more_than_fifty_receipt_items(): void
    {
        $tooManyItems = array_map(fn (int $index): array => ['name' => "Barang {$index}", 'subtotal' => 1000], range(1, 51));

        $this->fakeClaudeToolUse('tambah_transaksi', ['items' => [$this->transactionArgs(51000, $tooManyItems)]]);

        $this->sendPhoto();

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_items', 0);
        $this->assertSame(AiFunctionStatus::Failed, AiFunctionLog::query()->firstOrFail()->status);
    }

    public function test_rejects_receipt_item_name_longer_than_one_hundred_characters(): void
    {
        $this->fakeClaudeToolUse('tambah_transaksi', ['items' => [
            $this->transactionArgs(10000, [['name' => str_repeat('a', 101), 'subtotal' => 10000]]),
        ]]);

        $this->sendPhoto();

        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame(AiFunctionStatus::Failed, AiFunctionLog::query()->firstOrFail()->status);
    }

    public function test_rejects_negative_or_non_integer_subtotal(): void
    {
        $this->fakeClaudeToolUse('tambah_transaksi', ['items' => [
            $this->transactionArgs(10000, [['name' => 'Roti', 'subtotal' => -500]]),
        ]]);

        $this->sendPhoto();

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_transaction_form_shows_receipt_details_with_discount_row(): void
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'type' => TransactionType::Pengeluaran,
            'amount' => 16000,
            'receipt_path' => "receipts/{$this->user->id}/struk.jpg",
        ]);
        TransactionItem::factory()->create(['transaction_id' => $transaction->id, 'name' => 'Roti Coklat', 'quantity' => 2, 'unit_price' => 12000, 'subtotal' => 24000]);
        TransactionItem::factory()->create(['transaction_id' => $transaction->id, 'name' => 'Kopi Susu', 'quantity' => null, 'unit_price' => null, 'subtotal' => 16000]);

        Livewire::actingAs($this->user)->test(TransactionForm::class)
            ->call('open', $transaction->id)
            ->assertSee('Rincian Struk')
            ->assertSee(route('struk.foto', $transaction), false)
            ->assertSeeInOrder(['Roti Coklat', 'x 2', 'Rp24.000', 'Kopi Susu', 'Rp16.000'])
            ->assertSeeInOrder(['Total barang', 'Rp40.000', 'Potongan', '-Rp24.000', 'Dibayar', 'Rp16.000']);
    }

    public function test_transaction_form_hides_discount_row_when_items_total_matches_amount(): void
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'type' => TransactionType::Pengeluaran,
            'amount' => 30000,
            'receipt_path' => "receipts/{$this->user->id}/struk.jpg",
        ]);
        TransactionItem::factory()->create(['transaction_id' => $transaction->id, 'subtotal' => 30000]);

        Livewire::actingAs($this->user)->test(TransactionForm::class)
            ->call('open', $transaction->id)
            ->assertSee('Total barang')
            ->assertDontSee('Potongan')
            ->assertDontSee('Dibayar');
    }

    public function test_manual_transaction_form_has_no_receipt_section(): void
    {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id, 'category_id' => $this->category->id]);

        Livewire::actingAs($this->user)->test(TransactionForm::class)
            ->call('open', $transaction->id)
            ->assertDontSee('Rincian Struk');
    }

    public function test_history_marks_only_transactions_with_receipt_photo(): void
    {
        Transaction::factory()->create([
            'user_id' => $this->user->id, 'category_id' => $this->category->id, 'description' => 'Dari struk',
            'receipt_path' => "receipts/{$this->user->id}/struk.jpg",
        ]);

        Livewire::actingAs($this->user)->test(TransactionHistory::class)
            ->assertSee('Ada foto struk');

        Transaction::query()->update(['receipt_path' => null]);

        Livewire::actingAs($this->user)->test(TransactionHistory::class)
            ->assertDontSee('Ada foto struk');
    }
}
