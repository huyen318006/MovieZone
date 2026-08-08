<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cập nhật enum status của bảng bookings.
 *
 * Hiện tại code hệ thống sử dụng các trạng thái PENDING_PAYMENT, PENDING_CASH_PAYMENT, COMPLETED
 * nhưng enum trong DB chỉ có ['PENDING','PAID','CANCELLED','EXPIRED'].
 * Migration này mở rộng enum để khớp với logic hiện tại.
 *
 * Sử dụng raw SQL ALTER MODIFY để bảo toàn dữ liệu status hiện có.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            // MySQL/MariaDB: ALTER MODIFY enum (bảo toàn dữ liệu)
            DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM(
                'PENDING',
                'PENDING_PAYMENT',
                'PENDING_CASH_PAYMENT',
                'PAID',
                'COMPLETED',
                'CANCELLED',
                'EXPIRED'
            ) NOT NULL DEFAULT 'PENDING'");
        } elseif ($driver === 'sqlite') {
            // SQLite không hỗ trợ ALTER enum trực tiếp.
            // Dùng cách drop + tạo lại (chỉ áp dụng nếu cần; dữ liệu status sẽ reset về default).
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('status');
            });
            Schema::table('bookings', function (Blueprint $table) {
                $table->enum('status', [
                    'PENDING',
                    'PENDING_PAYMENT',
                    'PENDING_CASH_PAYMENT',
                    'PAID',
                    'COMPLETED',
                    'CANCELLED',
                    'EXPIRED',
                ])->default('PENDING')->after('final_amount');
            });
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM(
                'PENDING',
                'PAID',
                'CANCELLED',
                'EXPIRED'
            ) NOT NULL DEFAULT 'PENDING'");
        } elseif ($driver === 'sqlite') {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('status');
            });
            Schema::table('bookings', function (Blueprint $table) {
                $table->enum('status', [
                    'PENDING',
                    'PAID',
                    'CANCELLED',
                    'EXPIRED',
                ])->default('PENDING')->after('final_amount');
            });
        }
    }
};
