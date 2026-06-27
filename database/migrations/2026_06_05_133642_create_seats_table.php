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
        Schema::create('seats', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('row_label', 10);
            $table->integer('seat_number');
            $table->string('seat_code', 20);

            // Giữ nguyên bản gốc của cậu
            $table->enum('seat_type', [
                'STANDARD', // Đổi từ NORMAL thành STANDARD cho đồng bộ với bảng giá
                'VIP',
                'COUPLE'
            ]);

            $table->enum('status', [
                'ACTIVE',
                'BROKEN',
                'BLOCKED'
            ])->default('ACTIVE');

            // THÊM: Cột lưu giá tiền theo yêu cầu mới
            $table->decimal('price', 12, 2)->default(80000.00);

            $table->timestamps();

            $table->unique([
                'room_id',
                'seat_code'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};