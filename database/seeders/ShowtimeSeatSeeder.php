<?php

namespace Database\Seeders;

use App\Models\Seat;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ShowtimeSeatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ShowtimeSeat::query()->delete();

        $rows = [];

        foreach (Showtime::all() as $showtime) {
            $roomId = $showtime->room_id;
            $seats = Seat::where('room_id', $roomId)->get();

            foreach ($seats as $seat) {
                $rows[] = [
                    'showtime_id' => $showtime->id,
                    'seat_id' => $seat->id,
                    'price' => rand(80000, 150000),
                    'status' => 'AVAILABLE',
                    'held_until' => null,
                    'held_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        ShowtimeSeat::insert($rows);
    }
}
