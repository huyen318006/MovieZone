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
        Schema::create('banners', function (Blueprint $table) {

            $table->id();

            $table->string('image_url');

            $table->string('link_url')->nullable();

            $table->enum('position', [
                'HOME_TOP',
                'HOME_MIDDLE'
            ])->default('HOME_TOP');

            $table->dateTime('start_date')->nullable();

            $table->dateTime('end_date')->nullable();

            $table->enum('status', [
                'ACTIVE',
                'INACTIVE'
            ]);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
