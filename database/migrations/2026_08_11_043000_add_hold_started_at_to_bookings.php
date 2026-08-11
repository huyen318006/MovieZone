<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thêm cột hold_started_at vào bảng bookings.
     *
     * hold_started_at = thời điểm user bắt đầu giữ ghế (click ghế đầu tiên).
     * Được inherit từ Hold Session khi submitSeats tạo Booking.
     * Cùng với expired_at tạo thành immutable hold window.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('hold_started_at')->nullable()->after('expired_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('hold_started_at');
        });
    }
};
