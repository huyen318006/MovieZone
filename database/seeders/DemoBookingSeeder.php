<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Showtime;
use App\Models\Booking;
use App\Models\Ticket;
use App\Services\TicketService;
use App\Services\QRCodeService;

class DemoBookingSeeder extends Seeder
{
    public function run(): void
    {
        $ticketService = app(TicketService::class);
        $qrService = app(QRCodeService::class);
        $now = now();

        $admin = User::where('email', 'admin1@moviezone.com')->first();
        if (!$admin) {
            return;
        }

        // Tạo 1 showtime cho ngày mai để test check-in
        $tomorrow = now()->addDay()->setHour(20)->setMinute(0)->setSecond(0);
        $showtimeId = DB::table('showtimes')->insertGetId([
            'movie_id' => 1,
            'cinema_id' => 1,
            'room_id' => 1,
            'start_time' => $tomorrow,
            'end_time' => $tomorrow->copy()->addHours(2),
            'status' => 'OPEN',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $ticketPrice = 80000;
        $bookingCode = $ticketService->generateUniqueBookingCode();

        // 1. Create Booking
        $bookingId = DB::table('bookings')->insertGetId([
            'booking_code' => $bookingCode,
            'user_id' => $admin->id,
            'showtime_id' => $showtimeId,
            'customer_name' => 'Admin Test',
            'customer_email' => 'admin1@moviezone.com',
            'customer_phone' => '0901234567',
            'total_ticket_amount' => $ticketPrice * 3,
            'total_combo_amount' => 0,
            'discount_amount' => 0,
            'final_amount' => $ticketPrice * 3,
            'status' => 'PAID',
            'payment_status' => 'PAID',
            'paid_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 2. Create Booking Seats
        // Find 3 available seats for Room 1
        $seats = DB::table('seats')->where('room_id', 1)->limit(3)->get();
        if ($seats->isEmpty()) {
            return;
        }

        $bsIds = [];
        foreach ($seats as $seat) {
            // Tạo showtime_seats
            $showtimeSeatId = DB::table('showtime_seats')->insertGetId([
                'showtime_id' => $showtimeId,
                'seat_id' => $seat->id,
                'price' => $ticketPrice,
                'status' => 'SOLD',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Tạo booking_seats
            $bsIds[] = DB::table('booking_seats')->insertGetId([
                'booking_id' => $bookingId,
                'showtime_seat_id' => $showtimeSeatId,
                'seat_code' => $seat->seat_code,
                'seat_type' => $seat->seat_type ?? 'STANDARD',
                'price' => $ticketPrice,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 3. Create Tickets
        foreach ($bsIds as $bsId) {
            $ticketCode = $ticketService->generateUniqueTicketCode();
            $qrContent = $qrService->generateQRContent($ticketCode);

            DB::table('tickets')->insert([
                'ticket_code' => $ticketCode,
                'booking_id' => $bookingId,
                'booking_seat_id' => $bsId,
                'qr_code' => $qrContent,
                'status' => 'UNUSED',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 4. Create Payment
        DB::table('payments')->insert([
            'booking_id' => $bookingId,
            'payment_method' => 'BANK_TRANSFER',
            'amount' => $ticketPrice * 3,
            'transaction_code' => 'TXN' . strtoupper(substr(md5(uniqid()), 0, 10)),
            'status' => 'SUCCESS',
            'paid_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
