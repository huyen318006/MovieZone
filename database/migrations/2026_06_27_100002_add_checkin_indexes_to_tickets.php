<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * UC-STAFF-01: Indexes cho tra cứu ticket nhanh khi check-in.
     * Target: scan → preview < 300ms.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->index('qr_code', 'idx_tickets_qr_code');
            $table->index('status', 'idx_tickets_status');
            $table->index(['booking_id', 'status'], 'idx_tickets_booking_status');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('idx_tickets_qr_code');
            $table->dropIndex('idx_tickets_status');
            $table->dropIndex('idx_tickets_booking_status');
        });
    }
};
