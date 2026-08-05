<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'banner_url',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    /**
     * Accessor thông minh cho Banner Khuyến Mãi
     */
    public function getBannerAttribute(): string
    {
        $path = trim($this->banner_url ?? '');

        if (empty($path)) {
            return asset('assets/promo/1.jpg');
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'storage/') || str_starts_with($path, 'assets/') || str_starts_with($path, 'uploads/')) {
            return asset($path);
        }

        if (file_exists(public_path($path))) {
            return asset($path);
        }

        if (file_exists(public_path('storage/' . $path))) {
            return asset('storage/' . $path);
        }

        if (file_exists(public_path('uploads/' . $path))) {
            return asset('uploads/' . $path);
        }

        if (file_exists(public_path('assets/' . $path))) {
            return asset('assets/' . $path);
        }

        return asset('storage/' . $path);
    }
}
