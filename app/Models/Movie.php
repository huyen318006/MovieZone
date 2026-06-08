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
    ];

    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'movie_genres');
    }

    public function showtimes()
    {
        return $this->hasMany(Showtime::class);
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
}
