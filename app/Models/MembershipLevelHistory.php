<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipLevelHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'old_level_id',
        'new_level_id',
        'reason',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function oldLevel()
    {
        return $this->belongsTo(MembershipLevel::class, 'old_level_id');
    }

    public function newLevel()
    {
        return $this->belongsTo(MembershipLevel::class, 'new_level_id');
    }
}