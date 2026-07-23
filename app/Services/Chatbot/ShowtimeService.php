<?php

namespace App\Services\Chatbot;

use App\Models\Movie;
use App\Models\Showtime;
use Carbon\Carbon;

class ShowtimeService
{
    /**
     * Lấy danh sách ngày có suất chiếu cho 1 phim
     */
    public function getDatesForMovie(int $movieId)
    {
        $movie = Movie::find($movieId);

        if (!$movie) {
            return [
                'type' => 'text',
                'message' => 'Không tìm thấy phim này.',
                'buttons' => [['label' => '🔙 Quay lại menu', 'action' => 'menu']],
            ];
        }

        $dates = Showtime::where('movie_id', $movieId)
            ->where('status', 'OPEN')
            ->where('start_time', '>', now())
            ->selectRaw('DATE(start_time) as show_date')
            ->distinct()
            ->orderBy('show_date')
            ->take(7)
            ->pluck('show_date');

        if ($dates->isEmpty()) {
            return [
                'type' => 'text',
                'message' => "Hiện tại chưa có lịch chiếu cho phim \"{$movie->title}\" 😢",
                'buttons' => [
                    ['label' => '🎬 Xem phim khác', 'action' => 'showtime_select_movie'],
                    ['label' => '🔙 Quay lại menu', 'action' => 'menu'],
                ],
            ];
        }

        $buttons = $dates->map(function ($date) use ($movieId) {
            $carbon = Carbon::parse($date);
            $label = $carbon->isToday()
                ? '📅 Hôm nay (' . $carbon->format('d/m') . ')'
                : ($carbon->isTomorrow()
                    ? '📅 Ngày mai (' . $carbon->format('d/m') . ')'
                    : '📅 ' . $carbon->format('d/m/Y (l)'));

            return [
                'label' => $label,
                'action' => 'showtime_result',
                'value' => $movieId . '|' . $date,
            ];
        })->toArray();

        $buttons[] = ['label' => '🔙 Quay lại menu', 'action' => 'menu'];

        return [
            'type' => 'text',
            'message' => "📅 Chọn ngày xem phim \"{$movie->title}\":",
            'buttons' => $buttons,
        ];
    }

    /**
     * Lấy suất chiếu theo phim + ngày
     */
    public function getShowtimes(int $movieId, string $date)
    {
        $movie = Movie::find($movieId);

        if (!$movie) {
            return [
                'type' => 'text',
                'message' => 'Không tìm thấy phim này.',
                'buttons' => [['label' => '🔙 Quay lại menu', 'action' => 'menu']],
            ];
        }

        $showtimes = Showtime::with('room')
            ->where('movie_id', $movieId)
            ->where('status', 'OPEN')
            ->whereDate('start_time', $date)
            ->where('start_time', '>', now())
            ->orderBy('start_time')
            ->get();

        if ($showtimes->isEmpty()) {
            return [
                'type' => 'text',
                'message' => "Không còn suất chiếu nào cho phim \"{$movie->title}\" vào ngày " . Carbon::parse($date)->format('d/m/Y') . " 😢",
                'buttons' => [
                    ['label' => '📅 Chọn ngày khác', 'action' => 'showtime_for_movie', 'value' => $movieId],
                    ['label' => '🔙 Quay lại menu', 'action' => 'menu'],
                ],
            ];
        }

        $data = $showtimes->map(function ($st) {
            return [
                'start_time' => $st->start_time->format('H:i'),
                'end_time' => $st->end_time ? $st->end_time->format('H:i') : null,
                'room' => $st->room ? $st->room->name : 'N/A',
                'format' => $st->format ?? '2D',
            ];
        })->toArray();

        $displayDate = Carbon::parse($date);
        $dateLabel = $displayDate->isToday() ? 'Hôm nay' : ($displayDate->isTomorrow() ? 'Ngày mai' : $displayDate->format('d/m/Y'));

        return [
            'type' => 'showtime_list',
            'message' => "🕒 Lịch chiếu phim \"{$movie->title}\" - {$dateLabel}:",
            'data' => $data,
            'movie_info' => [
                'title'      => $movie->title,
                'poster_url' => $movie->poster_url,
                'detail_url' => route('movie.detail', $movie->slug),
            ],
            'buttons' => [
                ['label' => '📅 Chọn ngày khác', 'action' => 'showtime_for_movie', 'value' => $movieId],
                ['label' => '🎬 Xem phim khác', 'action' => 'showtime_select_movie'],
                ['label' => '🔙 Quay lại menu', 'action' => 'menu'],
            ],
        ];
    }
}
