<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'original_title',
        'description',
        'duration_minutes',
        'release_date',
        'end_date',
        'age_rating',
        'language',
        'subtitle',
        'country',
        'director',
        'cast',
        'poster_url',
        'banner_url',
        'trailer_url',
        'status',
        'rating',
    ];

    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'movie_genres');
    }
    public function movieroomtytle()
    {
        return $this->belongsToMany(MovieRoomTytle::class, 'movie_room_tytle', 'movie_id', 'room_id');
    }

    public function showtimes()
    {
        return $this->hasMany(Showtime::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews()
    {
        return $this->hasMany(Review::class)->where('status', 'APPROVED');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('status', '!=', 'HIDDEN');
    }

    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (blank($keyword)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($keyword) {
            $query->where('title', 'like', "%{$keyword}%")
                ->orWhere('original_title', 'like', "%{$keyword}%");
        });
    }

    //tính lại điểm trung bình của phim
    public function recalculateRating()
    {
        $avg = $this->reviews()
            ->where('status', 'APPROVED')
            ->avg('rating');

        $this->rating = $avg ? round($avg, 2) : 0.00;
        $this->save();
    }

    /**
     * Accessor thông minh cho Poster Phim (hỗ trợ cả storage/ và assets/)
     */
    public function getPosterAttribute(): string
    {
        $path = trim($this->poster_url ?? '');

        if (empty($path)) {
            return asset('assets/movies/dune.jpg');
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            return asset(ltrim($path, '/'));
        }

        if (str_starts_with($path, 'storage/') || str_starts_with($path, 'assets/') || str_starts_with($path, 'uploads/')) {
            return asset($path);
        }

        if (file_exists(public_path($path))) {
            return asset($path);
        }

        if (file_exists(public_path('storage/' . $path))) {
            return asset('storage/' . $path);
        }

        if (file_exists(public_path('assets/' . $path))) {
            return asset('assets/' . $path);
        }

        return asset('storage/' . $path);
    }

    /**
     * Accessor thông minh cho Banner Phim
     */
    public function getBannerAttribute(): string
    {
        $path = trim($this->banner_url ?? '');

        if (empty($path)) {
            return asset('assets/hero/avatar2.jpg');
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            return asset(ltrim($path, '/'));
        }

        if (str_starts_with($path, 'storage/') || str_starts_with($path, 'assets/')) {
            return asset($path);
        }

        if (file_exists(public_path($path))) {
            return asset($path);
        }

        if (file_exists(public_path('storage/' . $path))) {
            return asset('storage/' . $path);
        }

        if (file_exists(public_path('assets/' . $path))) {
            return asset('assets/' . $path);
        }

        return asset('storage/' . ltrim($path, '/'));
    }
}
