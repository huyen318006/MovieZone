<?php

namespace Database\Seeders;

use App\Models\Cinema;
use App\Models\Room;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ==================== SEEDER ROOM - DỄ MỞ RỘNG ====================//
        Room::query()->delete();

        $cinemas = Cinema::all();

        if ($cinemas->isEmpty()) {
            $this->command->warn('Không có cinema');
            return;
        }

        $data = [
            ['name' => 'Room 1', 'room_type' => '2D', 'total_seats' => 120],
            ['name' => 'Room 2', 'room_type' => '3D', 'total_seats' => 140],
            ['name' => 'Room 3', 'room_type' => 'IMAX', 'total_seats' => 160],
            ['name' => 'Room 4', 'room_type' => 'VIP', 'total_seats' => 80],
            ['name' => 'Room 5', 'room_type' => '4DX', 'total_seats' => 100],
        ];

        $rooms = [];

        foreach ($data as $i => $room) {

            $cinema = $cinemas->random();

            $rooms[] = [
                'cinema_id'   => $cinema->id,
                'name'        => $room['name'],
                'room_type'   => $room['room_type'],
                'total_seats' => $room['total_seats'],
                'status'      => 'ACTIVE',
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
        }

        Room::insert($rooms);
    }
}
