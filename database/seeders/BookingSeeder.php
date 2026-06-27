<?php

namespace Database\Seeders;

use App\Models\Booking;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Booking::query()->delete();

        // Đa dạng trạng thái để test tra cứu
        $statuses = [
            ['status' => 'PAID',      'payment_status' => 'PAID'],
            ['status' => 'PAID',      'payment_status' => 'PAID'],
            ['status' => 'PAID',      'payment_status' => 'PAID'],
            ['status' => 'PENDING',   'payment_status' => 'UNPAID'],
            ['status' => 'PENDING',   'payment_status' => 'UNPAID'],
            ['status' => 'CANCELLED', 'payment_status' => 'REFUNDED'],
            ['status' => 'EXPIRED',   'payment_status' => 'UNPAID'],
            ['status' => 'PAID',      'payment_status' => 'PAID'],
            ['status' => 'CANCELLED', 'payment_status' => 'FAILED'],
            ['status' => 'PAID',      'payment_status' => 'PAID'],
        ];

        for ($i = 1; $i <= 20; $i++) {
            $totalTicketAmount = rand(80000, 250000);
            $totalComboAmount = rand(0, 150000);
            $discountAmount = rand(0, 50000);
            $finalAmount = $totalTicketAmount + $totalComboAmount - $discountAmount;

            // Ngày tạo random trong 7 ngày qua
            $createdDate = now()->subDays(rand(0, 7));
            $dateStr = $createdDate->format('Ymd');

            // Format chuẩn: BK-YYYYMMDD-NNN
            $bookingCode = 'BK-' . $dateStr . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);

            $statusPair = $statuses[($i - 1) % count($statuses)];
            $isPaid = $statusPair['status'] === 'PAID';

            Booking::create([
                'user_id'              => rand(1, 4),
                'showtime_id'          => rand(1, 20),
                'booking_code'         => $bookingCode,
                'total_ticket_amount'  => $totalTicketAmount,
                'total_combo_amount'   => $totalComboAmount,
                'discount_amount'      => $discountAmount,
                'final_amount'         => $finalAmount,
                'status'               => $statusPair['status'],
                'payment_status'       => $statusPair['payment_status'],
                'expired_at'           => $createdDate->copy()->addMinutes(15),
                'paid_at'              => $isPaid ? $createdDate->copy()->addMinutes(rand(1, 10)) : null,
                'created_at'           => $createdDate,
                'updated_at'           => $createdDate,
            ]);
        }
    }
}

