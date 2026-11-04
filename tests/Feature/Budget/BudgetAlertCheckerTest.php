<?php

namespace Tests\Feature\Budget;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Budget\BudgetAlertChecker;
use App\TransactionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetAlertCheckerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_null_when_no_budget_set(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'type' => TransactionType::Pengeluaran,
            'amount' => 100000, 'transaction_date' => '2026-11-02',
        ]);

        $this->assertNull(app(BudgetAlertChecker::class)->check($transaction));
    }

    public function test_returns_null_when_below_threshold(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);
        Budget::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'period' => '2026-11', 'amount' => 1000000, 'alert_threshold' => 80]);
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'type' => TransactionType::Pengeluaran,
            'amount' => 100000, 'transaction_date' => '2026-11-02',
        ]);

        $this->assertNull(app(BudgetAlertChecker::class)->check($transaction));
    }

    public function test_returns_warning_when_at_or_above_threshold(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);
        Budget::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'period' => '2026-11', 'amount' => 100000, 'alert_threshold' => 80]);
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'type' => TransactionType::Pengeluaran,
            'amount' => 90000, 'transaction_date' => '2026-11-02',
        ]);

        $warning = app(BudgetAlertChecker::class)->check($transaction);

        $this->assertNotNull($warning);
        $this->assertSame(90.0, $warning['percentage']);
    }

    public function test_returns_null_for_pemasukan_transactions(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pemasukan]);
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'type' => TransactionType::Pemasukan,
            'amount' => 90000, 'transaction_date' => '2026-11-02',
        ]);

        $this->assertNull(app(BudgetAlertChecker::class)->check($transaction));
    }
}
