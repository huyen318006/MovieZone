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
        Schema::create('cinemas', function (Blueprint $table) {

            $table->id();

            $table->string('name');

            $table->string('city');

            $table->string('district')->nullable();

            $table->string('address');

            $table->string('hotline')->nullable();

            $table->string('map_url')->nullable();

            $table->enum('status', [
                'ACTIVE',
                'INACTIVE',
                'MAINTENANCE'
            ])->default('ACTIVE');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cinemas');
    }
};
