<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\Seat;
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

        // Lấy tất cả các phòng hiện có (không hardcode số phòng)
        $rooms = Room::all();

        foreach ($rooms as $room) {

            // ==================== CẤU HÌNH HÀNG GHẾ (DỄ THÊM SAU NÀY) ====================
            $rowConfig = [
                // Hàng thường
                'A' => ['type' => 'STANDARD', 'seats' => 10],
                'B' => ['type' => 'STANDARD', 'seats' => 10],
                'C' => ['type' => 'STANDARD', 'seats' => 10],
                'D' => ['type' => 'STANDARD', 'seats' => 10],

                // Hàng VIP
                'E' => ['type' => 'VIP', 'seats' => 10],
                'F' => ['type' => 'VIP', 'seats' => 10],
                'G' => ['type' => 'VIP', 'seats' => 10],
                'H' => ['type' => 'VIP', 'seats' => 10],

                // Hàng thường
                'I' => ['type' => 'STANDARD', 'seats' => 10],

                // Hàng Sweetbox (ghế đôi)
                'J' => ['type' => 'COUPLE', 'seats' => 10],

            ];

            foreach ($rowConfig as $rowLabel => $config) {
                $seatType = $config['type'];
                $numSeats = $config['seats'];

                for ($number = 1; $number <= $numSeats; $number++) {
                    $code = $rowLabel.str_pad($number, 2, '0', STR_PAD_LEFT);

                    $seats[] = [
                        'room_id' => $room->id,
                        'row_label' => $rowLabel,
                        'seat_number' => $number,
                        'seat_code' => $code,
                        'seat_type' => $seatType,
                        'status' => 'ACTIVE',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        // Insert một lần duy nhất (rất nhanh)
        Seat::insert($seats);

        $this->command->info('✅ Đã seed ghế thành công cho '.$rooms->count().' phòng!');
        $this->command->info('   - Tổng số ghế: '.count($seats));
    }
}
