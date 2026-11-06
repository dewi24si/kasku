<?php

namespace App\Services\Ai\Functions;

use App\Models\Category;
use App\Models\ChatMessage;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Ai\AiFunctionResult;
use App\Services\Ai\Contracts\RequiresConfirmation;
use App\Services\Ai\Functions\Concerns\FormatsTransactions;
use App\Services\Ai\Functions\Concerns\RejectsFutureDates;
use App\Services\Ai\Functions\Concerns\ScopesToUser;
use App\Services\Budget\BudgetAlertChecker;
use App\TransactionType;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UbahTransaksiFunction implements RequiresConfirmation
{
    use FormatsTransactions, RejectsFutureDates, ScopesToUser;

    public function __construct(private BudgetAlertChecker $budgetAlertChecker) {}

    public function name(): string
    {
        return 'ubah_transaksi';
    }

    public function rules(User $user, array $args): array
    {
        return [
            'transaction_id' => ['required', ...$this->ownedTransactionRule($user)],
            'changes' => ['required', 'array', 'min:1'],
            'changes.type' => ['sometimes', Rule::in(['pemasukan', 'pengeluaran'])],
            'changes.category_id' => ['sometimes', ...$this->categoryRule($user)],
            'changes.amount' => ['sometimes', 'integer', 'min:1'],
            'changes.description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'changes.transaction_date' => ['bail', 'sometimes', 'date_format:Y-m-d', $this->notInFutureRule()],
        ];
    }

    public function preview(User $user, array $args): array
    {
        $transaction = Transaction::query()
            ->where('user_id', $user->id)
            ->with('category')
            ->findOrFail($args['transaction_id']);

        $before = $this->transactionCard($transaction);
        $after = array_merge($before, $args['changes'] ?? []);

        if (isset($args['changes']['category_id'])) {
            $after['category'] = $this->categoryCard((int) $args['changes']['category_id']);
        }

        return ['before' => $before, 'after' => $after];
    }

    public function execute(User $user, array $args, ChatMessage $triggerMessage): AiFunctionResult
    {
        $transaction = Transaction::query()
            ->where('user_id', $user->id)
            ->findOrFail($args['transaction_id']);

        Gate::forUser($user)->authorize('update', $transaction);

        $transaction->fill($args['changes'] ?? []);
        $transaction->save();
        $transaction->load('category');

        $warning = $transaction->type === TransactionType::Pengeluaran
            ? $this->budgetAlertChecker->check($transaction)
            : null;

        $message = 'Transaksi berhasil diubah: '.$this->transactionSummary($transaction).'.';

        if ($warning) {
            $message .= "\nPeringatan: Budget {$warning['category']} ({$warning['period']}) sudah terpakai {$warning['percentage']}% (batas peringatan {$warning['threshold']}%).";
        }

        return AiFunctionResult::success(
            message: $message,
            attachment: [
                'type' => 'transaction',
                'action' => 'updated',
                'transactions' => [$this->transactionCard($transaction)],
                'budget_warning' => $warning,
            ],
            logPayload: ['transaction_id' => $transaction->id],
        );
    }

    private function transactionSummary(Transaction $transaction): string
    {
        return "{$transaction->category->name}, ".$this->formatRupiah($transaction->amount).' pada '.$transaction->transaction_date->translatedFormat('d M Y');
    }

    private function categoryCard(int $categoryId): ?array
    {
        $category = Category::find($categoryId);

        return $category ? ['id' => $category->id, 'name' => $category->name, 'icon' => $category->icon, 'color' => $category->color] : null;
    }
}
