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
}
