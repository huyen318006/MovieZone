<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingCancellation extends Model
{
    protected $fillable = [
        'type',           // CANCELLATION | LATE_PAYMENT
        'booking_id',
        'canceled_by',    // nullable khi hệ thống tự phát hiện (LATE_PAYMENT)
        'reason',
        'refund_status',  // null | pending_refund | refunded (chỉ dùng cho LATE_PAYMENT)
        'notes',          // JSON: thông tin giao dịch muộn
    ];

    protected function casts(): array
    {
        return [
            'notes' => 'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /** Admin đã hủy (nullable với LATE_PAYMENT do hệ thống tự phát hiện) */
    public function admin()
    {
        return $this->belongsTo(User::class, 'canceled_by');
    }

    /** Booking liên quan */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeCancellations($query)
    {
        return $query->where('type', 'CANCELLATION');
    }

    public function scopeLatePayments($query)
    {
        return $query->where('type', 'LATE_PAYMENT');
    }

    public function scopePendingRefund($query)
    {
        return $query->where('type', 'LATE_PAYMENT')
                     ->where('refund_status', 'pending_refund');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isLatePayment(): bool
    {
        return $this->type === 'LATE_PAYMENT';
    }

    public function isPendingRefund(): bool
    {
        return $this->refund_status === 'pending_refund';
    }

    public function markRefunded(): void
    {
        $this->update(['refund_status' => 'refunded']);
    }
}
