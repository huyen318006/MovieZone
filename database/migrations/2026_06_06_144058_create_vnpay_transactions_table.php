<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tạo bảng lưu trữ giao dịch thanh toán VNPAY
     */
    public function up(): void
    {
        Schema::create('vnpay_transactions', function (Blueprint $table) {
            $table->id();

            // ── Thông tin đơn hàng từ hệ thống merchant ───────────────────
            $table->string('order_id', 100)->index()->comment('Mã đơn hàng (vnp_TxnRef)');
            $table->unsignedBigInteger('user_id')->nullable()->index()->comment('User thực hiện thanh toán');
            $table->decimal('amount', 15, 2)->comment('Số tiền thanh toán (VND)');
            $table->string('order_info', 255)->nullable()->comment('Mô tả nội dung thanh toán');
            $table->string('order_type', 100)->default('other')->comment('Mã danh mục hàng hóa');

            // ── Thông tin phản hồi từ VNPAY ───────────────────────────────
            $table->string('vnp_transaction_no', 50)->nullable()->comment('Mã giao dịch tại VNPAY');
            $table->string('vnp_bank_code', 20)->nullable()->comment('Mã ngân hàng thanh toán');
            $table->string('vnp_bank_tran_no', 255)->nullable()->comment('Mã giao dịch tại ngân hàng');
            $table->string('vnp_card_type', 20)->nullable()->comment('Loại thẻ: ATM, QRCODE, etc.');
            $table->string('vnp_response_code', 10)->nullable()->comment('Mã phản hồi kết quả từ VNPAY');
            $table->string('vnp_transaction_status', 10)->nullable()->comment('Trạng thái GD tại VNPAY');
            $table->string('vnp_pay_date', 14)->nullable()->comment('Thời gian thanh toán (yyyyMMddHHmmss)');

            // ── Trạng thái nội bộ ─────────────────────────────────────────
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])
                ->default('pending')
                ->index()
                ->comment('Trạng thái thanh toán nội bộ');

            // ── Thông tin IP & metadata ────────────────────────────────────
            $table->string('ip_address', 45)->nullable()->comment('IP khách hàng');
            $table->string('bank_code_request', 20)->nullable()->comment('Bank code gửi đi (nếu có)');
            $table->text('payment_url')->nullable()->comment('URL thanh toán đã tạo');

            // ── Hoàn tiền ─────────────────────────────────────────────────
            $table->decimal('refund_amount', 15, 2)->nullable()->comment('Số tiền đã hoàn');
            $table->timestamp('refunded_at')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            // ── Foreign key ───────────────────────────────────────────────
            // Bỏ comment nếu bạn có bảng users
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vnpay_transactions');
    }
};
