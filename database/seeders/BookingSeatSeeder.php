<?php

namespace Database\Seeders;

use App\Models\BookingSeat;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BookingSeatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        BookingSeat::query()->delete();

        for ($i = 1; $i <= 50; $i++) {
            BookingSeat::create([
                'booking_id' => rand(1, 20),
                'showtime_seat_id' => $i,
                'seat_code' => 'S' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'seat_type' => rand(1, 2) === 1 ? 'STANDARD' : 'VIP',
                'price' => rand(80000, 120000),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
