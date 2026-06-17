<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'entity_name',
        'entity_id',
        'old_value',
        'new_value',
        'created_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
