<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Combo extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'image_url',
        'status'
    ];

    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'combo_items'
        )->withPivot('quantity');
    }

    public function bookingCombos()
    {
        return $this->hasMany(
            BookingCombo::class
        );
    }
}
