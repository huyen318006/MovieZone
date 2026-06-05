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
        Schema::create('tickets', function (Blueprint $table) {

            $table->id();

            $table->string('ticket_code', 50)->unique();

            $table->foreignId('booking_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('booking_seat_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('qr_code')->nullable();

            $table->enum('status', [
                'UNUSED',
                'USED',
                'CANCELLED'
            ])->default('UNUSED');

            $table->timestamp('checked_in_at')->nullable();

            $table->foreignId('checked_in_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
