<?php

namespace App\Services;

use App\Models\Cinema;
use App\Models\Movie;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AdminDashboardService
{
    public function normalizeFilters(array $filters = []): array
    {
        $startDate = !empty($filters['start_date'])
            ? Carbon::parse($filters['start_date'])->startOfDay()
            : now()->startOfMonth();

        $endDate = !empty($filters['end_date'])
            ? Carbon::parse($filters['end_date'])->endOfDay()
            : now()->endOfDay();

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'cinema_id' => $filters['cinema_id'] ?? null,
            'movie_id' => $filters['movie_id'] ?? null,
        ];
    }

    public function filterOptions(): array
    {
        return [
            'cinemas' => Cinema::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'movies' => Movie::query()
                ->orderBy('title')
                ->get(['id', 'title']),
        ];
    }

    /**
     * Trạng thái rỗng dùng cho commit nền và fallback khi dashboard lỗi.
     */
    public function emptyOverview(array $filters = []): array
    {
        $filters = $filters ?: $this->normalizeFilters();

        return [
            'filters' => $filters,
            'metrics' => [
                'revenue' => 0,
                'sold_tickets' => 0,
                'occupancy_rate' => 0,
                'new_bookings' => 0,
            ],
            'top_movies' => collect(),
            'recent_bookings' => collect(),
            'room_performance' => collect(),
            'least_effective_room' => null,
        ];
    }
}