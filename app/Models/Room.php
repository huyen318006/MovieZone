<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Booking;
use App\Models\ShowtimeSeat;

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

    /**
     * Suất chiếu chưa kết thúc và chưa bị hủy.
     */
    public function activeShowtimes()
    {
        return $this->showtimes()
            ->where('end_time', '>', now())
            ->where('status', '!=', 'CANCELLED');
    }

    /**
     * Suất chiếu đang diễn ra (start_time ≤ now ≤ end_time).
     */
    public function currentlyShowingShowtimes()
    {
        return $this->showtimes()
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->where('status', 'OPEN');
    }

    /**
     * Suất chiếu sắp bắt đầu trong 30 phút tới.
     */
    public function aboutToStartShowtimes()
    {
        return $this->showtimes()
            ->where('start_time', '>', now())
            ->where('start_time', '<=', now()->addMinutes(30))
            ->where('status', 'OPEN');
    }

    /**
     * Đếm số ghế đang bị giữ (HELD) trong các suất chiếu chưa kết thúc.
     */
    public function heldSeatsCount(): int
    {
        return ShowtimeSeat::whereIn('showtime_id', $this->activeShowtimes()->pluck('id'))
            ->where('status', 'HELD')
            ->where('held_until', '>', now())
            ->count();
    }

    /**
     * Đếm số ghế đã bán (SOLD) trong các suất chiếu chưa kết thúc.
     */
    public function soldSeatsCount(): int
    {
        return ShowtimeSeat::whereIn('showtime_id', $this->activeShowtimes()->pluck('id'))
            ->where('status', 'SOLD')
            ->count();
    }

    /**
     * Đếm số booking chưa hoàn tất liên quan đến phòng này.
     */
    public function activeBookingsCount(): int
    {
        return Booking::whereIn('showtime_id', $this->activeShowtimes()->pluck('id'))
            ->whereIn('status', ['PENDING', 'CONFIRMED'])
            ->count();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'ACTIVE');
    }
}
