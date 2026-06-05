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
        Schema::create('booking_combos', function (Blueprint $table) {

            $table->id();

            $table->foreignId('booking_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('combo_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->integer('quantity');

            $table->decimal('unit_price', 12, 2);

            $table->decimal('total_price', 12, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_combos');
    }
};
