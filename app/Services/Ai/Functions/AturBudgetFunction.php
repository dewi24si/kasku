<?php

namespace App\Services\Ai\Functions;

use App\Models\Budget;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\Ai\AiFunctionResult;
use App\Services\Ai\Contracts\AiFunction;
use App\Services\Ai\Functions\Concerns\FormatsTransactions;
use App\Services\Ai\Functions\Concerns\ScopesToUser;
use App\TransactionType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class AturBudgetFunction implements AiFunction
{
    use FormatsTransactions, ScopesToUser;

    public function name(): string
    {
        return 'atur_budget';
    }

    public function rules(User $user, array $args): array
    {
        return [
            'category_id' => ['required', ...$this->categoryRule($user, TransactionType::Pengeluaran->value)],
            'amount' => ['required', 'integer', 'min:1'],
            'period' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'alert_threshold' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function execute(User $user, array $args, ChatMessage $triggerMessage): AiFunctionResult
    {
        Gate::forUser($user)->authorize('create', Budget::class);

        $budget = Budget::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'category_id' => $args['category_id'],
                'period' => $args['period'],
            ],
            [
                'amount' => $args['amount'],
                'alert_threshold' => $args['alert_threshold'] ?? 80,
            ],
        );

        $budget->load('category');
        $periodLabel = Carbon::createFromFormat('Y-m-d', "{$args['period']}-01")->translatedFormat('F Y');

        $message = "Budget {$budget->category->name} untuk {$periodLabel} diset ".$this->formatRupiah($budget->amount)." (peringatan di {$budget->alert_threshold}%).";

        return AiFunctionResult::success(
            message: $message,
            attachment: [
                'type' => 'budget',
                'action' => 'set',
                'budgets' => [[
                    'category' => $budget->category->name,
                    'period' => $budget->period,
                    'amount' => $budget->amount,
                    'spent' => null,
                    'percentage' => null,
                    'threshold' => $budget->alert_threshold,
                    'status' => 'set',
                ]],
            ],
            logPayload: ['budget_id' => $budget->id],
        );
    }
}
