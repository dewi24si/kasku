<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\User;
use App\TransactionType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<RecurringTransaction>
 */
class RecurringTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dayOfMonth = $this->faker->numberBetween(1, 28);

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'type' => TransactionType::Pengeluaran,
            'amount' => $this->faker->numberBetween(10_000, 2_000_000),
            'description' => $this->faker->words(2, true),
            'day_of_month' => $dayOfMonth,
            'next_run_date' => Carbon::today()->addMonthNoOverflow()->startOfMonth()->setDay($dayOfMonth),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function income(): static
    {
        return $this->state(fn (): array => ['type' => TransactionType::Pemasukan]);
    }

    /**
     * Aturan yang jatuh tempo di tanggal tertentu.
     */
    public function dueOn(string $date, ?int $dayOfMonth = null): static
    {
        return $this->state(fn (): array => [
            'next_run_date' => $date,
            'day_of_month' => $dayOfMonth ?? Carbon::parse($date)->day,
        ]);
    }
}
