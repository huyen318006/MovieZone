<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cinema extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'city',
        'district',
        'address',
        'hotline',
        'map_url',
        'status',
    ];

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function showtimes()
    {
        return $this->hasMany(Showtime::class);
    }
}
//banner_film
