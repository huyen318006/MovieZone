<?php

namespace Database\Seeders;

use App\Services\TicketService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BookingCancellationSeeder extends Seeder
{
        public function run(): void
    {
        // 1. Tìm một User có sẵn (thường là Admin hoặc Khách hàng) để làm người hủy đơn
        $userId = \Illuminate\Support\Facades\DB::table('users')->value('id');
        
        // 2. Tìm một suất chiếu có sẵn trong hệ thống để đặt vé
        $showtimeId = \Illuminate\Support\Facades\DB::table('showtimes')->value('id');

        // Nếu hệ thống đã có User và Suất chiếu từ các seeder trước, tiến hành tạo mới hoàn toàn từ đầu
        if ($userId && $showtimeId) {
            
            // Format mới: CSPRNG + safe alphabet [A-Z2-9]
            $bookingCode = app(TicketService::class)->generateUniqueBookingCode();

            // Bước 1: Tạo hẳn một bản ghi Booking mới ở trạng thái CANCELLED
            $bookingId = \Illuminate\Support\Facades\DB::table('bookings')->insertGetId([
                'booking_code' => $bookingCode,
                'user_id' => $userId,
                'showtime_id' => $showtimeId,
                'total_ticket_amount' => 80000.00, // Giá vé chuẩn của rạp
                'total_combo_amount' => 0.00,
                'discount_amount' => 0.00,
                'final_amount' => 80000.00,
                'status' => 'CANCELLED', // Trạng thái đã hủy luôn từ đầu
                'payment_status' => 'UNPAID', // Chưa thanh toán hoặc bấm hủy
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Bước 2: Chèn lý do hủy đơn tương ứng với Booking vừa tạo ở trên vào bảng phụ
            \Illuminate\Support\Facades\DB::table('booking_cancellations')->insert([
                'booking_id'  => $bookingId, // Lấy đúng ID vừa sinh ra ở Bước 1
                'canceled_by' => $userId,
                'reason'      => 'Khách hàng bận việc đột xuất không thể tham gia suất chiếu.',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

}
