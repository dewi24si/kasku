<?php

namespace App\Services\Ai\Functions;

use App\Models\Category;
use App\Models\ChatMessage;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Ai\AiFunctionResult;
use App\Services\Ai\Contracts\AiFunction;
use App\Services\Ai\Functions\Concerns\FormatsTransactions;
use App\Services\Ai\Functions\Concerns\RejectsFutureDates;
use App\Services\Ai\Functions\Concerns\ScopesToUser;
use App\Services\Budget\BudgetAlertChecker;
use App\TransactionSource;
use App\TransactionType;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TambahTransaksiFunction implements AiFunction
{
    use FormatsTransactions, RejectsFutureDates, ScopesToUser;

    public const MAX_RECEIPT_ITEMS = 50;

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
            'items.*.transaction_date' => ['bail', 'required', 'date_format:Y-m-d', $this->notInFutureRule()],
            'items.*.receipt_items' => ['nullable', 'array', 'max:'.self::MAX_RECEIPT_ITEMS],
            'items.*.receipt_items.*.name' => ['required', 'string', 'max:100'],
            'items.*.receipt_items.*.quantity' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'items.*.receipt_items.*.unit_price' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'items.*.receipt_items.*.subtotal' => ['required', 'integer', 'min:0', 'max:999999999999'],
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
        $receiptPath = $this->receiptPathOf($triggerMessage);

        foreach ($args['items'] as $item) {
            $transaction = DB::transaction(function () use ($user, $item, $triggerMessage, $receiptPath): Transaction {
                $transaction = Transaction::query()->create([
                    'user_id' => $user->id,
                    'category_id' => $item['category_id'],
                    'type' => $item['type'],
                    'amount' => $item['amount'],
                    'description' => $item['description'] ?? null,
                    'transaction_date' => $item['transaction_date'],
                    'source' => TransactionSource::Chat,
                    'chat_message_id' => $triggerMessage->id,
                    'receipt_path' => $receiptPath,
                ]);

                // Barang hanya disimpan kalau pesannya benar-benar membawa foto struk, supaya AI tidak bisa
                // "mengarang" daftar barang dari pesan teks biasa.
                if ($receiptPath !== null && ! empty($item['receipt_items'])) {
                    $transaction->items()->createMany(array_map(fn (array $receiptItem): array => [
                        'name' => $receiptItem['name'],
                        'quantity' => $receiptItem['quantity'] ?? null,
                        'unit_price' => $receiptItem['unit_price'] ?? null,
                        'subtotal' => $receiptItem['subtotal'],
                    ], $item['receipt_items']));
                }

                return $transaction;
            });

            $transaction->load('category')->loadCount('items');
            $created->push($transaction);

            if ($transaction->type === TransactionType::Pengeluaran) {
                if ($warning = $this->budgetAlertChecker->check($transaction)) {
                    $warnings[] = $warning;
                }
            }
        }

        $message = $created->count() === 1
            ? 'Tercatat: '.$this->formatRupiah($created->first()->amount)." untuk {$created->first()->category->name} pada ".$created->first()->transaction_date->translatedFormat('d M Y').'.'
            : "{$created->count()} transaksi berhasil dicatat.";

        foreach ($warnings as $warning) {
            $message .= "\nPeringatan: Budget {$warning['category']} ({$warning['period']}) sudah terpakai {$warning['percentage']}% (batas peringatan {$warning['threshold']}%).";
        }

        return AiFunctionResult::success(
            message: $message,
            attachment: [
                'type' => 'transaction',
                'action' => 'created',
                'transactions' => $created->map(fn (Transaction $transaction): array => [
                    ...$this->transactionCard($transaction),
                    'receipt_items_count' => $transaction->items_count,
                ])->all(),
                'budget_warning' => $warnings[0] ?? null,
            ],
            logPayload: [
                'created_ids' => $created->pluck('id')->all(),
                'warnings' => $warnings,
            ],
        );
    }

    /**
     * Path foto struk di disk privat kalau pesan pemicunya membawa foto. Semua transaksi dari satu struk merujuk ke foto yang sama.
     */
    private function receiptPathOf(ChatMessage $triggerMessage): ?string
    {
        $attachment = $triggerMessage->attachment ?? [];

        if (($attachment['type'] ?? null) !== 'receipt_photo' || blank($attachment['path'] ?? null)) {
            return null;
        }

        return $attachment['path'];
    }
}
