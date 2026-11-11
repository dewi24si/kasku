<?php

namespace App\Models;

use Database\Factories\TransactionItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['transaction_id', 'name', 'quantity', 'unit_price', 'subtotal'])]
class TransactionItem extends Model
{
    /** @use HasFactory<TransactionItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'integer',
            'subtotal' => 'integer',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Jumlah barang tanpa nol di belakang koma, misalnya "2" atau "1,5". Null kalau tidak tercatat.
     */
    public function formattedQuantity(): ?string
    {
        if ($this->quantity === null) {
            return null;
        }

        return str_replace('.', ',', rtrim(rtrim($this->quantity, '0'), '.'));
    }
}
