<?php

namespace Tests\Feature\Recurring;

use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\TransactionSource;
use App\TransactionType;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProsesTransaksiRutinCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createRule(array $attributes = []): RecurringTransaction
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);

        return RecurringTransaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'type' => TransactionType::Pengeluaran,
            'amount' => 800_000,
            'description' => 'Bayar kos',
            ...$attributes,
        ]);
    }

    /**
     * @return list<string>
     */
    private function recordedDates(RecurringTransaction $rule): array
    {
        return Transaction::query()
            ->where('recurring_transaction_id', $rule->id)
            ->orderBy('transaction_date')
            ->get()
            ->map(fn (Transaction $transaction): string => $transaction->transaction_date->toDateString())
            ->all();
    }

    public function test_command_records_due_transaction_and_moves_next_run_date_to_next_month(): void
    {
        $this->travelTo('2026-11-09 00:15:00');
        $rule = $this->createRule(['day_of_month' => 1, 'next_run_date' => '2026-11-01']);

        $this->artisan('kasku:proses-transaksi-rutin')
            ->expectsOutputToContain('1 transaksi rutin dicatat dari 1 aturan')
            ->assertSuccessful();

        $transaction = Transaction::query()->where('recurring_transaction_id', $rule->id)->sole();
        $this->assertSame($rule->user_id, $transaction->user_id);
        $this->assertSame($rule->category_id, $transaction->category_id);
        $this->assertSame(TransactionType::Pengeluaran, $transaction->type);
        $this->assertSame(800_000, $transaction->amount);
        $this->assertSame('Bayar kos', $transaction->description);
        $this->assertSame('2026-11-01', $transaction->transaction_date->toDateString());
        $this->assertSame(TransactionSource::Rutin, $transaction->source);
        $this->assertSame('2026-12-01', $rule->refresh()->next_run_date->toDateString());
    }

    public function test_rule_due_exactly_today_is_recorded(): void
    {
        $this->travelTo('2026-11-25 00:15:00');
        $rule = $this->createRule(['day_of_month' => 25, 'next_run_date' => '2026-11-25']);

        $this->artisan('kasku:proses-transaksi-rutin')->assertSuccessful();

        $this->assertSame(['2026-11-25'], $this->recordedDates($rule));
        $this->assertSame('2026-12-25', $rule->refresh()->next_run_date->toDateString());
    }

    public function test_running_twice_on_the_same_day_does_not_duplicate(): void
    {
        $this->travelTo('2026-11-09 00:15:00');
        $rule = $this->createRule(['day_of_month' => 1, 'next_run_date' => '2026-11-01']);

        $this->artisan('kasku:proses-transaksi-rutin')->assertSuccessful();
        $this->artisan('kasku:proses-transaksi-rutin')
            ->expectsOutputToContain('0 transaksi rutin dicatat')
            ->assertSuccessful();

        $this->assertSame(['2026-11-01'], $this->recordedDates($rule));
        $this->assertSame('2026-12-01', $rule->refresh()->next_run_date->toDateString());
    }

    public function test_catches_up_every_missed_month_when_cron_was_down(): void
    {
        $this->travelTo('2026-11-09 00:15:00');
        $rule = $this->createRule(['day_of_month' => 5, 'next_run_date' => '2026-08-05']);

        $this->artisan('kasku:proses-transaksi-rutin')
            ->expectsOutputToContain('4 transaksi rutin dicatat')
            ->assertSuccessful();

        $this->assertSame(['2026-08-05', '2026-09-05', '2026-10-05', '2026-11-05'], $this->recordedDates($rule));
        $this->assertSame('2026-12-05', $rule->refresh()->next_run_date->toDateString());
    }

    public function test_day_31_falls_on_last_day_of_february_and_returns_to_31_afterwards(): void
    {
        $this->travelTo('2027-03-31 00:15:00');
        $rule = $this->createRule(['day_of_month' => 31, 'next_run_date' => '2027-01-31']);

        $this->artisan('kasku:proses-transaksi-rutin')->assertSuccessful();

        $this->assertSame(['2027-01-31', '2027-02-28', '2027-03-31'], $this->recordedDates($rule));
        $rule->refresh();
        $this->assertSame(31, $rule->day_of_month);
        $this->assertSame('2027-04-30', $rule->next_run_date->toDateString());
    }

    public function test_day_29_uses_february_29_in_a_leap_year(): void
    {
        $this->travelTo('2028-02-29 00:15:00');
        $rule = $this->createRule(['day_of_month' => 29, 'next_run_date' => '2028-02-29']);

        $this->artisan('kasku:proses-transaksi-rutin')->assertSuccessful();

        $this->assertSame(['2028-02-29'], $this->recordedDates($rule));
        $this->assertSame('2028-03-29', $rule->refresh()->next_run_date->toDateString());
    }

    public function test_inactive_rule_is_skipped(): void
    {
        $this->travelTo('2026-11-09 00:15:00');
        $rule = $this->createRule(['day_of_month' => 1, 'next_run_date' => '2026-11-01', 'is_active' => false]);

        $this->artisan('kasku:proses-transaksi-rutin')
            ->expectsOutputToContain('0 transaksi rutin dicatat dari 0 aturan')
            ->assertSuccessful();

        $this->assertSame([], $this->recordedDates($rule));
        $this->assertSame('2026-11-01', $rule->refresh()->next_run_date->toDateString());
    }

    public function test_rule_not_yet_due_is_left_alone(): void
    {
        $this->travelTo('2026-11-09 00:15:00');
        $rule = $this->createRule(['day_of_month' => 10, 'next_run_date' => '2026-11-10']);

        $this->artisan('kasku:proses-transaksi-rutin')->assertSuccessful();

        $this->assertSame([], $this->recordedDates($rule));
        $this->assertSame('2026-11-10', $rule->refresh()->next_run_date->toDateString());
    }

    public function test_transaction_deleted_by_user_is_not_recreated_if_schedule_is_rewound(): void
    {
        $this->travelTo('2026-11-09 00:15:00');
        $rule = $this->createRule(['day_of_month' => 1, 'next_run_date' => '2026-11-01']);

        $this->artisan('kasku:proses-transaksi-rutin')->assertSuccessful();
        Transaction::query()->where('recurring_transaction_id', $rule->id)->sole()->delete();
        $rule->update(['next_run_date' => '2026-11-01']);

        $this->artisan('kasku:proses-transaksi-rutin')->assertSuccessful();

        $this->assertSame([], $this->recordedDates($rule));
        $this->assertSame(1, Transaction::withTrashed()->where('recurring_transaction_id', $rule->id)->count());
        $this->assertSame('2026-12-01', $rule->refresh()->next_run_date->toDateString());
    }

    public function test_database_rejects_two_transactions_for_the_same_rule_and_date(): void
    {
        $rule = $this->createRule();
        $attributes = [
            'user_id' => $rule->user_id,
            'category_id' => $rule->category_id,
            'type' => TransactionType::Pengeluaran,
            'transaction_date' => '2026-11-01',
            'recurring_transaction_id' => $rule->id,
            'source' => TransactionSource::Rutin,
        ];

        Transaction::factory()->create($attributes);

        $this->expectException(UniqueConstraintViolationException::class);
        Transaction::factory()->create($attributes);
    }

    public function test_command_is_scheduled_daily_at_0015_jakarta_time(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn (Event $event): bool => str_contains((string) $event->command, 'kasku:proses-transaksi-rutin'));

        $this->assertNotNull($event);
        $this->assertSame('15 0 * * *', $event->expression);
        $this->assertSame('Asia/Jakarta', $event->timezone);
    }
}
