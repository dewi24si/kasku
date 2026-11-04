<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\TransactionSource;
use App\TransactionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'type' => $this->faker->randomElement(TransactionType::cases()),
            'amount' => $this->faker->numberBetween(1_000, 1_000_000),
            'description' => $this->faker->sentence(3),
            'transaction_date' => $this->faker->dateTimeThisYear(),
            'source' => TransactionSource::Manual,
            'chat_message_id' => null,
        ];
    }
}
