<?php

namespace Database\Factories;

use App\AiFunctionStatus;
use App\Models\AiFunctionLog;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiFunctionLog>
 */
class AiFunctionLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chat_message_id' => ChatMessage::factory(),
            'user_id' => User::factory(),
            'function_name' => $this->faker->randomElement([
                'tambah_transaksi', 'ubah_transaksi', 'hapus_transaksi', 'cari_transaksi',
                'ringkasan', 'tampilkan_grafik', 'atur_budget', 'cek_budget',
            ]),
            'arguments' => [],
            'result' => null,
            'status' => AiFunctionStatus::Success,
        ];
    }
}
