<?php

namespace Tests\Feature\Recurring;

use App\Livewire\RecurringTransactions;
use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\TransactionSource;
use App\TransactionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecurringTransactionsPageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Category $expenseCategory;

    private Category $incomeCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-09 10:00:00');
        $this->user = User::factory()->create();
        $this->expenseCategory = Category::factory()->create(['user_id' => null, 'name' => 'Tagihan', 'type' => TransactionType::Pengeluaran]);
        $this->incomeCategory = Category::factory()->create(['user_id' => null, 'name' => 'Uang Saku', 'type' => TransactionType::Pemasukan]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createRule(array $attributes = []): RecurringTransaction
    {
        return RecurringTransaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->expenseCategory->id,
            'type' => TransactionType::Pengeluaran,
            'amount' => 800_000,
            'description' => 'Bayar kos',
            'day_of_month' => 1,
            'next_run_date' => '2026-12-01',
            ...$attributes,
        ]);
    }

    public function test_budget_page_shows_recurring_card_with_empty_state(): void
    {
        $this->actingAs($this->user)->get(route('budget'))
            ->assertOk()
            ->assertSeeLivewire(RecurringTransactions::class)
            ->assertSee('Transaksi Rutin')
            ->assertSee('Belum ada transaksi rutin.');
    }

    public function test_rules_are_listed_as_ledger_rows_with_schedule(): void
    {
        $this->createRule();
        $this->createRule([
            'type' => TransactionType::Pemasukan,
            'category_id' => $this->incomeCategory->id,
            'description' => 'Uang bulanan',
            'amount' => 1_500_000,
            'day_of_month' => 25,
            'next_run_date' => '2026-11-25',
        ]);

        Livewire::actingAs($this->user)->test(RecurringTransactions::class)
            ->assertSeeInOrder(['Bayar kos', '-Rp800.000', 'Tiap tanggal 1 · berikutnya 01 Des'])
            ->assertSeeInOrder(['Uang bulanan', '+Rp1.500.000', 'Tiap tanggal 25 · berikutnya 25 Nov']);
    }

    public function test_user_can_create_rule_scheduled_for_next_occurrence(): void
    {
        Livewire::actingAs($this->user)->test(RecurringTransactions::class)
            ->call('openCreateForm')
            ->assertSet('showForm', true)
            ->set('type', 'pengeluaran')
            ->set('description', 'Bayar kos')
            ->set('amount', 800_000)
            ->set('categoryId', $this->expenseCategory->id)
            ->set('dayOfMonth', 1)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false)
            ->assertDispatched('toast')
            ->assertSee('Tiap tanggal 1 · berikutnya 01 Des');

        $rule = RecurringTransaction::query()->sole();
        $this->assertSame($this->user->id, $rule->user_id);
        $this->assertSame(800_000, $rule->amount);
        $this->assertTrue($rule->is_active);
        $this->assertSame('2026-12-01', $rule->next_run_date->toDateString());
        $this->assertSame(0, Transaction::query()->count());
    }

    public function test_creating_rule_for_today_records_todays_transaction_immediately(): void
    {
        Livewire::actingAs($this->user)->test(RecurringTransactions::class)
            ->call('openCreateForm')
            ->set('type', 'pemasukan')
            ->set('description', 'Gaji')
            ->set('amount', 3_500_000)
            ->set('categoryId', $this->incomeCategory->id)
            ->set('dayOfMonth', 9)
            ->call('save')
            ->assertHasNoErrors();

        $rule = RecurringTransaction::query()->sole();
        $transaction = Transaction::query()->sole();
        $this->assertSame($rule->id, $transaction->recurring_transaction_id);
        $this->assertSame('2026-11-09', $transaction->transaction_date->toDateString());
        $this->assertSame(TransactionSource::Rutin, $transaction->source);
        $this->assertSame('2026-12-09', $rule->next_run_date->toDateString());
    }

    public function test_validation_rejects_category_of_the_wrong_type_and_invalid_day(): void
    {
        Livewire::actingAs($this->user)->test(RecurringTransactions::class)
            ->call('openCreateForm')
            ->set('type', 'pengeluaran')
            ->set('description', '')
            ->set('amount', 0)
            ->set('categoryId', $this->incomeCategory->id)
            ->set('dayOfMonth', 32)
            ->call('save')
            ->assertHasErrors(['description', 'amount', 'categoryId', 'dayOfMonth']);

        $this->assertSame(0, RecurringTransaction::query()->count());
    }

    public function test_changing_type_resets_selected_category(): void
    {
        Livewire::actingAs($this->user)->test(RecurringTransactions::class)
            ->call('openCreateForm')
            ->set('categoryId', $this->expenseCategory->id)
            ->set('type', 'pemasukan')
            ->assertSet('categoryId', null)
            ->assertSee('Uang Saku');
    }

    public function test_user_can_edit_rule_and_new_day_is_rescheduled(): void
    {
        $rule = $this->createRule();

        Livewire::actingAs($this->user)->test(RecurringTransactions::class)
            ->call('openEditForm', $rule->id)
            ->assertSet('editingRuleId', $rule->id)
            ->assertSet('description', 'Bayar kos')
            ->assertSet('dayOfMonth', 1)
            ->set('amount', 850_000)
            ->set('dayOfMonth', 15)
            ->call('save')
            ->assertHasNoErrors();

        $rule->refresh();
        $this->assertSame(850_000, $rule->amount);
        $this->assertSame(15, $rule->day_of_month);
        $this->assertSame('2026-11-15', $rule->next_run_date->toDateString());
    }

    public function test_editing_day_does_not_record_a_month_twice(): void
    {
        $rule = $this->createRule(['next_run_date' => '2026-11-01']);
        $this->artisan('kasku:proses-transaksi-rutin');

        Livewire::actingAs($this->user)->test(RecurringTransactions::class)
            ->call('openEditForm', $rule->id)
            ->set('dayOfMonth', 20)
            ->call('save');

        $this->assertSame('2026-12-20', $rule->refresh()->next_run_date->toDateString());
    }

    public function test_user_can_deactivate_and_reactivate_rule(): void
    {
        $rule = $this->createRule(['next_run_date' => '2026-08-01']);

        $component = Livewire::actingAs($this->user)->test(RecurringTransactions::class)
            ->call('toggleActive', $rule->id)
            ->assertSee('Tiap tanggal 1 · nonaktif');

        $this->assertFalse($rule->refresh()->is_active);

        $component->call('toggleActive', $rule->id);

        $rule->refresh();
        $this->assertTrue($rule->is_active);
        $this->assertSame('2026-12-01', $rule->next_run_date->toDateString());
        $this->assertSame(0, Transaction::query()->count(), 'Mengaktifkan lagi tidak boleh mengejar bulan saat aturan nonaktif.');
    }

    public function test_user_can_delete_rule_and_keeps_recorded_transactions(): void
    {
        $rule = $this->createRule(['next_run_date' => '2026-11-01']);
        $this->artisan('kasku:proses-transaksi-rutin');
        $transaction = Transaction::query()->sole();

        Livewire::actingAs($this->user)->test(RecurringTransactions::class)
            ->call('delete', $rule->id)
            ->assertDispatched('toast')
            ->assertSee('Belum ada transaksi rutin.');

        $this->assertModelMissing($rule);
        $this->assertNull($transaction->refresh()->recurring_transaction_id);
    }

    public function test_user_does_not_see_other_users_rules(): void
    {
        $otherUser = User::factory()->create();
        $this->createRule(['user_id' => $otherUser->id, 'description' => 'Kos milik orang lain']);

        Livewire::actingAs($this->user)->test(RecurringTransactions::class)
            ->assertDontSee('Kos milik orang lain')
            ->assertSee('Belum ada transaksi rutin.');
    }

    public function test_user_cannot_edit_other_users_rule(): void
    {
        $otherRule = $this->createRule(['user_id' => User::factory()->create()->id]);

        Livewire::actingAs($this->user)->test(RecurringTransactions::class)
            ->call('openEditForm', $otherRule->id)
            ->assertNotFound()
            ->assertSet('editingRuleId', null)
            ->assertSet('showForm', false);
    }

    public function test_user_cannot_save_over_other_users_rule_by_tampering_with_editing_id(): void
    {
        $otherRule = $this->createRule(['user_id' => User::factory()->create()->id]);

        Livewire::actingAs($this->user)->test(RecurringTransactions::class)
            ->set('editingRuleId', $otherRule->id)
            ->set('description', 'Diretas')
            ->set('amount', 1)
            ->set('categoryId', $this->expenseCategory->id)
            ->call('save')
            ->assertNotFound();

        $otherRule->refresh();
        $this->assertSame('Bayar kos', $otherRule->description);
        $this->assertSame(800_000, $otherRule->amount);
        $this->assertSame(1, RecurringTransaction::query()->count());
    }

    public function test_user_cannot_toggle_or_delete_other_users_rule(): void
    {
        $otherRule = $this->createRule(['user_id' => User::factory()->create()->id]);

        Livewire::actingAs($this->user)->test(RecurringTransactions::class)
            ->call('toggleActive', $otherRule->id)
            ->assertNotFound();
        Livewire::actingAs($this->user)->test(RecurringTransactions::class)
            ->call('delete', $otherRule->id)
            ->assertNotFound();

        $this->assertModelExists($otherRule);
        $this->assertTrue($otherRule->refresh()->is_active);
    }
}
