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

    public function getImageAttribute(): string
    {
        $path = trim($this->image_url ?? '');

        if (empty($path)) {
            return asset('assets/promo/1.jpg');
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        return asset('storage/' . ltrim($path, '/'));
    }

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
