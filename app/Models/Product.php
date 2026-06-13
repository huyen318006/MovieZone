<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'description',
        'image_url',
        'price',
        'status'
    ];

    public function combos()
    {
        return $this->belongsToMany(
            Combo::class,
            'combo_items'
        )->withPivot('quantity');
    }
}
