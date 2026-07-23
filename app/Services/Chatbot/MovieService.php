<?php

namespace App\Services\Chatbot;

use App\Models\Movie;
use App\Models\Genre;

class MovieService
{
    /**
     * Lấy danh sách phim đang chiếu
     */
    public function getNowShowing()
    {
        $movies = Movie::with('genres')
            ->where('status', 'NOW_SHOWING')
            ->orderBy('release_date', 'desc')
            ->get();

        if ($movies->isEmpty()) {
            return [
                'type' => 'text',
                'message' => 'Hiện tại rạp chưa có phim nào đang chiếu. Bạn vui lòng quay lại sau nhé! 🎬',
                'buttons' => $this->backToMenuButtons(),
            ];
        }

        $data = $movies->map(fn($m) => $this->formatMovie($m))->toArray();

        return [
            'type' => 'movie_list',
            'message' => '🎬 Danh sách phim đang chiếu tại MovieZone:',
            'data' => $data,
            'buttons' => [
                ['label' => '📖 Xem chi tiết phim', 'action' => 'movie_detail_select'],
                ['label' => '🕒 Xem lịch chiếu', 'action' => 'showtime_select_movie'],
                ['label' => '🔙 Quay lại menu', 'action' => 'menu'],
            ],
        ];
    }

