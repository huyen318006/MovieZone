<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price'
    ];

    public function booking()
    {
        return $this->belongsTo(
            Booking::class
        );
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}