<?php

namespace App\Services\Insight;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\TransactionType;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Rule-based spending insights computed from the user's own transactions.
 *
 * Kept deterministic (no AI call) so the dashboard stays fast, free, and works offline.
 */
class SpendingInsights
{
    /**
     * Minimum relative change (percent) before a week-over-week change is worth mentioning.
     */
    public const WEEKLY_CHANGE_THRESHOLD = 30;

    /**
     * Minimum spending (rupiah) in the current window for a category change to be mentioned.
     */
    public const WEEKLY_MINIMUM_AMOUNT = 20_000;

    /**
     * Minimum elapsed days in the month before projecting month-end spending.
     */
    public const PROJECTION_MINIMUM_DAYS = 5;

    /**
     * @return list<array{tone: 'warning'|'success'|'info', icon: string, message: string}>
     */
    public function forUser(User $user, CarbonInterface $today, int $limit = 3): array
    {
        $insights = array_filter([
            $this->budgetInsight($user, $today),
            $this->weeklyCategoryInsight($user, $today),
            $this->projectionInsight($user, $today),
            $this->topCategoryInsight($user, $today),
        ]);

        $toneOrder = ['warning' => 0, 'success' => 1, 'info' => 2];
        usort($insights, fn (array $a, array $b): int => $toneOrder[$a['tone']] <=> $toneOrder[$b['tone']]);

        return array_slice(array_values($insights), 0, $limit);
    }

    /**
     * @return array{tone: 'warning', icon: string, message: string}|null
     */
    private function budgetInsight(User $user, CarbonInterface $today): ?array
    {
        $period = $today->format('Y-m');

        $budgets = Budget::query()
            ->where('user_id', $user->id)
            ->where('period', $period)
            ->where('amount', '>', 0)
            ->with('category')
            ->get();

        if ($budgets->isEmpty()) {
            return null;
        }

        $spentPerCategory = $this->expenseTotalsByCategory(
            $user,
            $today->copy()->startOfMonth(),
            $today->copy()->endOfMonth(),
        );

        $mostCritical = $budgets
            ->map(fn (Budget $budget): array => [
                'budget' => $budget,
                'percentage' => round(($spentPerCategory->get($budget->category_id, 0) / $budget->amount) * 100),
            ])
            ->filter(fn (array $row): bool => $row['percentage'] >= $row['budget']->alert_threshold)
            ->sortByDesc('percentage')
            ->first();

        if (! $mostCritical) {
            return null;
        }

        $name = $mostCritical['budget']->category->name;
        $percentage = (int) $mostCritical['percentage'];

        return [
            'tone' => 'warning',
            'icon' => 'triangle-alert',
            'message' => $percentage >= 100
                ? "Budget {$name} bulan ini sudah terlampaui ({$percentage}%)."
                : "Budget {$name} bulan ini sudah terpakai {$percentage}%. Hati-hati, ya!",
        ];
    }

    /**
     * Compare the last 7 days with the 7 days before, per expense category.
     *
     * @return array{tone: 'warning'|'success', icon: string, message: string}|null
     */
    private function weeklyCategoryInsight(User $user, CarbonInterface $today): ?array
    {
        $currentStart = $today->copy()->subDays(6)->startOfDay();
        $previousStart = $today->copy()->subDays(13)->startOfDay();
        $previousEnd = $today->copy()->subDays(7)->endOfDay();

        $current = $this->expenseTotalsByCategory($user, $currentStart, $today->copy()->endOfDay());
        $previous = $this->expenseTotalsByCategory($user, $previousStart, $previousEnd);

        $biggestIncrease = $current
            ->map(fn (int $total, int $categoryId): array => [
                'category_id' => $categoryId,
                'current' => $total,
                'previous' => $previous->get($categoryId, 0),
            ])
            ->filter(fn (array $row): bool => $row['previous'] > 0 && $row['current'] >= self::WEEKLY_MINIMUM_AMOUNT)
            ->map(fn (array $row): array => [...$row, 'change' => (int) round((($row['current'] - $row['previous']) / $row['previous']) * 100)])
            ->filter(fn (array $row): bool => $row['change'] >= self::WEEKLY_CHANGE_THRESHOLD)
            ->sortByDesc('change')
            ->first();

        if ($biggestIncrease) {
            $name = Category::query()->find($biggestIncrease['category_id'])?->name ?? 'Lainnya';

            return [
                'tone' => 'warning',
                'icon' => 'trending-up',
                'message' => "Pengeluaran {$name} 7 hari terakhir naik {$biggestIncrease['change']}% dibanding 7 hari sebelumnya.",
            ];
        }

        $currentTotal = $current->sum();
        $previousTotal = $previous->sum();

        if ($previousTotal > 0) {
            $change = (int) round((($currentTotal - $previousTotal) / $previousTotal) * 100);

            if ($change <= -self::WEEKLY_CHANGE_THRESHOLD) {
                return [
                    'tone' => 'success',
                    'icon' => 'trending-down',
                    'message' => 'Mantap! Total pengeluaran 7 hari terakhir turun '.abs($change).'% dibanding 7 hari sebelumnya.',
                ];
            }
        }

        return null;
    }

