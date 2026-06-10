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
        Schema::create('sepay_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique();
            $table->string('package_id');
            $table->string('package_name');
            $table->unsignedInteger('amount');
            $table->string('status')->default('pending'); // pending, paid, expired
            $table->string('transaction_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sepay_orders');
    }
};
