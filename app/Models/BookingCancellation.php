<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingCancellation extends Model
{
    protected $fillable = ['booking_id', 'canceled_by', 'reason'];

    //Bản ghi hủy này thuộc về một Admin cụ thể trong bảng users
    public function admin()
    {
        return $this->belongsTo(User::class, 'canceled_by');
    }
}