    /**
     * Project month-end spending from the average daily spending so far.
     *
     * @return array{tone: 'warning'|'info', icon: string, message: string}|null
     */
    private function projectionInsight(User $user, CarbonInterface $today): ?array
    {
        $elapsedDays = $today->day;

        if ($elapsedDays < self::PROJECTION_MINIMUM_DAYS) {
            return null;
        }

        $monthStart = $today->copy()->startOfMonth();
        $totals = $this->totalsByType($user, $monthStart, $today->copy()->endOfDay());

        if ($totals['pengeluaran'] === 0) {
            return null;
        }

        $projected = (int) round($totals['pengeluaran'] / $elapsedDays * $today->daysInMonth);

        if ($totals['pemasukan'] > 0 && $projected > $totals['pemasukan']) {
            return [
                'tone' => 'warning',
                'icon' => 'calendar-clock',
                'message' => 'Dengan pola sekarang, pengeluaran bulan ini diperkirakan '.$this->formatRupiah($projected)
                    .', melebihi pemasukanmu '.$this->formatRupiah($totals['pemasukan']).'.',
            ];
        }

        return [
            'tone' => 'info',
            'icon' => 'calendar-clock',
            'message' => 'Rata-rata pengeluaranmu '.$this->formatRupiah((int) round($totals['pengeluaran'] / $elapsedDays))
                .'/hari. Perkiraan total akhir bulan: '.$this->formatRupiah($projected).'.',
        ];
    }

    /**
     * @return array{tone: 'info', icon: string, message: string}|null
     */
    private function topCategoryInsight(User $user, CarbonInterface $today): ?array
    {
        $totals = $this->expenseTotalsByCategory($user, $today->copy()->startOfMonth(), $today->copy()->endOfDay());
        $grandTotal = $totals->sum();

        if ($grandTotal === 0 || $totals->count() < 2) {
            return null;
        }

        $topCategoryId = $totals->sortDesc()->keys()->first();
        $topTotal = $totals->get($topCategoryId);
        $name = Category::query()->find($topCategoryId)?->name ?? 'Lainnya';
        $share = (int) round($topTotal / $grandTotal * 100);

        return [
            'tone' => 'info',
            'icon' => 'chart-pie',
            'message' => "Pengeluaran terbesar bulan ini: {$name} ({$this->formatRupiah($topTotal)}, {$share}% dari total).",
        ];
    }

    /**
     * @return Collection<int, int> Expense totals keyed by category id.
     */
    private function expenseTotalsByCategory(User $user, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return Transaction::query()
            ->where('user_id', $user->id)
            ->where('type', TransactionType::Pengeluaran)
            ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('category_id')
            ->selectRaw('category_id, SUM(amount) as total')
            ->pluck('total', 'category_id')
            ->map(fn (mixed $total): int => (int) $total);
    }

    /**
     * @return array{pemasukan: int, pengeluaran: int}
     */
    private function totalsByType(User $user, CarbonInterface $from, CarbonInterface $to): array
    {
        $totals = Transaction::query()
            ->where('user_id', $user->id)
            ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('type')
            ->selectRaw('type, SUM(amount) as total')
            ->pluck('total', 'type');

        return [
            'pemasukan' => (int) ($totals[TransactionType::Pemasukan->value] ?? 0),
            'pengeluaran' => (int) ($totals[TransactionType::Pengeluaran->value] ?? 0),
        ];
    }

    private function formatRupiah(int $amount): string
    {
        return 'Rp'.number_format($amount, 0, ',', '.');
    }
}
