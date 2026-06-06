<?php

namespace Database\Seeders;

use App\Models\MembershipLevel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MembershipLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MembershipLevel::query()->delete();

        MembershipLevel::insert([
            [
                'name' => 'BRONZE',
                'min_points' => 0,
                'discount_percent' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'SILVER',
                'min_points' => 1000,
                'discount_percent' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'GOLD',
                'min_points' => 5000,
                'discount_percent' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
