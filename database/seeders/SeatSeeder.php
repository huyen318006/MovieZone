<?php

namespace Database\Seeders;

use App\Models\Seat;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SeatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Seat::query()->delete();

        $seats = [];

        for ($room = 1; $room <= 15; $room++) {

            foreach (range('A', 'J') as $row) {

                for ($number = 1; $number <= 10; $number++) {
                    $code = $row . str_pad($number, 2, '0', STR_PAD_LEFT);

                    $seats[] = [
                        'room_id' => $room,
                        'row_label' => $row,
                        'seat_number' => $number,
                        'seat_code' => $code,
                        'seat_type' => $number <= 2 ? 'VIP' : 'NORMAL',
                        'status' => 'ACTIVE',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        Seat::insert($seats);
    }
}
