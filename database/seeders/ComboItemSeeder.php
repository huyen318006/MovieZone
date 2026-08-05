<?php

namespace Database\Seeders;

use App\Models\Combo;
use App\Models\ComboItem;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ComboItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ComboItem::query()->delete();

        $items = [
            [1, 1, 1],
            [1, 4, 2],
            [2, 2, 2],
            [2, 4, 4],
            [3, 3, 1],
            [3, 8, 1],
            [4, 3, 1],
            [4, 9, 1],
            [5, 2, 2],
            [5, 7, 1],
            [6, 3, 2],
            [6, 10, 2],
            [7, 2, 3],
            [7, 7, 3],
            [8, 3, 1],
            [8, 9, 1],
            [9, 1, 1],
            [9, 7, 1],
            [10, 3, 2],
            [10, 10, 2],
        ];

        foreach ($items as [$comboId, $productId, $quantity]) {
            ComboItem::create([
                'combo_id' => $comboId,
                'product_id' => $productId,
                'quantity' => $quantity,
            ]);
        }
    }
}
