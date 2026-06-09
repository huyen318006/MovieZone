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
        Schema::create('movies', function (Blueprint $table) {

            $table->id();

            $table->string('title');

            $table->string('slug')->unique();

            $table->string('original_title')->nullable();

            $table->text('description')->nullable();

            $table->integer('duration_minutes');

            $table->date('release_date');

            $table->date('end_date')->nullable();

            $table->string('age_rating', 20);

            $table->string('language', 100);

            $table->string('subtitle', 100)->nullable();

            $table->string('country', 100)->nullable();

            $table->string('director')->nullable();

            $table->longText('cast')->nullable();

            $table->string('poster_url')->nullable();

            $table->string('banner_url')->nullable();

            $table->string('trailer_url')->nullable();

            $table->decimal('rating', 3, 2)->default(0.00);
            
            $table->enum('status', [
                'COMING_SOON',
                'NOW_SHOWING',
                'ENDED',
                'HIDDEN'
            ])->default('COMING_SOON');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
