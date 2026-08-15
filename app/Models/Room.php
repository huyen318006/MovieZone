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

    // 🏢 Room thuộc về Cinema
    public function cinema()
    {
        return $this->belongsTo(Cinema::class);
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
     * Đếm số ghế đang bị giữ (booking PENDING chưa thanh toán) trong các suất chiếu chưa kết thúc.
     * H2-FIX: Hệ thống dùng Cache để giữ ghế, showtime_seats.status không được cập nhật.
     * Nên đếm từ booking_seats với booking đang PENDING.
     */
    public function heldSeatsCount(): int
    {
        return \DB::table('booking_seats')
            ->join('bookings', 'bookings.id', '=', 'booking_seats.booking_id')
            ->whereIn('bookings.showtime_id', $this->activeShowtimes()->pluck('id'))
            ->whereIn('bookings.status', ['PENDING', 'PENDING_PAYMENT', 'PENDING_CASH_PAYMENT'])
            ->count();
    }

    /**
     * Đếm số ghế đã bán (booking PAID) trong các suất chiếu chưa kết thúc.
     * H3-FIX: Tương tự H2, đếm từ booking_seats thay vì showtime_seats.status.
     */
    public function soldSeatsCount(): int
    {
        return \DB::table('booking_seats')
            ->join('bookings', 'bookings.id', '=', 'booking_seats.booking_id')
            ->whereIn('bookings.showtime_id', $this->activeShowtimes()->pluck('id'))
            ->where('bookings.status', 'PAID')
            ->count();
    }

    /**
     * Đếm số booking chưa hoàn tất liên quan đến phòng này.
     * H4-FIX: Thay 'CONFIRMED' (không tồn tại) bằng các status thực tế của hệ thống.
     */
    public function activeBookingsCount(): int
    {
        return Booking::whereIn('showtime_id', $this->activeShowtimes()->pluck('id'))
            ->whereIn('status', ['PENDING', 'PENDING_PAYMENT', 'PENDING_CASH_PAYMENT', 'PAID'])
            ->count();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'ACTIVE');
    }
}
