<?php

namespace App\Http\Controllers;


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

                ->when($filters['status'] ?? null, function ($query, $status) use ($allowedStatuses) {
                    if (in_array($status, $allowedStatuses, true)) {
                        $query->where('status', $status);
                    }
                })
                ->latest('release_date');

            $movies = $moviesQuery->paginate(12)->withQueryString();

            $genres = Genre::query()->orderBy('name')->get();

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
                'ageRatings' => collect(),
                'languages' => collect(),
                'filters' => $filters,
                'allowedStatuses' => $allowedStatuses,
                'loadError' => 'Không thể tải danh sách phim. Vui lòng thử lại.',
            ]);
        }
    }
    public function show(string $slug)
    {
        $movie = Movie::query()
            ->visible()
            ->with([
                'genres',
                'approvedReviews.user',
                'showtimes' => function ($query) {
                    $query->where('status', 'OPEN')
                        ->where('start_time', '>=', now())
                        ->with(['room'])
                        ->orderBy('start_time')
                        ->limit(8);
                },
            ])
            ->where('slug', $slug)
            ->first();

        if (!$movie) {
            return redirect()
                ->route('movies')
                ->with('success', 'Phim không khả dụng');
        }

        $trailerEmbedUrl = $this->toYoutubeEmbedUrl($movie->trailer_url);

        return view('movie.detail', compact('movie', 'trailerEmbedUrl'));
    }

    private function toYoutubeEmbedUrl(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        if (preg_match('/youtu\.be\/([^?&]+)/', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }

        if (preg_match('/youtube\.com\/watch\?v=([^?&]+)/', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }

        if (str_contains($url, 'youtube.com/embed/')) {
            return $url;
        }

        return null;
    }
}
