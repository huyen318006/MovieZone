<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_products', function (Blueprint $table) {

            $table->id();

            // Đơn đặt vé
            $table->foreignId('booking_id')
                ->constrained()
                ->cascadeOnDelete();

            // Sản phẩm
            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();

            // Giá sản phẩm tại thời điểm đặt vé
            $table->decimal('unit_price', 12, 2);

            // Số lượng sản phẩm đã bán
            $table->integer('quantity');

            // Tổng tiền
            $table->decimal('total_price', 12, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_products');
    }
};