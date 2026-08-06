<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_cancellations', function (Blueprint $table) {
            // Loại bản ghi: CANCELLATION (hủy thường) hoặc LATE_PAYMENT (thanh toán muộn cần hoàn tiền)
            $table->string('type', 30)->default('CANCELLATION')->after('id');

            // Trạng thái xử lý (chỉ dùng cho LATE_PAYMENT): pending_refund, refunded
            $table->string('refund_status', 30)->nullable()->after('reason');

            // Thông tin giao dịch thanh toán muộn (JSON)
            $table->json('notes')->nullable()->after('refund_status');

            // Cho phép canceled_by = NULL khi hệ thống tự phát hiện (không có admin nào hủy)
            $table->foreignId('canceled_by')->nullable()->change();

            // MySQL: phải drop FK trước, rồi mới drop unique index
            $table->dropForeign(['booking_id']);
            $table->dropUnique(['booking_id']);

            // Thêm lại FK bình thường (không unique) để 1 booking có thể có
            // cả bản ghi hủy lẫn bản ghi thanh toán muộn
            $table->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();

            // Index hỗ trợ tìm kiếm
            $table->index('type');
            $table->index('refund_status');
        });
    }

    public function down(): void
    {
        Schema::table('booking_cancellations', function (Blueprint $table) {
            $table->dropColumn(['type', 'refund_status', 'notes']);
            $table->dropForeign(['booking_id']);
            $table->unique('booking_id');
            $table->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();
            $table->foreignId('canceled_by')->nullable(false)->change();
            $table->dropIndex(['type']);
            $table->dropIndex(['refund_status']);
        });
    }
};
