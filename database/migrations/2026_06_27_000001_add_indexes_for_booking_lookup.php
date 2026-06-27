<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * S1-02: Thêm indexes tối ưu cho UC-STAFF-03 Tra cứu Booking/Vé.
     *
     * Indexes cần thiết để đảm bảo performance tra cứu < 500ms:
     * - users(phone)         → Tìm booking theo SĐT khách hàng
     * - users(email)         → Tìm booking theo email khách hàng
     * - bookings(status, payment_status) → Filter theo trạng thái
     * - bookings(created_at) → Sắp xếp kết quả mới nhất
     * - audit_logs(entity_name, entity_id) → Lấy audit log nhanh
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('phone', 'idx_users_phone');
            $table->index('email', 'idx_users_email');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['status', 'payment_status'], 'idx_bookings_status_payment');
            $table->index('created_at', 'idx_bookings_created_at');
        });

        // audit_logs(entity_name, entity_id) đã có index từ migration gốc
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_phone');
            $table->dropIndex('idx_users_email');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('idx_bookings_status_payment');
            $table->dropIndex('idx_bookings_created_at');
        });
    }
};
