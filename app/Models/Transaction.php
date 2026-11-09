<?php

namespace App\Models;

use App\TransactionSource;
use App\TransactionType;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'category_id', 'type', 'amount', 'description', 'transaction_date', 'source', 'chat_message_id', 'recurring_transaction_id'])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'source' => TransactionSource::class,
            'amount' => 'integer',
            'transaction_date' => 'date',
        ];
    }

    /**
     * Apply the transaction history filters (search, type, category, period).
     *
     * @param  Builder<self>  $query
     * @param  array{search?: string|null, type?: string|null, category_id?: int|string|null, period?: string|null}  $filters
     */
    #[Scope]
    protected function filter(Builder $query, array $filters): void
    {
        $search = $filters['search'] ?? null;
        $type = $filters['type'] ?? null;
        $categoryId = $filters['category_id'] ?? null;
        $period = $filters['period'] ?? null;

        $query
            ->when(filled($search), fn (Builder $query) => $query->where('description', 'like', '%'.$search.'%'))
            ->when(filled($type), fn (Builder $query) => $query->where('type', $type))
            ->when(filled($categoryId), fn (Builder $query) => $query->where('category_id', $categoryId))
            ->when(filled($period), function (Builder $query) use ($period): void {
                [$year, $month] = explode('-', $period);
                $query->whereYear('transaction_date', $year)->whereMonth('transaction_date', $month);
            });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function chatMessage(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class);
    }

    public function recurringTransaction(): BelongsTo
    {
        return $this->belongsTo(RecurringTransaction::class);
    }
}
