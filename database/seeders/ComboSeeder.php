<?php

namespace Database\Seeders;

use App\Models\Combo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ComboSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Combo::query()->delete();

        Combo::insert([
            [
                'name' => 'Combo Couple',
                'description' => '1 popcorn + 2 drinks',
                'price' => 99000,
                'image_url' => 'combos/combo-couple.jpg',
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Combo Family',
                'description' => '2 popcorns + 4 drinks',
                'price' => 199000,
                'image_url' => 'combos/combo-family.jpg',
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
