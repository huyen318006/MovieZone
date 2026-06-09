<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;
    protected $fillable = [
        'ticket_code',
        'booking_id',
        'booking_seat_id',
        'qr_code',
        'status',
        'checked_in_at',
        'checked_in_by'
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
