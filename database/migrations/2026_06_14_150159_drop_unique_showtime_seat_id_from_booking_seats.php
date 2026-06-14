<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bỏ UNIQUE constraint trên showtime_seat_id
     * Lý do: Một ghế có thể được đặt lại sau khi booking cũ bị huỷ/expired.
     */
    public function up(): void
    {
        Schema::table('booking_seats', function (Blueprint $table) {
            // Phải drop FK trước vì MySQL không cho drop index đang được FK tham chiếu
            $table->dropForeign(['showtime_seat_id']);
        });

        Schema::table('booking_seats', function (Blueprint $table) {
            // Drop unique constraint
            $table->dropUnique(['showtime_seat_id']);
            // Thêm lại FK + index thường
            $table->foreign('showtime_seat_id')->references('id')->on('showtime_seats')->cascadeOnDelete();
            $table->index('showtime_seat_id');
        });
    }

    public function down(): void
    {
        Schema::table('booking_seats', function (Blueprint $table) {
            $table->dropForeign(['showtime_seat_id']);
            $table->dropIndex(['showtime_seat_id']);
        });

        Schema::table('booking_seats', function (Blueprint $table) {
            $table->unique('showtime_seat_id');
            $table->foreign('showtime_seat_id')->references('id')->on('showtime_seats')->cascadeOnDelete();
        });
    }
};
