<?php

namespace Tests\Feature\TransactionHistory;

use App\Livewire\TransactionHistory;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\TransactionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TransactionHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/riwayat')->assertRedirect('/login');
    }

    public function test_authenticated_user_sees_history_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/riwayat')->assertOk();
    }

    public function test_shows_transactions_from_all_periods_not_just_current_month(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);

        Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'type' => TransactionType::Pengeluaran,
            'transaction_date' => now()->subMonths(6),
        ]);

        $component = Livewire::actingAs($user)->test(TransactionHistory::class);

        $this->assertSame(1, $component->get('transactions')->total());
    }

    public function test_only_shows_own_transactions(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id, 'type' => TransactionType::Pengeluaran]);

        Transaction::factory()->create(['user_id' => $user->id, 'category_id' => $category->id]);
        Transaction::factory()->create(['user_id' => $otherUser->id, 'category_id' => $otherCategory->id]);

        $component = Livewire::actingAs($user)->test(TransactionHistory::class);

        $this->assertSame(1, $component->get('transactions')->total());
    }

    public function test_search_filters_by_description(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);

        Transaction::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'description' => 'Netflix bulanan']);
        Transaction::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'description' => 'Makan siang']);

        $component = Livewire::actingAs($user)->test(TransactionHistory::class)
            ->set('search', 'netflix');

        $this->assertSame(1, $component->get('transactions')->total());
    }

    public function test_type_filter_narrows_results(): void
    {
        $user = User::factory()->create();
        $expenseCategory = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);
        $incomeCategory = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pemasukan]);

        Transaction::factory()->create(['user_id' => $user->id, 'category_id' => $expenseCategory->id, 'type' => TransactionType::Pengeluaran]);
        Transaction::factory()->create(['user_id' => $user->id, 'category_id' => $incomeCategory->id, 'type' => TransactionType::Pemasukan]);

        $component = Livewire::actingAs($user)->test(TransactionHistory::class)
            ->set('type', 'pemasukan');

        $this->assertSame(1, $component->get('transactions')->total());
    }

    public function test_category_filter_narrows_results(): void
    {
        $user = User::factory()->create();
        $categoryA = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);
        $categoryB = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);

        Transaction::factory()->create(['user_id' => $user->id, 'category_id' => $categoryA->id]);
        Transaction::factory()->create(['user_id' => $user->id, 'category_id' => $categoryB->id]);

        $component = Livewire::actingAs($user)->test(TransactionHistory::class)
            ->set('categoryId', $categoryA->id);

        $this->assertSame(1, $component->get('transactions')->total());
    }

    public function test_period_filter_narrows_results(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);

        Transaction::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'transaction_date' => '2026-06-15']);
        Transaction::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'transaction_date' => '2026-07-15']);

        $component = Livewire::actingAs($user)->test(TransactionHistory::class)
            ->set('period', '2026-06');

        $this->assertSame(1, $component->get('transactions')->total());
    }

    public function test_pagination_shows_fifteen_per_page(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);

        Transaction::factory()->count(20)->create(['user_id' => $user->id, 'category_id' => $category->id]);

        $component = Livewire::actingAs($user)->test(TransactionHistory::class);

        $this->assertCount(15, $component->get('transactions')->items());

        $component->call('nextPage');

        $this->assertCount(5, $component->get('transactions')->items());
    }

    public function test_reset_filters_clears_all_filters(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);
        Transaction::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'description' => 'Netflix']);

        $component = Livewire::actingAs($user)->test(TransactionHistory::class)
            ->set('search', 'tidak-ada-yang-cocok');

        $this->assertSame(0, $component->get('transactions')->total());

        $component->call('resetFilters')->assertSet('search', '');

        $this->assertSame(1, $component->get('transactions')->total());
    }
}
