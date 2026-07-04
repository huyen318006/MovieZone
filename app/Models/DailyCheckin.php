<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyCheckin extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'user_id',
        'checkin_date',
        'reward_coin',
    ];

    protected $casts = [
        'checkin_date' => 'date',
    ];

    /**
     * Điểm danh thuộc về một người dùng
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
