<?php

namespace Database\Seeders;

use App\Models\TicketPrice;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TicketPriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TicketPrice::query()->delete();

        TicketPrice::insert([
            [
                'cinema_id' => 1,
                'room_type' => 'STANDARD',
                'seat_type' => 'STANDARD',
                'day_type' => 'WEEKDAY',
                'time_type' => 'MORNING',
                'price' => 80000,
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'cinema_id' => 1,
                'room_type' => 'VIP',
                'seat_type' => 'VIP',
                'day_type' => 'WEEKEND',
                'time_type' => 'EVENING',
                'price' => 120000,
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
