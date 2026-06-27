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

    public function bookingSeat()
    {
        return $this->belongsTo(BookingSeat::class);
    }

    /**
     * Nhân viên thực hiện check-in (BR05)
     */
    public function checkedInByUser()
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }
}
