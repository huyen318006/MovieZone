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
        Schema::create('booking_seats', function (Blueprint $table) {

            $table->id();

            $table->foreignId('booking_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('showtime_seat_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('seat_code', 20);

            $table->string('seat_type', 30);

            $table->decimal('price', 12, 2);

            $table->timestamps();

            $table->unique('showtime_seat_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_seats');
    }
};
