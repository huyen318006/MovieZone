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
        // Xóa sạch dữ liệu cũ trước khi nạp mới
        Seat::query()->delete();

        $seats = [];

        // Chạy vòng lặp cho 15 phòng chiếu của cậu
        for ($room = 1; $room <= 15; $room++) {

            // Hàng ghế từ A đến J (10 hàng)
            foreach (range('A', 'J') as $row) {

                // Mỗi hàng có 10 ghế
                for ($number = 1; $number <= 10; $number++) {
                    
                    // Tạo mã ghế dạng A01, A02... J10 giống bản gốc của cậu
                    $code = $row . str_pad($number, 2, '0', STR_PAD_LEFT);

                    // --- LOGIC PHÂN CHIA LOẠI GHẾ THỰC TẾ ---
                    if ($row === 'J') {
                        // Hàng J (Hàng cuối cùng) -> Toàn bộ là ghế đôi COUPLE (Sweetbox)
                        $type = 'COUPLE';
                    } elseif (in_array($row, ['E', 'F', 'G', 'H'])) {
                        // Các hàng trung tâm xem phim đẹp nhất -> Ghế VIP
                        $type = 'VIP';
                    } else {
                        // Các hàng đầu A, B, C, D và hàng I -> Ghế STANDARD
                        $type = 'STANDARD';
                    }

                    $seats[] = [
                        'room_id'     => $room,
                        'row_label'   => $row,
                        'seat_number' => $number,
                        'seat_code'   => $code,
                        'seat_type'   => $type, // Khớp chuẩn enum: STANDARD, VIP, COUPLE
                        'status'      => 'ACTIVE',
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ];
                }
            }
        }

        // Đẩy toàn bộ 1500 ghế vào DB trong 1 câu lệnh duy nhất
        Seat::insert($seats);
        
        $this->command->info('Đã seed 1500 ghế cho 15 phòng theo cấu trúc: Thường, VIP và Couple!');
    }
}