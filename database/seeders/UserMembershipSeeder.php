<?php

namespace Database\Seeders;

use App\Models\UserMembership;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserMembershipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        UserMembership::query()->delete();

        UserMembership::insert([
            [
                'user_id' => 1,
                'level_id' => 3,
                'points' => 8000,
                'total_spent' => 200000,
                'updated_at' => now(),
            ],
            [
                'user_id' => 2,
                'level_id' => 2,
                'points' => 2500,
                'total_spent' => 75000,
                'updated_at' => now(),
            ],
            [
                'user_id' => 3,
                'level_id' => 1,
                'points' => 200,
                'total_spent' => 10000,
                'updated_at' => now(),
            ],
        ]);
    }
}
