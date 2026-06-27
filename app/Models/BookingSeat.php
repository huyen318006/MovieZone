<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingSeat extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'showtime_seat_id',
        'seat_code',
        'seat_type',
        'price',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function showtimeSeat()
    {
        return $this->belongsTo(ShowtimeSeat::class);
    }

    public function ticket()
    {
        return $this->hasOne(Ticket::class);
    }
}
