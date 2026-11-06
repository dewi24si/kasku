<?php

namespace App\Services\Ai\Functions;

use App\Models\Budget;
use App\Models\ChatMessage;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Ai\AiFunctionResult;
use App\Services\Ai\Contracts\AiFunction;
use App\Services\Ai\Functions\Concerns\ScopesToUser;
use App\TransactionType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class CekBudgetFunction implements AiFunction
{
    use ScopesToUser;

    public function name(): string
    {
        return 'cek_budget';
    }

    public function rules(User $user, array $args): array
    {
        return [
            'period' => ['sometimes', 'nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'category_id' => ['sometimes', 'nullable', ...$this->categoryRule($user, TransactionType::Pengeluaran->value)],
        ];
    }

    public function execute(User $user, array $args, ChatMessage $triggerMessage): AiFunctionResult
    {
        Gate::forUser($user)->authorize('viewAny', Budget::class);

        $period = $args['period'] ?? Carbon::today()->format('Y-m');

        $budgets = Budget::query()
            ->where('user_id', $user->id)
            ->where('period', $period)
            ->when(! empty($args['category_id']), fn ($query) => $query->where('category_id', $args['category_id']))
            ->with('category')
            ->get();

        if ($budgets->isEmpty()) {
            return AiFunctionResult::success(
                message: 'Belum ada budget yang diset untuk periode ini.',
                attachment: ['type' => 'budget', 'action' => 'check', 'budgets' => []],
                logPayload: ['period' => $period, 'count' => 0],
            );
        }

        $rows = $budgets->map(function (Budget $budget) use ($period) {
            $spent = (int) Transaction::query()
                ->where('user_id', $budget->user_id)
                ->where('category_id', $budget->category_id)
                ->where('type', TransactionType::Pengeluaran)
                ->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m') = ?", [$period])
                ->sum('amount');

            $percentage = $budget->amount > 0 ? round(($spent / $budget->amount) * 100, 1) : 0.0;

            return [
                'category' => $budget->category->name,
                'period' => $budget->period,
                'amount' => $budget->amount,
                'spent' => $spent,
                'percentage' => $percentage,
                'threshold' => $budget->alert_threshold,
                'status' => $percentage >= $budget->alert_threshold ? 'warning' : 'ok',
            ];
        });

        $warningCount = $rows->where('status', 'warning')->count();
        $message = $warningCount > 0
            ? "Peringatan: {$warningCount} dari {$rows->count()} budget sudah melewati batas peringatan."
            : "Semua budget ({$rows->count()}) masih aman.";

        return AiFunctionResult::success(
            message: $message,
            attachment: [
                'type' => 'budget',
                'action' => 'check',
                'budgets' => $rows->all(),
            ],
            logPayload: ['period' => $period, 'count' => $rows->count(), 'warnings' => $warningCount],
        );
    }
}
