<?php

namespace App\Services\Ai\Functions;

use App\Models\ChatMessage;
use App\Models\RecurringTransaction;
use App\Models\User;
use App\Services\Ai\AiFunctionResult;
use App\Services\Ai\Contracts\RequiresConfirmation;
use App\Services\Ai\Functions\Concerns\FormatsRecurringTransactions;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Menonaktifkan aturan transaksi rutin. Seperti ubah/hapus transaksi, perubahan ini wajib dikonfirmasi user.
 * Transaksi yang sudah tercatat tidak ikut terhapus.
 */
class NonaktifkanTransaksiRutinFunction implements RequiresConfirmation
{
    use FormatsRecurringTransactions;

    public function name(): string
    {
        return 'nonaktifkan_transaksi_rutin';
    }

    public function rules(User $user, array $args): array
    {
        return [
            'recurring_transaction_id' => [
                'required',
                'integer',
                Rule::exists('recurring_transactions', 'id')->where('user_id', $user->id)->where('is_active', true),
            ],
        ];
    }

    public function preview(User $user, array $args): array
    {
        $rule = $this->findOwnedRule($user, $args['recurring_transaction_id']);

        return [
            'before' => [
                'category' => ['name' => $rule->description],
                'amount' => $rule->amount,
                'schedule' => $rule->scheduleLabel(),
            ],
            'after' => null,
        ];
    }

    public function execute(User $user, array $args, ChatMessage $triggerMessage): AiFunctionResult
    {
        $rule = $this->findOwnedRule($user, $args['recurring_transaction_id']);

        Gate::forUser($user)->authorize('update', $rule);

        $rule->update(['is_active' => false]);

        return AiFunctionResult::success(
            message: "Transaksi rutin {$rule->description} dinonaktifkan. Transaksi yang sudah tercatat tetap ada.",
            attachment: [
                'type' => 'recurring',
                'action' => 'deactivated',
                'rules' => [$this->recurringCard($rule)],
            ],
            logPayload: ['recurring_transaction_id' => $rule->id],
        );
    }

    private function findOwnedRule(User $user, int $ruleId): RecurringTransaction
    {
        return RecurringTransaction::query()
            ->where('user_id', $user->id)
            ->with('category')
            ->findOrFail($ruleId);
    }
}
