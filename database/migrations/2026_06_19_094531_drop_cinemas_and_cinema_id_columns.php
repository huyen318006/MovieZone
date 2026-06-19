<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Xóa cột cinema_id khỏi rooms, showtimes, ticket_prices và drop bảng cinemas.
     * Hệ thống chỉ quản lý 1 rạp duy nhất nên không cần bảng cinemas.
     */
    public function up(): void
    {
        // 1. Drop cinema_id FK + column từ rooms
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropForeign(['cinema_id']);
            $table->dropColumn('cinema_id');
        });

        // 2. Drop cinema_id FK + column từ showtimes
        Schema::table('showtimes', function (Blueprint $table) {
            $table->dropForeign(['cinema_id']);
            $table->dropColumn('cinema_id');
        });

        // 3. Drop cinema_id FK + column từ ticket_prices
        Schema::table('ticket_prices', function (Blueprint $table) {
            $table->dropForeign(['cinema_id']);
            $table->dropColumn('cinema_id');
        });

        // 4. Drop bảng cinemas
        Schema::dropIfExists('cinemas');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tạo lại bảng cinemas
        Schema::create('cinemas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('city');
            $table->string('district')->nullable();
            $table->string('address');
            $table->string('hotline')->nullable();
            $table->string('map_url')->nullable();
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'MAINTENANCE'])->default('ACTIVE');
            $table->timestamps();
        });

        // Thêm lại cinema_id vào rooms
        Schema::table('rooms', function (Blueprint $table) {
            $table->foreignId('cinema_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();
        });

        // Thêm lại cinema_id vào showtimes
        Schema::table('showtimes', function (Blueprint $table) {
            $table->foreignId('cinema_id')
                ->nullable()
                ->after('movie_id')
                ->constrained()
                ->cascadeOnDelete();
        });

        // Thêm lại cinema_id vào ticket_prices
        Schema::table('ticket_prices', function (Blueprint $table) {
            $table->foreignId('cinema_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();
        });
    }
};
