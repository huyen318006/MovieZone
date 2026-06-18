<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'cinema_id',
        'name',
        'room_type',
        'total_seats',
        'status',
    ];

    public function cinema()
    {
        return $this->belongsTo(Cinema::class);
    }

    public function showtimes()
    {
        return $this->hasMany(Showtime::class);
    }

    public function seats()
    {
        return $this->hasMany(Seat::class);
    }

    public function upcomingShowtimes()
    {
        return $this->showtimes()
            ->where('start_time', '>', now())
            ->where('status', '!=', 'CANCELLED');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'ACTIVE');
    }
}
