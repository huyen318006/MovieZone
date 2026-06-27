<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * UC-STAFF-01: Check-in Log Model.
 *
 * Lưu chi tiết mỗi lần scan/check-in (cả thành công và thất bại).
 * Dùng cho thống kê check-in theo suất chiếu và truy vết.
 */
class CheckInLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'ticket_id',
        'booking_id',
        'showtime_id',
        'staff_id',
        'scan_method',
        'qr_payload',
        'result',
        'failure_reason',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function showtime()
    {
        return $this->belongsTo(Showtime::class);
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}
