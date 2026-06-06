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

        for ($i = 1; $i <= 20; $i++) {
            $totalTicketAmount = rand(80000, 250000);
            $totalComboAmount = rand(0, 150000);
            $discountAmount = rand(0, 50000);
            $finalAmount = $totalTicketAmount + $totalComboAmount - $discountAmount;

            Booking::create([
                'user_id' => rand(1, 4),
                'showtime_id' => rand(1, 20),
                'booking_code' => 'BK' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'total_ticket_amount' => $totalTicketAmount,
                'total_combo_amount' => $totalComboAmount,
                'discount_amount' => $discountAmount,
                'final_amount' => $finalAmount,
                'status' => 'PAID',
                'payment_status' => 'PAID',
                'expired_at' => now()->addDays(rand(1, 7)),
                'paid_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
