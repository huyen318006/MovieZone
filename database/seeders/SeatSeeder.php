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

            // Giá gốc theo loại ghế (đồng bộ với TicketPriceSeeder)
            $basePrices = [
                'STANDARD' => 80000,
                'VIP'      => 150000,
                'COUPLE'   => 250000,
            ];

            foreach ($rowConfig as $rowLabel => $config) {
                $seatType = $config['type'];
                $numSeats = $config['seats'];

                for ($number = 1; $number <= $numSeats; $number++) {
                    $code = $rowLabel . str_pad($number, 2, '0', STR_PAD_LEFT);

                    $seats[] = [
                        'room_id'      => $room->id,
                        'row_label'    => $rowLabel,
                        'seat_number'  => $number,
                        'seat_code'    => $code,
                        'seat_type'    => $seatType,
                        'status'       => 'ACTIVE',
                        'price'        => $basePrices[$seatType] ?? 80000,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ];
                }
            }

            // ==================== GHẾ DEMO (Z99, 10.000 VND) ====================
            // Ghế demo dùng để test booking với giá rẻ, mỗi phòng 1 ghế
            $seats[] = [
                'room_id' => $room->id,
                'row_label' => 'Z',
                'seat_number' => 99,
                'seat_code' => 'Z99',
                'seat_type' => 'DEMO',
                'status' => 'ACTIVE',
                'price' => 10000,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert một lần duy nhất (rất nhanh)
        Seat::insert($seats);

        $this->command->info('✅ Đã seed ghế thành công cho '.$rooms->count().' phòng!');
        $this->command->info('   - Tổng số ghế: '.count($seats));
        $this->command->info('   - Bao gồm ghế demo Z99 (10.000 VND) cho mỗi phòng');
    }
}
