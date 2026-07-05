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
        Schema::create('movie_room_types', function (Blueprint $table) {

            $table->foreignId('movie_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('type_name_room');

            $table->primary([
                'movie_id',
                'type_name_room'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movie_room_types');
    }
};
