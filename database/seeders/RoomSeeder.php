<?php

namespace Database\Seeders;

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
        Room::query()->delete();

        $rooms = [];

        for ($cinema = 1; $cinema <= 3; $cinema++) {

            $rooms[] = [
                'cinema_id' => $cinema,
                'name' => 'Room 1',
                'room_type' => '2D',
                'total_seats' => 100,
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $rooms[] = [
                'cinema_id' => $cinema,
                'name' => 'Room 2',
                'room_type' => '3D',
                'total_seats' => 120,
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $rooms[] = [
                'cinema_id' => $cinema,
                'name' => 'Room 3',
                'room_type' => 'IMAX',
                'total_seats' => 150,
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $rooms[] = [
                'cinema_id' => $cinema,
                'name' => 'Room 4',
                'room_type' => 'VIP',
                'total_seats' => 80,
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $rooms[] = [
                'cinema_id' => $cinema,
                'name' => 'Room 5',
                'room_type' => '4DX',
                'total_seats' => 90,
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Room::insert($rooms);
    }
}
