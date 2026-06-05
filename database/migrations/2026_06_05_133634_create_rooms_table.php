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
        Schema::create('rooms', function (Blueprint $table) {

            $table->id();

            $table->foreignId('cinema_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');

            $table->string('room_type');

            $table->integer('total_seats');

            $table->enum('status', [
                'ACTIVE',
                'INACTIVE',
                'MAINTENANCE'
            ]);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
