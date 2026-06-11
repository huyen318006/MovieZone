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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_code', 50)->unique();

            $table->foreignId('sepay_order_id')
                ->constrained('sepay_orders')
                ->cascadeOnDelete();

            $table->string('customer_email');
            $table->string('customer_name')->nullable();

            // Thông tin phim & suất chiếu
            $table->string('movie_title');
            $table->string('cinema');
            $table->string('room');
            $table->string('showtime');
            $table->string('show_date');
            $table->string('format')->default('2D');

            // Ghế & thanh toán
            $table->json('seats'); // [{code, type, price}, ...]
            $table->unsignedInteger('total_amount');
            $table->string('payment_method')->default('QR Bank Transfer');
            $table->string('transaction_id')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Trạng thái gửi email
            $table->enum('email_status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamp('email_sent_at')->nullable();

            $table->timestamps();

            $table->index('customer_email');
            $table->index('email_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
