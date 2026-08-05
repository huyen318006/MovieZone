<?php

namespace Database\Seeders;

use App\Models\Promotion;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Promotion::query()->delete();

        $promotions = [
            ['title' => 'Siêu khuyến mãi hè', 'description' => 'Giảm giá vé xem phim vào các ngày cuối tuần.', 'banner_url' => 'promotions/AlTKSy6N7NocsqjhgULgyNKUZby3au3ekRTN8DMU.png', 'start_date' => now(), 'end_date' => now()->addMonth(), 'status' => 'ACTIVE'],
            ['title' => 'Ngày hội phim Việt', 'description' => 'Ưu đãi đặc biệt cho các bộ phim Việt Nam.', 'banner_url' => 'promotions/HVeR3dy3m3Hxk4BzpyYLc7iQzLoABgaecOnx2uWY.png', 'start_date' => now()->addDays(2), 'end_date' => now()->addDays(15), 'status' => 'ACTIVE'],
            ['title' => 'Combo xem phim rẻ', 'description' => 'Mua combo bắp nước tiết kiệm hơn 20%.', 'banner_url' => 'promotions/hyQlYzaXwjGW45xQSfqhPdaC5W3jpPXTMc2Uk6KR.png', 'start_date' => now()->addDays(5), 'end_date' => now()->addDays(20), 'status' => 'ACTIVE'],
            ['title' => 'Thành viên thân thiết', 'description' => 'Ưu đãi riêng cho thành viên có hạng vàng.', 'banner_url' => 'promotions/jVE0JbbANeTpialt7URToSAlj2W1jp3I7qr33UZJ.png', 'start_date' => now()->addDays(7), 'end_date' => now()->addMonth(2), 'status' => 'ACTIVE'],
            ['title' => 'Khuyến mãi cuối tháng', 'description' => 'Giảm giá vé cho suất chiếu buổi tối.', 'banner_url' => 'promotions/KMvXoWMxrUiYffmQXJUmn5vOKnPkcAlvwUAZtR7e.png', 'start_date' => now()->addDays(10), 'end_date' => now()->addDays(25), 'status' => 'ACTIVE'],
        ];

        foreach ($promotions as $promotion) {
            Promotion::create([
                'title' => $promotion['title'],
                'description' => $promotion['description'],
                'banner_url' => $promotion['banner_url'],
                'start_date' => $promotion['start_date'],
                'end_date' => $promotion['end_date'],
                'status' => $promotion['status'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
