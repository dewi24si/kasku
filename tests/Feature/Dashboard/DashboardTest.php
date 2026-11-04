<?php

namespace Tests\Feature\Dashboard;

use App\Livewire\Dashboard;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\TransactionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_user_sees_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_shows_empty_state_when_no_transactions(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(Dashboard::class)
            ->assertSet('summary.saldo', 0)
            ->assertSee('Belum ada transaksi');
    }

    public function test_summary_totals_are_correct_for_current_period(): void
    {
        $user = User::factory()->create();
        $expenseCategory = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);
        $incomeCategory = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pemasukan]);

        Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $expenseCategory->id,
            'type' => TransactionType::Pengeluaran, 'amount' => 35000, 'transaction_date' => now()->startOfMonth(),
        ]);
        Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $incomeCategory->id,
            'type' => TransactionType::Pemasukan, 'amount' => 2000000, 'transaction_date' => now()->startOfMonth(),
        ]);

        Livewire::actingAs($user)->test(Dashboard::class)
            ->assertSet('summary.pemasukan', 2000000)
            ->assertSet('summary.pengeluaran', 35000)
            ->assertSet('summary.saldo', 1965000);
    }

    public function test_only_shows_own_transactions(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id, 'type' => TransactionType::Pengeluaran]);

        Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id,
            'type' => TransactionType::Pengeluaran, 'amount' => 10000, 'transaction_date' => now(),
        ]);
        Transaction::factory()->create([
            'user_id' => $otherUser->id, 'category_id' => $otherCategory->id,
            'type' => TransactionType::Pengeluaran, 'amount' => 999000, 'transaction_date' => now(),
        ]);

        Livewire::actingAs($user)->test(Dashboard::class)
            ->assertSet('summary.pengeluaran', 10000);
    }

    public function test_navigating_to_previous_period_shows_that_months_data_only(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);

        Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'type' => TransactionType::Pengeluaran,
            'amount' => 50000, 'transaction_date' => now()->subMonth()->startOfMonth(),
        ]);

        Livewire::actingAs($user)->test(Dashboard::class)
            ->assertSet('summary.pengeluaran', 0)
            ->call('previousPeriod')
            ->assertSet('summary.pengeluaran', 50000)
            ->call('nextPeriod')
            ->assertSet('summary.pengeluaran', 0);
    }

    public function test_expense_chart_aggregates_per_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);

        Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'type' => TransactionType::Pengeluaran,
            'amount' => 20000, 'transaction_date' => now(),
        ]);
        Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'type' => TransactionType::Pengeluaran,
            'amount' => 30000, 'transaction_date' => now(),
        ]);

        Livewire::actingAs($user)->test(Dashboard::class)
            ->assertSet('expenseChart.data', [50000]);
    }
}
