<?php

namespace App\Services\Budget;

use App\Models\Budget;
use App\Models\Transaction;
use App\TransactionType;

class BudgetAlertChecker
{
    /**
     * @return array{category: string, period: string, amount: int, spent: int, percentage: float, threshold: int}|null
     */
    public function check(Transaction $transaction): ?array
    {
        if ($transaction->type !== TransactionType::Pengeluaran) {
            return null;
        }

        $period = $transaction->transaction_date->format('Y-m');

        $budget = Budget::query()
            ->where('user_id', $transaction->user_id)
            ->where('category_id', $transaction->category_id)
            ->where('period', $period)
            ->first();

        if (! $budget) {
            return null;
        }

        $spent = (int) Transaction::query()
            ->where('user_id', $transaction->user_id)
            ->where('category_id', $transaction->category_id)
            ->where('type', TransactionType::Pengeluaran)
            ->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m') = ?", [$period])
            ->sum('amount');

        $percentage = $budget->amount > 0 ? round(($spent / $budget->amount) * 100, 1) : 0.0;

        if ($percentage < $budget->alert_threshold) {
            return null;
        }

        return [
            'category' => $transaction->category->name,
            'period' => $period,
            'amount' => $budget->amount,
            'spent' => $spent,
            'percentage' => $percentage,
            'threshold' => $budget->alert_threshold,
        ];
    }
}
