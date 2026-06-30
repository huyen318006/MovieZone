<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('booking_cancellations', function (Blueprint $table) {
            $table->id();

            // Liên kết 1-1 với bảng bookings. Nếu đơn hàng bị xóa, bản ghi này tự mất theo
            $table->foreignId('booking_id')
                ->unique() // Đảm bảo 1 đơn hàng chỉ có 1 lý do hủy duy nhất
                ->constrained('bookings')
                ->cascadeOnDelete();

            // Lưu ID của Admin đã bấm nút hủy đơn hàng
            $table->foreignId('canceled_by')
                ->constrained('users') // Admin và khách dùng chung bảng users
                ->cascadeOnDelete();

            // Lý do hủy đơn
            $table->string('reason', 255);

            
            $table->timestamps();
        });
    }
};
