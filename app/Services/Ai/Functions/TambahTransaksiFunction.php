<?php

namespace App\Services\Ai\Functions;

use App\Models\Category;
use App\Models\ChatMessage;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Ai\AiFunctionResult;
use App\Services\Ai\Contracts\AiFunction;
use App\Services\Ai\Functions\Concerns\FormatsTransactions;
use App\Services\Ai\Functions\Concerns\ScopesToUser;
use App\Services\Budget\BudgetAlertChecker;
use App\TransactionSource;
use App\TransactionType;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TambahTransaksiFunction implements AiFunction
{
    use FormatsTransactions, ScopesToUser;

    public function __construct(private BudgetAlertChecker $budgetAlertChecker) {}

    public function name(): string
    {
        return 'tambah_transaksi';
    }

    public function rules(User $user, array $args): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.type' => ['required', Rule::in(['pemasukan', 'pengeluaran'])],
            'items.*.category_id' => [
                'required',
                ...$this->categoryRule($user),
                $this->matchesCategoryType($args),
            ],
            'items.*.amount' => ['required', 'integer', 'min:1'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.transaction_date' => ['required', 'date_format:Y-m-d'],
        ];
    }

    private function matchesCategoryType(array $args): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($args) {
            $index = (int) explode('.', $attribute)[1];
            $type = $args['items'][$index]['type'] ?? null;
            $category = Category::find($value);

            if ($category && $type && $category->type->value !== $type) {
                $fail("Kategori \"{$category->name}\" bertipe {$category->type->value}, tidak cocok dengan transaksi {$type}.");
            }
        };
    }

    public function execute(User $user, array $args, ChatMessage $triggerMessage): AiFunctionResult
    {
        Gate::forUser($user)->authorize('create', Transaction::class);

        $created = new Collection;
        $warnings = [];

        foreach ($args['items'] as $item) {
            $transaction = Transaction::query()->create([
                'user_id' => $user->id,
                'category_id' => $item['category_id'],
                'type' => $item['type'],
                'amount' => $item['amount'],
                'description' => $item['description'] ?? null,
                'transaction_date' => $item['transaction_date'],
                'source' => TransactionSource::Chat,
                'chat_message_id' => $triggerMessage->id,
            ]);

            $transaction->load('category');
            $created->push($transaction);

            if ($transaction->type === TransactionType::Pengeluaran) {
                if ($warning = $this->budgetAlertChecker->check($transaction)) {
                    $warnings[] = $warning;
                }
            }
        }

        $message = $created->count() === 1
            ? '✅ Tercatat: '.$this->formatRupiah($created->first()->amount)." untuk {$created->first()->category->name} pada ".$created->first()->transaction_date->translatedFormat('d M Y').'.'
            : "✅ {$created->count()} transaksi berhasil dicatat.";

        foreach ($warnings as $warning) {
            $message .= "\n⚠️ Budget {$warning['category']} ({$warning['period']}) sudah terpakai {$warning['percentage']}% (batas peringatan {$warning['threshold']}%).";
        }

        return AiFunctionResult::success(
            message: $message,
            attachment: [
                'type' => 'transaction',
                'action' => 'created',
                'transactions' => $created->map(fn (Transaction $transaction): array => $this->transactionCard($transaction))->all(),
                'budget_warning' => $warnings[0] ?? null,
            ],
            logPayload: [
                'created_ids' => $created->pluck('id')->all(),
                'warnings' => $warnings,
            ],
        );
    }
}
