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
        'total_ticket_amount',
        'total_combo_amount',
        'discount_amount',
        'final_amount',
        'status',
        'payment_status',
        'expired_at',
        'paid_at'
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
}
