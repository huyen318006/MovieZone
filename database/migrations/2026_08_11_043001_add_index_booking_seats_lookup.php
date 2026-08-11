<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thêm index cho booking_seats để tăng tốc kiểm tra double booking.
     *
     * Query pattern: SELECT FROM booking_seats
     *   JOIN bookings ON booking_seats.booking_id = bookings.id
     *   WHERE booking_seats.showtime_seat_id IN (...)
     *   AND bookings.status IN ('PAID', 'PENDING', ...)
     */
    public function up(): void
    {
        Schema::table('booking_seats', function (Blueprint $table) {
            $table->index(['showtime_seat_id', 'booking_id'], 'idx_booking_seats_active_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('booking_seats', function (Blueprint $table) {
            $table->dropIndex('idx_booking_seats_active_lookup');
        });
    }
};
