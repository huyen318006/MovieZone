<?php

namespace Database\Seeders;

use App\Models\ComboItem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ComboItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        ComboItem::truncate();

        ComboItem::insert([
            [
                'combo_id' => 1,
                'product_id' => 1,
                'quantity' => 1,
            ],
            [
                'combo_id' => 1,
                'product_id' => 3,
                'quantity' => 2,
            ],
            [
                'combo_id' => 2,
                'product_id' => 2,
                'quantity' => 2,
            ],
            [
                'combo_id' => 2,
                'product_id' => 3,
                'quantity' => 4,
            ],
        ]);
    }
}
