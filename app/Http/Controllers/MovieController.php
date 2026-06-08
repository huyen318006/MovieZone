<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use App\Models\Genre;
use App\Models\Movie;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only([
            'keyword',
            'genre',
            'age_rating',
            'language',
            'cinema',
            'status',
        ]);

        $allowedStatuses = [
            'NOW_SHOWING',
            'COMING_SOON',
            'ENDED',
        ];

        try {
            $moviesQuery = Movie::query()
                ->visible()
                ->with('genres')
                ->search($filters['keyword'] ?? null)
                ->when($filters['genre'] ?? null, function ($query, $genreId) {
                    $query->whereHas('genres', function ($genreQuery) use ($genreId) {
                        $genreQuery->where('genres.id', $genreId);
                    });
                })
                ->when($filters['age_rating'] ?? null, function ($query, $ageRating) {
                    $query->where('age_rating', $ageRating);
                })
                ->when($filters['language'] ?? null, function ($query, $language) {
                    $query->where('language', $language);
                })
                ->when($filters['cinema'] ?? null, function ($query, $cinemaId) {
                    $query->whereHas('showtimes', function ($showtimeQuery) use ($cinemaId) {
                        $showtimeQuery->where('cinema_id', $cinemaId);
                    });
                })
                ->when($filters['status'] ?? null, function ($query, $status) use ($allowedStatuses) {
                    if (in_array($status, $allowedStatuses, true)) {
                        $query->where('status', $status);
                    }
                })
                ->latest('release_date');

            $movies = $moviesQuery->paginate(12)->withQueryString();

            $genres = Genre::query()->orderBy('name')->get();
            $cinemas = Cinema::query()
                ->where('status', 'ACTIVE')
                ->orderBy('name')
                ->get();
            $ageRatings = Movie::query()
                ->visible()
                ->whereNotNull('age_rating')
                ->distinct()
                ->orderBy('age_rating')
                ->pluck('age_rating');
            $languages = Movie::query()
                ->visible()
                ->whereNotNull('language')
                ->distinct()
                ->orderBy('language')
                ->pluck('language');

            return view('movie.index', compact(
                'movies',
                'genres',
                'cinemas',
                'ageRatings',
                'languages',
                'filters',
                'allowedStatuses'
            ))->with('loadError', null);
        } catch (\Throwable $error) {
            report($error);

            return view('movie.index', [
                'movies' => collect(),
                'genres' => collect(),
                'cinemas' => collect(),
                'ageRatings' => collect(),
                'languages' => collect(),
                'filters' => $filters,
                'allowedStatuses' => $allowedStatuses,
                'loadError' => 'Không thể tải danh sách phim. Vui lòng thử lại.',
            ]);
        }
    }
}
