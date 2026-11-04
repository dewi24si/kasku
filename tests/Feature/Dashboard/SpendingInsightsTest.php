<?php

namespace Tests\Feature\Dashboard;

use App\Livewire\Dashboard;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Insight\SpendingInsights;
use App\TransactionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class SpendingInsightsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Category $food;

    private Category $transport;

    private Category $salary;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->food = Category::factory()->create(['user_id' => $this->user->id, 'name' => 'Makan & Minum', 'type' => TransactionType::Pengeluaran]);
        $this->transport = Category::factory()->create(['user_id' => $this->user->id, 'name' => 'Transport', 'type' => TransactionType::Pengeluaran]);
        $this->salary = Category::factory()->create(['user_id' => $this->user->id, 'name' => 'Gaji', 'type' => TransactionType::Pemasukan]);
    }

    /**
     * @return list<array{tone: string, icon: string, message: string}>
     */
    private function insightsOn(string $date, int $limit = 10): array
    {
        return app(SpendingInsights::class)->forUser($this->user, Carbon::parse($date), $limit);
    }

    private function record(Category $category, int $amount, string $date, ?User $user = null): void
    {
        Transaction::factory()->create([
            'user_id' => ($user ?? $this->user)->id,
            'category_id' => $category->id,
            'type' => $category->type,
            'amount' => $amount,
            'transaction_date' => $date,
        ]);
    }

    /**
     * @param  list<array{tone: string, icon: string, message: string}>  $insights
     */
    private function messages(array $insights): string
    {
        return implode("\n", array_column($insights, 'message'));
    }

    public function test_returns_no_insights_without_data(): void
    {
        $this->assertSame([], $this->insightsOn('2026-11-20'));
    }

    public function test_detects_weekly_category_increase(): void
    {
        $this->record($this->food, 100_000, '2026-11-08');
        $this->record($this->food, 140_000, '2026-11-18');

        $messages = $this->messages($this->insightsOn('2026-11-20'));

        $this->assertStringContainsString('Pengeluaran Makan & Minum 7 hari terakhir naik 40%', $messages);
    }

    public function test_ignores_small_weekly_increase(): void
    {
        $this->record($this->food, 100_000, '2026-11-08');
        $this->record($this->food, 110_000, '2026-11-18');

        $this->assertStringNotContainsString('naik', $this->messages($this->insightsOn('2026-11-20')));
    }

    public function test_praises_weekly_total_decrease(): void
    {
        $this->record($this->food, 200_000, '2026-11-08');
        $this->record($this->food, 100_000, '2026-11-18');

        $insights = $this->insightsOn('2026-11-20');

        $this->assertStringContainsString('turun 50%', $this->messages($insights));
        $this->assertContains('success', array_column($insights, 'tone'));
    }

    public function test_warns_when_projected_spending_exceeds_income(): void
    {
        $this->record($this->salary, 1_000_000, '2026-11-01');
        $this->record($this->food, 600_000, '2026-11-05');

        $messages = $this->messages($this->insightsOn('2026-11-10'));

        $this->assertStringContainsString('diperkirakan Rp1.800.000, melebihi pemasukanmu Rp1.000.000', $messages);
    }

    public function test_shows_daily_average_when_projection_is_safe(): void
    {
        $this->record($this->salary, 5_000_000, '2026-11-01');
        $this->record($this->food, 100_000, '2026-11-05');

        $messages = $this->messages($this->insightsOn('2026-11-10'));

        $this->assertStringContainsString('Rata-rata pengeluaranmu Rp10.000/hari', $messages);
    }

    public function test_skips_projection_early_in_month(): void
    {
        $this->record($this->food, 100_000, '2026-11-02');

        $this->assertStringNotContainsString('Rata-rata', $this->messages($this->insightsOn('2026-11-03')));
    }

    public function test_reports_top_category_share(): void
    {
        $this->record($this->food, 300_000, '2026-11-02');
        $this->record($this->transport, 100_000, '2026-11-03');

        $messages = $this->messages($this->insightsOn('2026-11-04'));

        $this->assertStringContainsString('Pengeluaran terbesar bulan ini: Makan & Minum (Rp300.000, 75% dari total)', $messages);
    }

    public function test_warns_about_budget_over_threshold(): void
    {
        Budget::factory()->create([
            'user_id' => $this->user->id, 'category_id' => $this->food->id,
            'amount' => 100_000, 'period' => '2026-11', 'alert_threshold' => 80,
        ]);
        $this->record($this->food, 120_000, '2026-11-02');

        $insights = $this->insightsOn('2026-11-04');

        $this->assertSame('warning', $insights[0]['tone']);
        $this->assertStringContainsString('Budget Makan & Minum bulan ini sudah terlampaui (120%)', $insights[0]['message']);
    }

    public function test_ignores_other_users_data(): void
    {
        $otherUser = User::factory()->create();
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id, 'type' => TransactionType::Pengeluaran]);

        $this->record($otherCategory, 100_000, '2026-11-08', $otherUser);
        $this->record($otherCategory, 500_000, '2026-11-18', $otherUser);

        $this->assertSame([], $this->insightsOn('2026-11-20'));
    }

    public function test_limits_number_of_insights_with_warnings_first(): void
    {
        Budget::factory()->create([
            'user_id' => $this->user->id, 'category_id' => $this->food->id,
            'amount' => 100_000, 'period' => '2026-11', 'alert_threshold' => 80,
        ]);
        $this->record($this->salary, 5_000_000, '2026-11-01');
        $this->record($this->food, 300_000, '2026-11-08');
        $this->record($this->transport, 100_000, '2026-11-09');

        $insights = $this->insightsOn('2026-11-10', 3);

        $this->assertCount(3, $insights);
        $this->assertSame('warning', $insights[0]['tone']);
    }

    public function test_dashboard_shows_insights_only_for_current_month(): void
    {
        $this->travelTo(Carbon::parse('2026-11-04'));
        $this->record($this->food, 300_000, '2026-11-02');
        $this->record($this->transport, 100_000, '2026-11-03');

        Livewire::actingAs($this->user)->test(Dashboard::class)
            ->assertSee('Insight Keuanganmu')
            ->assertSee('Pengeluaran terbesar bulan ini')
            ->call('previousPeriod')
            ->assertDontSee('Insight Keuanganmu');
    }

    public function test_dashboard_shows_friendly_empty_insight_state(): void
    {
        Livewire::actingAs($this->user)->test(Dashboard::class)
            ->assertSee('Belum cukup data untuk insight');
    }
}
