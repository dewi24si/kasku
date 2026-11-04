<?php

namespace Tests\Feature\Budget;

use App\Livewire\Budget;
use App\Models\Budget as BudgetModel;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\TransactionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BudgetPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/budget')->assertRedirect('/login');
    }

    public function test_authenticated_user_sees_budget_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/budget')->assertOk();
    }

    public function test_shows_empty_state_when_no_budgets(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(Budget::class)
            ->assertSee('Belum ada budget');
    }

    public function test_can_create_a_budget(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);

        Livewire::actingAs($user)->test(Budget::class)
            ->call('openCreateForm')
            ->set('categoryId', $category->id)
            ->set('amount', 500000)
            ->set('alertThreshold', 80)
            ->call('save');

        $this->assertDatabaseHas('budgets', [
            'user_id' => $user->id, 'category_id' => $category->id, 'amount' => 500000, 'alert_threshold' => 80,
        ]);
    }

    public function test_saving_an_already_budgeted_category_updates_instead_of_duplicating(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);
        BudgetModel::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'period' => now()->format('Y-m'), 'amount' => 500000,
        ]);

        Livewire::actingAs($user)->test(Budget::class)
            ->call('openCreateForm')
            ->set('categoryId', $category->id)
            ->set('amount', 700000)
            ->set('alertThreshold', 90)
            ->call('save');

        $this->assertDatabaseCount('budgets', 1);
        $this->assertDatabaseHas('budgets', ['user_id' => $user->id, 'category_id' => $category->id, 'amount' => 700000]);
    }

    public function test_rejects_category_belonging_to_another_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id, 'type' => TransactionType::Pengeluaran]);

        Livewire::actingAs($user)->test(Budget::class)
            ->call('openCreateForm')
            ->set('categoryId', $otherCategory->id)
            ->set('amount', 500000)
            ->call('save')
            ->assertHasErrors('categoryId');

        $this->assertDatabaseCount('budgets', 0);
    }

    public function test_rejects_category_that_is_not_an_expense_type(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pemasukan]);

        Livewire::actingAs($user)->test(Budget::class)
            ->call('openCreateForm')
            ->set('categoryId', $category->id)
            ->set('amount', 500000)
            ->call('save')
            ->assertHasErrors('categoryId');

        $this->assertDatabaseCount('budgets', 0);
    }

    public function test_budget_status_is_warning_when_spending_exceeds_threshold(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);
        BudgetModel::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'period' => now()->format('Y-m'),
            'amount' => 100000, 'alert_threshold' => 80,
        ]);
        Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'type' => TransactionType::Pengeluaran,
            'amount' => 90000, 'transaction_date' => now(),
        ]);

        Livewire::actingAs($user)->test(Budget::class)
            ->assertSet('budgets.0.status', 'warning')
            ->assertSet('budgets.0.percentage', 90.0);
    }

    public function test_budget_status_is_ok_when_below_threshold(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);
        BudgetModel::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'period' => now()->format('Y-m'),
            'amount' => 1000000, 'alert_threshold' => 80,
        ]);
        Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'type' => TransactionType::Pengeluaran,
            'amount' => 100000, 'transaction_date' => now(),
        ]);

        Livewire::actingAs($user)->test(Budget::class)
            ->assertSet('budgets.0.status', 'ok');
    }

    public function test_only_shows_own_budgets(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $otherUser->id, 'type' => TransactionType::Pengeluaran]);
        BudgetModel::factory()->create([
            'user_id' => $otherUser->id, 'category_id' => $category->id, 'period' => now()->format('Y-m'),
        ]);

        Livewire::actingAs($user)->test(Budget::class)
            ->assertSee('Belum ada budget');
    }

    public function test_can_delete_own_budget(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);
        $budget = BudgetModel::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'period' => now()->format('Y-m'),
        ]);

        Livewire::actingAs($user)->test(Budget::class)
            ->call('delete', $budget->id);

        $this->assertDatabaseMissing('budgets', ['id' => $budget->id]);
    }

    public function test_cannot_delete_another_users_budget(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $owner->id, 'type' => TransactionType::Pengeluaran]);
        $budget = BudgetModel::factory()->create([
            'user_id' => $owner->id, 'category_id' => $category->id, 'period' => now()->format('Y-m'),
        ]);

        Livewire::actingAs($intruder)->test(Budget::class)
            ->call('delete', $budget->id);

        $this->assertDatabaseHas('budgets', ['id' => $budget->id]);
    }
}
