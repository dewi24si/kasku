<?php

namespace Tests\Feature\TransactionForm;

use App\Livewire\Dashboard;
use App\Livewire\TransactionForm;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\TransactionSource;
use App\TransactionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TransactionFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_add_transaction_button(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(Dashboard::class)
            ->assertSee('Tambah transaksi');
    }

    public function test_can_create_manual_transaction(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);

        Livewire::actingAs($user)->test(TransactionForm::class)
            ->call('open')
            ->assertSet('showForm', true)
            ->assertSet('transactionDate', now()->format('Y-m-d'))
            ->set('categoryId', $category->id)
            ->set('amount', 35000)
            ->set('description', 'Nasi goreng')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false)
            ->assertDispatched('transaction-saved')
            ->assertDispatched('toast', type: 'success');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'type' => 'pengeluaran',
            'amount' => 35000,
            'description' => 'Nasi goreng',
            'source' => TransactionSource::Manual->value,
        ]);
    }

    public function test_validates_required_fields(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(TransactionForm::class)
            ->call('open')
            ->call('save')
            ->assertHasErrors(['categoryId' => 'required', 'amount' => 'required']);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_rejects_category_with_mismatched_type(): void
    {
        $user = User::factory()->create();
        $incomeCategory = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pemasukan]);

        Livewire::actingAs($user)->test(TransactionForm::class)
            ->call('open')
            ->set('type', 'pengeluaran')
            ->set('categoryId', $incomeCategory->id)
            ->set('amount', 10000)
            ->call('save')
            ->assertHasErrors(['categoryId']);
    }

    public function test_rejects_category_of_other_user(): void
    {
        $user = User::factory()->create();
        $otherCategory = Category::factory()->create(['user_id' => User::factory(), 'type' => TransactionType::Pengeluaran]);

        Livewire::actingAs($user)->test(TransactionForm::class)
            ->call('open')
            ->set('categoryId', $otherCategory->id)
            ->set('amount', 10000)
            ->call('save')
            ->assertHasErrors(['categoryId']);
    }

    public function test_changing_type_resets_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);

        Livewire::actingAs($user)->test(TransactionForm::class)
            ->call('open')
            ->set('categoryId', $category->id)
            ->set('type', 'pemasukan')
            ->assertSet('categoryId', null);
    }

    public function test_can_edit_own_transaction(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'type' => TransactionType::Pengeluaran,
            'amount' => 20000, 'source' => TransactionSource::Chat,
        ]);

        Livewire::actingAs($user)->test(TransactionForm::class)
            ->call('open', $transaction->id)
            ->assertSet('editingTransactionId', $transaction->id)
            ->assertSet('amount', 20000)
            ->set('amount', 25000)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('transaction-saved');

        $transaction->refresh();
        $this->assertSame(25000, $transaction->amount);
        $this->assertSame(TransactionSource::Chat, $transaction->source);
    }

    public function test_cannot_open_other_users_transaction(): void
    {
        $user = User::factory()->create();
        $otherTransaction = Transaction::factory()->create();

        Livewire::actingAs($user)->test(TransactionForm::class)
            ->call('open', $otherTransaction->id)
            ->assertNotFound();
    }

    public function test_can_delete_own_transaction(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);
        $transaction = Transaction::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'type' => TransactionType::Pengeluaran]);

        Livewire::actingAs($user)->test(TransactionForm::class)
            ->call('open', $transaction->id)
            ->call('delete')
            ->assertSet('showForm', false)
            ->assertDispatched('transaction-saved');

        $this->assertSoftDeleted($transaction);
    }

    public function test_warns_when_expense_exceeds_budget_threshold(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);
        Budget::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id,
            'amount' => 100000, 'period' => now()->format('Y-m'), 'alert_threshold' => 80,
        ]);

        Livewire::actingAs($user)->test(TransactionForm::class)
            ->call('open')
            ->set('categoryId', $category->id)
            ->set('amount', 90000)
            ->call('save')
            ->assertDispatched('toast', type: 'warning');
    }

    public function test_dashboard_refreshes_after_transaction_saved(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pemasukan]);

        $dashboard = Livewire::actingAs($user)->test(Dashboard::class);
        $this->assertSame(0, $dashboard->get('summary')['pemasukan']);

        Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'type' => TransactionType::Pemasukan,
            'amount' => 50000, 'transaction_date' => now(),
        ]);

        $dashboard->dispatch('transaction-saved');
        $this->assertSame(50000, $dashboard->get('summary')['pemasukan']);
    }
}
