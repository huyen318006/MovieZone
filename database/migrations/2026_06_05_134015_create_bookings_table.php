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
        Schema::create('bookings', function (Blueprint $table) {

            $table->id();

            $table->string('booking_code', 50)->unique();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('showtime_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('customer_name', 255)->nullable();
            $table->string('customer_email', 255)->nullable();
            $table->string('customer_phone', 20)->nullable();

            $table->decimal('total_ticket_amount', 12, 2)->default(0);

            $table->decimal('total_combo_amount', 12, 2)->default(0);

            $table->decimal('discount_amount', 12, 2)->default(0);

            $table->decimal('final_amount', 12, 2)->default(0);

            $table->enum('status', [
                'PENDING',
                'PAID',
                'CANCELLED',
                'EXPIRED'
            ])->default('PENDING');

            $table->enum('payment_status', [
                'UNPAID',
                'PAID',
                'FAILED',
                'REFUNDED'
            ])->default('UNPAID');

            $table->timestamp('expired_at')->nullable();

            $table->timestamp('paid_at')->nullable();

            $table->string('canceled_reason')->nullable()->comment('Lý do hủy đơn');
            $table->foreignId('canceled_by')
                ->nullable()
                ->comment('ID của Admin thực hiện hủy')
                ->constrained('users') // Liên kết đến bảng users vì Admin và Khách hàng dùng chung bảng này
                ->nullOnDelete(); // Nếu user/admin đó bị xóa thì trường này chuyển về null chứ không xóa mất booking
                

            $table->timestamps();

            $table->index(['user_id']);
            $table->index(['showtime_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
