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
        Schema::create('vouchers', function (Blueprint $table) {

            $table->id();

            $table->string('code')->unique();

            $table->enum('discount_type', [
                'PERCENT',
                'FIXED'
            ]);

            $table->decimal('discount_value', 12, 2);

            $table->decimal('max_discount', 12, 2)->nullable();

            $table->decimal('min_order_amount', 12, 2)->default(0);

            // usage_limit: -1 => unlimited, >=1 => limited
            $table->integer('usage_limit')->default(-1);

            $table->integer('usage_per_user')->default(1);

            $table->dateTime('start_date');

            $table->dateTime('end_date');

            $table->enum('status', [
                'ACTIVE',
                'DISABLED',
                'EXPIRED'
            ]);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
