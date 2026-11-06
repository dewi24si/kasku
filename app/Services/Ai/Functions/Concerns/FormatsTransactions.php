<?php

namespace App\Services\Ai\Functions\Concerns;

use App\Models\Transaction;
use App\Services\Format\Rupiah;

trait FormatsTransactions
{
    /**
     * @return array<string, mixed>
     */
    protected function transactionCard(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'type' => $transaction->type->value,
            'amount' => $transaction->amount,
            'category' => [
                'id' => $transaction->category->id,
                'name' => $transaction->category->name,
                'icon' => $transaction->category->icon,
                'color' => $transaction->category->color,
            ],
            'description' => $transaction->description,
            'transaction_date' => $transaction->transaction_date->toDateString(),
        ];
    }

    protected function formatRupiah(int $amount): string
    {
        return Rupiah::format($amount);
    }
}
