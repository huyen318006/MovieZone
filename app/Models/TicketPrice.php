<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'cinema_id',
        'room_type',
        'seat_type',
        'day_type',
        'time_type',
        'price',
        'status',
    ];

    public function cinema()
    {
        return $this->belongsTo(Cinema::class);
    }
}