    /**
     * Tìm phim theo tên
     */
    public function searchByName(string $keyword)
    {
        $movies = Movie::with('genres')
            ->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('original_title', 'like', "%{$keyword}%");
            })
            ->take(10)
            ->get();

        if ($movies->isEmpty()) {
            return [
                'type' => 'text',
                'message' => "Không tìm thấy phim nào với từ khóa \"{$keyword}\" 😢. Bạn thử tìm với tên khác nhé!",
                'buttons' => [
                    ['label' => '🔍 Tìm lại', 'action' => 'search_by_name'],
                    ['label' => '🔙 Quay lại menu', 'action' => 'menu'],
                ],
            ];
        }

        $data = $movies->map(fn($m) => $this->formatMovie($m))->toArray();

        return [
            'type' => 'movie_list',
            'message' => "🔍 Kết quả tìm kiếm cho \"{$keyword}\":",
            'data' => $data,
            'buttons' => [
                ['label' => '📖 Xem chi tiết phim', 'action' => 'movie_detail_select'],
                ['label' => '🔙 Quay lại menu', 'action' => 'menu'],
            ],
        ];
    }

    /**
     * Lấy danh sách thể loại để hiển thị nút chọn
     */
    public function getGenreButtons()
    {
        $genres = Genre::all();
        $buttons = [];

        foreach ($genres as $genre) {
            // Danh sách emoji ngẫu nhiên hoặc mặc định cho thể loại
            $emoji = match($genre->name) {
                'Hành động' => '🎬',
                'Phiêu lưu' => '🌍',
                'Hài' => '😂',
                'Chính kịch' => '🎭',
                'Giả tưởng' => '🧙',
                'Kinh dị' => '👻',
                'Tình cảm' => '❤️',
                'Khoa học viễn tưởng' => '🚀',
                'Giật gân' => '😱',
                'Hoạt hình' => '🎨',
                default => '🎞️'
            };
            
            $buttons[] = ['label' => "{$emoji} {$genre->name}", 'action' => 'search_by_genre', 'value' => $genre->id];
        }

        $buttons[] = ['label' => '🔙 Quay lại menu', 'action' => 'menu'];

        return [
            'type' => 'text',
            'message' => '🎭 Bạn muốn xem phim thể loại nào?',
            'buttons' => $buttons,
        ];
    }

    /**
     * Tìm phim theo thể loại
     */
    public function searchByGenre(int $genreId)
    {
        $genre = Genre::find($genreId);

        if (!$genre) {
            return [
                'type' => 'text',
                'message' => 'Không tìm thấy thể loại này.',
                'buttons' => $this->backToMenuButtons(),
            ];
        }

        $movies = $genre->movies()->with('genres')
            ->where('status', 'NOW_SHOWING')
            ->get();

        if ($movies->isEmpty()) {
            return [
                'type' => 'text',
                'message' => "Hiện tại không có phim thể loại \"{$genre->name}\" đang chiếu 😢",
                'buttons' => [
                    ['label' => '🎭 Chọn thể loại khác', 'action' => 'search_by_genre_select'],
                    ['label' => '🔙 Quay lại menu', 'action' => 'menu'],
                ],
            ];
        }

        $data = $movies->map(fn($m) => $this->formatMovie($m))->toArray();

        return [
            'type' => 'movie_list',
            'message' => "🎬 Phim thể loại \"{$genre->name}\" đang chiếu:",
            'data' => $data,
            'buttons' => [
                ['label' => '📖 Xem chi tiết phim', 'action' => 'movie_detail_select'],
                ['label' => '🎭 Chọn thể loại khác', 'action' => 'search_by_genre_select'],
                ['label' => '🔙 Quay lại menu', 'action' => 'menu'],
            ],
        ];
    }

    /**
     * Xem chi tiết phim
     */
    public function getMovieDetail(int $movieId)
    {
        $movie = Movie::with('genres')->find($movieId);

        if (!$movie) {
            return [
                'type' => 'text',
                'message' => 'Không tìm thấy thông tin phim này.',
                'buttons' => $this->backToMenuButtons(),
            ];
        }

        return [
            'type' => 'movie_detail',
            'message' => "📖 Thông tin chi tiết phim:",
            'data' => [
                'id' => $movie->id,
                'title' => $movie->title,
                'original_title' => $movie->original_title,
                'description' => $movie->description,
                'duration_minutes' => $movie->duration_minutes,
                'age_rating' => $movie->age_rating,
                'language' => $movie->language,
                'subtitle' => $movie->subtitle,
                'country' => $movie->country,
                'director' => $movie->director,
                'cast' => $movie->cast,
                'genres' => $movie->genres->pluck('name')->toArray(),
                'poster_url' => $movie->poster_url,
                'trailer_url' => $movie->trailer_url,
                'rating' => $movie->rating,
                'status' => $movie->status,
                'release_date' => $movie->release_date,
                'slug' => $movie->slug,
                'detail_url' => route('movie.detail', $movie->slug),
            ],
            'buttons' => [
                ['label' => '🕒 Xem lịch chiếu phim này', 'action' => 'showtime_for_movie', 'value' => $movie->id],
                ['label' => '🎬 Xem phim khác', 'action' => 'now_showing'],
                ['label' => '🔙 Quay lại menu', 'action' => 'menu'],
            ],
        ];
    }

    /**
     * Lấy danh sách phim dạng nút chọn (cho chọn xem chi tiết / lịch chiếu)
     */
    public function getMovieSelectButtons(string $nextAction)
    {
        $movies = Movie::where('status', 'NOW_SHOWING')
            ->orderBy('title')
            ->get();

        $buttons = $movies->map(fn($m) => [
            'label' => "🎬 {$m->title}",
            'action' => $nextAction,
            'value' => $m->id,
        ])->toArray();

        $buttons[] = ['label' => '🔙 Quay lại menu', 'action' => 'menu'];

        return [
            'type' => 'text',
            'message' => '👇 Chọn phim bạn muốn xem:',
            'buttons' => $buttons,
        ];
    }

    /**
     * Format movie data cho response
     */
    private function formatMovie(Movie $movie): array
    {
        return [
            'id' => $movie->id,
            'title' => $movie->title,
            'duration_minutes' => $movie->duration_minutes,
            'age_rating' => $movie->age_rating,
            'genres' => $movie->genres->pluck('name')->toArray(),
            'poster_url' => $movie->poster_url,
            'description' => \Str::limit($movie->description, 100),
            'rating' => $movie->rating,
            'slug' => $movie->slug,
            'detail_url' => route('movie.detail', $movie->slug),
        ];
    }

    private function backToMenuButtons(): array
    {
        return [
            ['label' => '🔙 Quay lại menu', 'action' => 'menu'],
        ];
    }
}
