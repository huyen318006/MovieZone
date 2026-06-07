<?php

namespace Database\Seeders;

use App\Models\PointTransaction;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PointTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PointTransaction::query()->delete();

        for ($i = 1; $i <= 20; $i++) {
            PointTransaction::create([
                'user_id' => rand(1, 4),
                'booking_id' => rand(1, 20),
                'points' => rand(50, 500),
                'type' => 'EARN',
                'created_at' => now(),
            ]);
        }
    }
}
