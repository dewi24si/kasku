<?php

namespace Database\Seeders;

use App\Models\Category;
use App\TransactionType;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pengeluaran = [
            ['name' => 'Makan & Minum', 'icon' => '🍔', 'color' => '#F59E0B'],
            ['name' => 'Transport', 'icon' => '🚗', 'color' => '#3B82F6'],
            ['name' => 'Belanja', 'icon' => '🛍️', 'color' => '#EC4899'],
            ['name' => 'Tagihan', 'icon' => '🧾', 'color' => '#6366F1'],
            ['name' => 'Hiburan', 'icon' => '🎮', 'color' => '#8B5CF6'],
            ['name' => 'Kesehatan', 'icon' => '🏥', 'color' => '#EF4444'],
            ['name' => 'Pendidikan', 'icon' => '📚', 'color' => '#14B8A6'],
            ['name' => 'Lainnya', 'icon' => '📦', 'color' => '#6B7280'],
        ];

        $pemasukan = [
            ['name' => 'Gaji', 'icon' => '💼', 'color' => '#10B981'],
            ['name' => 'Uang Saku', 'icon' => '💵', 'color' => '#22C55E'],
            ['name' => 'Bonus', 'icon' => '🎁', 'color' => '#F59E0B'],
            ['name' => 'Lainnya', 'icon' => '📦', 'color' => '#6B7280'],
        ];

        foreach ($pengeluaran as $category) {
            Category::query()->create([
                ...$category,
                'user_id' => null,
                'type' => TransactionType::Pengeluaran,
            ]);
        }

        foreach ($pemasukan as $category) {
            Category::query()->create([
                ...$category,
                'user_id' => null,
                'type' => TransactionType::Pemasukan,
            ]);
        }
    }
}
