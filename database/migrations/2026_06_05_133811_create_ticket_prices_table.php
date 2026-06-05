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
        Schema::create('ticket_prices', function (Blueprint $table) {

            $table->id();

            $table->foreignId('cinema_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('room_type', 50);

            $table->string('seat_type', 30);

            $table->string('day_type', 30);

            $table->string('time_type', 30);

            $table->decimal('price', 12, 2);

            $table->enum('status', [
                'ACTIVE',
                'INACTIVE'
            ])->default('ACTIVE');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_prices');
    }
};
