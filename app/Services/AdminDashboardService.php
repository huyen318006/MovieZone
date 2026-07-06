<?php

namespace App\Services;

use Illuminate\Support\Collection;

class AdminDashboardService
{
    /**
     * Trạng thái rỗng dùng cho commit nền và fallback khi dashboard lỗi.
     */
    public function emptyOverview(): array
    {
        return [
            'filters' => [
                'start_date' => null,
                'end_date' => null,
                'cinema_id' => null,
                'movie_id' => null,
            ],
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