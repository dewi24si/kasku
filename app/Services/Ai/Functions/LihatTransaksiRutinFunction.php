<?php

namespace App\Services\Ai\Functions;

use App\Models\ChatMessage;
use App\Models\RecurringTransaction;
use App\Models\User;
use App\Services\Ai\AiFunctionResult;
use App\Services\Ai\Contracts\AiFunction;
use App\Services\Ai\Functions\Concerns\FormatsRecurringTransactions;
use Illuminate\Support\Facades\Gate;

class LihatTransaksiRutinFunction implements AiFunction
{
    use FormatsRecurringTransactions;

    public function name(): string
    {
        return 'lihat_transaksi_rutin';
    }

    public function rules(User $user, array $args): array
    {
        return [
            'include_inactive' => ['sometimes', 'boolean'],
        ];
    }

    public function execute(User $user, array $args, ChatMessage $triggerMessage): AiFunctionResult
    {
        Gate::forUser($user)->authorize('viewAny', RecurringTransaction::class);

        $includeInactive = (bool) ($args['include_inactive'] ?? false);

        $rules = RecurringTransaction::query()
            ->where('user_id', $user->id)
            ->when(! $includeInactive, fn ($query) => $query->where('is_active', true))
            ->with('category')
            ->orderByDesc('is_active')
            ->orderBy('day_of_month')
            ->orderBy('id')
            ->get();

        $message = $rules->isEmpty()
            ? 'Belum ada transaksi rutin'.($includeInactive ? '.' : ' yang aktif.')
            : "Ada {$rules->count()} transaksi rutin".($includeInactive ? '.' : ' yang aktif.');

        return AiFunctionResult::success(
            message: $message,
            attachment: [
                'type' => 'recurring',
                'action' => 'list',
                'rules' => $rules->map(fn (RecurringTransaction $rule): array => $this->recurringCard($rule))->all(),
            ],
            logPayload: ['count' => $rules->count(), 'include_inactive' => $includeInactive],
        );
    }
}
