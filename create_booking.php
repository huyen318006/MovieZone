<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

DB::beginTransaction();

try {
    $now = now();
    $userId = 2; // admin1@moviezone.com
    $showtimeId = 1; // Doraemon Movie, Room 1, today
    $ticketPrice = 80000;
    $bookingCode = 'BK' . strtoupper(substr(md5(uniqid()), 0, 10));

    // 1. Create Booking
    $bookingId = DB::table('bookings')->insertGetId([
        'booking_code' => $bookingCode,
        'user_id' => $userId,
        'showtime_id' => $showtimeId,
        'customer_name' => 'Admin',
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

    echo "Booking created: ID={$bookingId}, Code={$bookingCode}\n";

    // 2. Create Booking Seats (A01, A02, A03)
    $seats = [
        ['showtime_seat_id' => 1, 'seat_code' => 'A01'],
        ['showtime_seat_id' => 2, 'seat_code' => 'A02'],
        ['showtime_seat_id' => 3, 'seat_code' => 'A03'],
    ];

    $bsIds = [];
    foreach ($seats as $seat) {
        $bsIds[] = DB::table('booking_seats')->insertGetId([
            'booking_id' => $bookingId,
            'showtime_seat_id' => $seat['showtime_seat_id'],
            'seat_code' => $seat['seat_code'],
            'seat_type' => 'STANDARD',
            'price' => $ticketPrice,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
    echo "Booking seats created: " . implode(', ', $bsIds) . "\n";

    // 3. Update showtime_seats -> SOLD
    DB::table('showtime_seats')->whereIn('id', [1, 2, 3])->update([
        'status' => 'SOLD',
        'updated_at' => $now,
    ]);
    echo "Showtime seats updated to SOLD\n";

    // 4. Create Tickets
    $qrService = app(\App\Services\QRCodeService::class);
    $ticketService = app(\App\Services\TicketService::class);

    foreach ($bsIds as $i => $bsId) {
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
        echo "Ticket created: {$ticketCode} (seat {$seats[$i]['seat_code']})\n";
    }

    // 5. Create Payment
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
    echo "Payment created\n";

    DB::commit();
    echo "\n✅ SUCCESS! Booking {$bookingCode} with 3 tickets created.\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
