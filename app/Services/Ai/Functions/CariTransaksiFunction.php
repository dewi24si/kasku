<?php

namespace App\Services\Ai\Functions;

use App\Models\ChatMessage;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Ai\AiFunctionResult;
use App\Services\Ai\Contracts\AiFunction;
use App\Services\Ai\Functions\Concerns\FormatsTransactions;
use App\Services\Ai\Functions\Concerns\ScopesToUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CariTransaksiFunction implements AiFunction
{
    use FormatsTransactions, ScopesToUser;

    public function name(): string
    {
        return 'cari_transaksi';
    }

    public function rules(User $user, array $args): array
    {
        return [
            'start_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'end_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'category_id' => ['sometimes', 'nullable', ...$this->categoryRule($user)],
            'type' => ['sometimes', 'nullable', Rule::in(['pemasukan', 'pengeluaran'])],
            'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function execute(User $user, array $args, ChatMessage $triggerMessage): AiFunctionResult
    {
        Gate::forUser($user)->authorize('viewAny', Transaction::class);

        $limit = $args['limit'] ?? 10;

        $query = Transaction::query()
            ->where('user_id', $user->id)
            ->with('category');

        if (! empty($args['start_date'])) {
            $query->whereDate('transaction_date', '>=', $args['start_date']);
        }

        if (! empty($args['end_date'])) {
            $query->whereDate('transaction_date', '<=', $args['end_date']);
        }

        if (! empty($args['category_id'])) {
            $query->where('category_id', $args['category_id']);
        }

        if (! empty($args['type'])) {
            $query->where('type', $args['type']);
        }

        if (! empty($args['keyword'])) {
            $query->where('description', 'like', '%'.$args['keyword'].'%');
        }

        $total = $query->count();
        $transactions = $query->latest('transaction_date')->limit($limit)->get();

        $message = $transactions->isEmpty()
            ? 'Tidak ada transaksi yang cocok ditemukan.'
            : "🔍 Ditemukan {$total} transaksi".($total > $transactions->count() ? ", menampilkan {$transactions->count()} terbaru" : '').'.';

        return AiFunctionResult::success(
            message: $message,
            attachment: [
                'type' => 'search_results',
                'query_summary' => $this->summarizeQuery($args),
                'count' => $total,
                'truncated' => $total > $transactions->count(),
                'transactions' => $transactions->map(fn (Transaction $transaction): array => $this->transactionCard($transaction))->all(),
            ],
            logPayload: ['count' => $total],
        );
    }

    private function summarizeQuery(array $args): string
    {
        $parts = array_filter([
            $args['keyword'] ?? null,
            isset($args['start_date'], $args['end_date']) ? "{$args['start_date']} s/d {$args['end_date']}" : null,
            $args['type'] ?? null,
        ]);

        return $parts === [] ? 'Semua transaksi' : implode(', ', $parts);
    }
}
