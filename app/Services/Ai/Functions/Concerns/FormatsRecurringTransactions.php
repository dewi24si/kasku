<?php

namespace App\Services\Ai\Functions\Concerns;

use App\Models\RecurringTransaction;

trait FormatsRecurringTransactions
{
    /**
     * Data satu aturan untuk kartu "Rutin" di chat. Disimpan apa adanya di attachment, jadi formatnya dipisah
     * dari model supaya pesan lama tetap bisa dirender.
     *
     * @return array{id: int, type: string, amount: int, description: string, category: array{id: int, name: string, icon: string|null}, day_of_month: int, next_run_date: string, is_active: bool}
     */
    protected function recurringCard(RecurringTransaction $rule): array
    {
        return [
            'id' => $rule->id,
            'type' => $rule->type->value,
            'amount' => $rule->amount,
            'description' => $rule->description,
            'category' => [
                'id' => $rule->category->id,
                'name' => $rule->category->name,
                'icon' => $rule->category->icon,
            ],
            'day_of_month' => $rule->day_of_month,
            'next_run_date' => $rule->next_run_date->toDateString(),
            'is_active' => $rule->is_active,
        ];
    }
}
