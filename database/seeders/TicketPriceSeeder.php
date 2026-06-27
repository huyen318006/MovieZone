<?php

namespace Database\Seeders;

use App\Models\TicketPrice;
use Illuminate\Database\Seeder;

class TicketPriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Xóa sạch dữ liệu cũ để nạp ma trận giá mới
        TicketPrice::query()->delete();

        // 2. Định nghĩa các tập thuộc tính để chạy vòng lặp sinh ma trận
        $roomTypes = ['STANDARD', 'VIP'];
        $dayTypes = ['WEEKDAY', 'WEEKEND'];
        $timeTypes = ['MORNING', 'AFTERNOON', 'EVENING'];

        // 3. Định nghĩa GIÁ GỐC chuẩn theo yêu cầu của cậu
        $basePrices = [
            'STANDARD' => 80000,   // Ghế thường mặc định 80k
            'VIP' => 150000,  // Ghế VIP mặc định 150k
            'COUPLE' => 250000,  // Ghế Đôi mặc định 250k
        ];

        $ticketPrices = [];

        // 4. Vòng lặp tự động tạo ra tất cả các trường hợp có thể xảy ra khi chọn ghế
        foreach ($roomTypes as $roomType) {
            foreach ($basePrices as $seatType => $price) {
                foreach ($dayTypes as $dayType) {
                    foreach ($timeTypes as $timeType) {

                        // Giữ nguyên mức giá cơ sở cậu muốn (80k - 150k - 250k) cho tất cả các khung giờ
                        // (Nếu muốn cuối tuần hay buổi tối tăng giá thì cậu cộng thêm ở đây, còn đồ án muốn chính xác giá đó thì giữ nguyên $price)
                        $finalPrice = $price;

                        $ticketPrices[] = [
                            'room_type' => $roomType,
                            'seat_type' => $seatType,
                            'day_type' => $dayType,
                            'time_type' => $timeType,
                            'price' => $finalPrice,
                            'status' => 'ACTIVE',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }
        }

        // 5. Đẩy toàn bộ ma trận giá vé (72 trường hợp) vào database
        TicketPrice::insert($ticketPrices);

        $this->command->info('Đã cập nhật ma trận giá vé chuẩn: STANDARD (80k), VIP (150k), COUPLE (250k) cho mọi khung giờ!');
    }
}
