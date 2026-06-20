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

        ShowtimeSeat::query()->delete();

        $rows = [];
        $showtimes = Showtime::with('room')->get();

        $this->command->info('Đang seed ShowtimeSeat cho ' . $showtimes->count() . ' suất chiếu...');

        foreach ($showtimes as $showtime) {
            $seats = Seat::where('room_id', $showtime->room_id)
                ->whereNull('deleted_at')
                ->get();

            foreach ($seats as $seat) {
                $rowLabel = strtoupper(trim($seat->row_label));

                $seatType = $seat->seat_type ?? 'STANDARD';

                // Xác định loại ghế cho hàng J (Sweetbox)
                if ($rowLabel === 'J') {
                    $seatType = 'COUPLE';
                }

                // Lấy giá từ bảng TicketPrice (ưu tiên)
                $priceRecord = TicketPrice::where('seat_type', $seatType)
                    ->first();

                if ($priceRecord) {
                    $price = $priceRecord->price;
                } else {
                    // Fallback giá cứng
                    $price = match ($seatType) {
                        'VIP'     => 150000,
                        'COUPLE'  => 250000,   // Giá cho cả cặp
                        default   => 90000,    // STANDARD
                    };
                }

                // Nếu là ghế đôi (COUPLE), chia đôi giá cho từng ghế đơn
                // (để khi khách chọn 2 ghế J01 + J02 = đúng 250k)
                if ($seatType === 'COUPLE') {
                    $price = (int) ($price / 2);
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

        // Insert theo chunk để tránh lỗi memory
        foreach (array_chunk($rows, 800) as $chunk) {
            ShowtimeSeat::insert($chunk);
        }

        $this->command->info("✅ Đã seed xong ShowtimeSeat!");
        $this->command->info("   - Tổng số bản ghi: " . count($rows));
    }
}
