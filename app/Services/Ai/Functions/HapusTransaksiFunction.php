<?php

namespace App\Services\Ai\Functions;

use App\Models\ChatMessage;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Ai\AiFunctionResult;
use App\Services\Ai\Contracts\RequiresConfirmation;
use App\Services\Ai\Functions\Concerns\FormatsTransactions;
use App\Services\Ai\Functions\Concerns\ScopesToUser;
use Illuminate\Support\Facades\Gate;

class HapusTransaksiFunction implements RequiresConfirmation
{
    use FormatsTransactions, ScopesToUser;

    public function name(): string
    {
        return 'hapus_transaksi';
    }

    public function rules(User $user, array $args): array
    {
        return [
            'transaction_id' => ['required', ...$this->ownedTransactionRule($user)],
        ];
    }

    public function preview(User $user, array $args): array
    {
        $transaction = Transaction::query()
            ->where('user_id', $user->id)
            ->with('category')
            ->findOrFail($args['transaction_id']);

        return ['before' => $this->transactionCard($transaction), 'after' => null];
    }

    public function execute(User $user, array $args, ChatMessage $triggerMessage): AiFunctionResult
    {
        $transaction = Transaction::query()
            ->where('user_id', $user->id)
            ->with('category')
            ->findOrFail($args['transaction_id']);

        Gate::forUser($user)->authorize('delete', $transaction);

        $card = $this->transactionCard($transaction);

        $transaction->delete();

        $message = '🗑️ Transaksi berhasil dihapus: '.$card['category']['name'].', '.$this->formatRupiah($card['amount']).' pada '.$transaction->transaction_date->translatedFormat('d M Y').'.';

        return AiFunctionResult::success(
            message: $message,
            attachment: [
                'type' => 'transaction',
                'action' => 'deleted',
                'transactions' => [$card],
                'budget_warning' => null,
            ],
            logPayload: ['transaction_id' => $transaction->id],
        );
    }
}
