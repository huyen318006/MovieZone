<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;
    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'max_discount',
        'min_order_amount',
        'usage_limit',
        'usage_per_user',
        'start_date',
        'end_date',
        'status'
    ];
    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];
    public function usages()
    {
        return $this->hasMany(VoucherUsage::class);
    }
}
