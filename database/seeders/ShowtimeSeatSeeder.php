<?php

namespace Database\Seeders;

use App\Models\Seat;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\TicketPrice;
use Illuminate\Database\Seeder;

class ShowtimeSeatSeeder extends Seeder
{
    public function run(): void
    {
        ShowtimeSeat::query()->delete();

        $rows = [];

        foreach (Showtime::all() as $showtime) {
            $seats = Seat::where('room_id', $showtime->room_id)->get();

            foreach ($seats as $seat) {
                // Lấy nhãn hàng ghế (A, B, C, D, E, F, G, H, I, J)
                $rowLabel = strtoupper($seat->row_label); 

                // XỬ LÝ SỬA LỖI: Ép loại ghế dựa trên hàng thực tế
                if ($rowLabel === 'F') {
                    $currentSeatType = 'VIP';
                } elseif ($rowLabel === 'J') {
                    $currentSeatType = 'COUPLE'; 
                } else {
                    $currentSeatType = 'STANDARD'; 
                }

                // 1. Cố gắng lấy giá từ bảng cấu hình trước
                $priceRecord = TicketPrice::where('cinema_id', $showtime->cinema_id)
                    ->where('seat_type', $currentSeatType) 
                    ->first();

                // 2. Nếu tìm thấy giá trong bảng thì dùng, không thì dùng logic cứng fallback
                if ($priceRecord) {
                    $price = $priceRecord->price;
                } else {
                    $price = match ($currentSeatType) {
                        'VIP' => 150000,
                        'COUPLE' => 250000, // Giá gốc cho cả cặp ghế đôi
                        default => 80000, 
                    };
                }

                // SỬA LỖI TẠI ĐÂY: Nếu là hàng J (ghế đôi), chia đôi giá ra cho từng ghế đơn trong DB
                // Khi giao diện cộng 2 ghế J lại (ví dụ J01 + J02) sẽ ra đúng chuẩn 250k!
                if ($rowLabel === 'J') {
                    $price = $price / 2; 
                }

                $rows[] = [
                    'showtime_id' => $showtime->id,
                    'seat_id'     => $seat->id,
                    'price'       => $price,
                    'status'      => 'AVAILABLE',
                    'held_until'  => null,
                    'held_by'     => null,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }
        }

        // Tăng chunk size nếu dữ liệu quá lớn để tránh lỗi memory
        foreach (array_chunk($rows, 500) as $chunk) {
            ShowtimeSeat::insert($chunk);
        }
    }
}