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
        Schema::create('showtimes', function (Blueprint $table) {

            $table->id();

            $table->foreignId('movie_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('cinema_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('room_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->dateTime('start_time');

            $table->dateTime('end_time');

            $table->string('format');

            $table->string('language_type');

            $table->enum('status', [
                'OPEN',
                'CLOSED',
                'CANCELLED'
            ]);

            $table->timestamps();

            $table->index([
                'room_id',
                'start_time'
            ]);
            $table->string('cancel_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('showtimes');
    }
};
