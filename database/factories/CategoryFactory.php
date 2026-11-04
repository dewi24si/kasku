<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use App\TransactionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
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
            'name' => $this->faker->unique()->word(),
            'type' => $this->faker->randomElement(TransactionType::cases()),
            'icon' => null,
            'color' => null,
        ];
    }
}
