<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'row_label',
        'seat_number',
        'seat_code',
        'seat_type',
        'status',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function showtimeSeats()
    {
        return $this->hasMany(ShowtimeSeat::class);
    }
}
