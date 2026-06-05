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

            $table->enum('seat_type', [
                'NORMAL',
                'VIP',
                'COUPLE'
            ]);

            $table->enum('status', [
                'ACTIVE',
                'BROKEN',
                'LOCKED'
            ]);

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
