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
        Schema::create('promotions', function (Blueprint $table) {

            $table->id();

            $table->string('title');

            $table->text('description')->nullable();

            $table->string('banner_url')->nullable();

            $table->dateTime('start_date');

            $table->dateTime('end_date');

            $table->enum('status', [
                'ACTIVE',
                'INACTIVE',
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
        Schema::dropIfExists('promotions');
    }
};
