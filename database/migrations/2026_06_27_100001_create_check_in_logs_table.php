<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * UC-STAFF-01: Bảng log chi tiết cho mỗi lần check-in/scan.
     * Tách riêng khỏi audit_logs để truy vấn thống kê nhanh theo suất chiếu.
     */
    public function up(): void
    {
        Schema::create('check_in_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ticket_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('booking_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('showtime_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('staff_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->enum('scan_method', ['QR_SCAN', 'MANUAL'])->default('QR_SCAN');

            $table->string('qr_payload')->nullable();

            $table->enum('result', ['SUCCESS', 'FAILED'])->default('SUCCESS');

            $table->string('failure_reason')->nullable();

            $table->string('ip_address', 45)->nullable();

            $table->text('user_agent')->nullable();

            $table->timestamp('created_at');

            // Indexes cho thống kê
            $table->index(['showtime_id', 'created_at'], 'idx_checkin_showtime_time');
            $table->index(['staff_id', 'created_at'], 'idx_checkin_staff_time');
            $table->index(['result'], 'idx_checkin_result');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_in_logs');
    }
};
