<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;
    protected $fillable = [
        'booking_code',
        'user_id',
        'showtime_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'total_ticket_amount',
        'total_combo_amount',
        'discount_amount',
        'final_amount',
        'status',
        'payment_status',
        'hold_started_at',
        'expired_at',
        'paid_at'
    ];

    protected $casts = [
        'hold_started_at' => 'datetime',
        'expired_at'      => 'datetime',
        'paid_at'         => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
    public function showtime()
    {
        return $this->belongsTo(Showtime::class);
    }
    public function bookingCombos()
    {
        return $this->hasMany(BookingCombo::class);
    }

    public function bookingSeats()
    {
        return $this->hasMany(BookingSeat::class);
    }

    public function voucherUsages()
    {
        return $this->hasMany(VoucherUsage::class);
    }

    // Quan hệ 1-1: Một đơn hàng có thể có một thông tin hủy đơn
    public function cancellation()
    {
        return $this->hasOne(BookingCancellation::class, 'booking_id')
                    ->where('type', 'CANCELLATION');
    }

    // Quan hệ 1-1: Thanh toán muộn (khách chuyển khoản sau khi đơn hết hạn)
    public function latePaymentAlert()
    {
        return $this->hasOne(BookingCancellation::class, 'booking_id')
                    ->where('type', 'LATE_PAYMENT');
    }
    public function bookingProducts()
    {
        return $this->hasMany(BookingProduct::class);
    }
}
