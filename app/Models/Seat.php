<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Seat extends Model
{
    use HasFactory, SoftDeletes; // Thêm SoftDeletes để đáp ứng A4 và BR04

    protected $fillable = [
        'room_id',
        'row_label',
        'seat_number',
        'seat_code',
        'seat_type',
        'status',
        'price' // Bổ sung price bị thiếu trong code gốc của cậu
    ];

    // BR01: Ghế phải thuộc một phòng chiếu cụ thể
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    // Liên kết với bảng trung gian khi tạo suất chiếu (BR05)
    public function showtimeSeats()
    {
        return $this->hasMany(ShowtimeSeat::class);
    }
}