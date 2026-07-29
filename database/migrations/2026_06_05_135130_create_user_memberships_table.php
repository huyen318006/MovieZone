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
        Schema::create('user_memberships', function (Blueprint $table) {

            $table->foreignId('user_id')
                ->primary()
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('level_id')
                ->constrained('membership_levels');

            $table->integer('points')->default(0);

            $table->decimal('total_spent', 12, 2)
                ->default(0);

            $table->timestamp('level_expired_at')->nullable();

            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_memberships');
    }
};
