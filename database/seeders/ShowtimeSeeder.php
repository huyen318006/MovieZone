<?php

namespace Database\Seeders;

use App\Models\Cinema;
use App\Models\Showtime;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ShowtimeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Showtime::query()->delete();
        $cinemas = 1;


        $showtimes = [];

        for ($i = 1; $i <= 20; $i++) {
            $start = now()->addDays(rand(1, 10));

            $showtimes[] = [
                'movie_id' => rand(1, 5),
                'cinema_id' => $cinemas,
                'room_id' => rand(1, 5),
                'start_time' => $start,
                'end_time' => $start->copy()->addHours(2),
                'format' => rand(1, 2) === 1 ? '2D' : '3D',
                'language_type' => rand(1, 2) === 1 ? 'English' : 'Vietnamese',
                'status' => 'OPEN',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Showtime::insert($showtimes);
    }
}
