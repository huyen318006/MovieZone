<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Payment;
use App\Models\ShowtimeSeat;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    private array $excludedBookingStatuses = ['CANCELLED', 'EXPIRED', 'FAILED'];

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

    public function getOverview(array $filters = []): array
    {
        $filters = $filters ?: $this->normalizeFilters();

        return [
            'filters' => $filters,
            'metrics' => [
                'revenue' => $this->revenue($filters),
                'sold_tickets' => $this->soldTickets($filters),
                'occupancy_rate' => $this->occupancyRate($filters),
                'new_bookings' => $this->newBookings($filters),
            ],
            'top_movies' => collect(),
            'recent_bookings' => collect(),
            'room_performance' => collect(),
            'least_effective_room' => null,
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

    private function revenue(array $filters): float
    {
        return (float) Payment::query()
            ->join('bookings', 'payments.booking_id', '=', 'bookings.id')
            ->join('showtimes', 'bookings.showtime_id', '=', 'showtimes.id')
            ->where('payments.status', 'SUCCESS')
            ->whereNotIn('bookings.status', $this->excludedBookingStatuses)
            ->whereBetween(DB::raw('COALESCE(payments.paid_at, payments.created_at)'), [$filters['start_date'], $filters['end_date']])
            ->when($filters['cinema_id'], fn ($query) => $query->where('showtimes.cinema_id', $filters['cinema_id']))
            ->when($filters['movie_id'], fn ($query) => $query->where('showtimes.movie_id', $filters['movie_id']))
            ->sum('payments.amount');
    }

    private function soldTickets(array $filters): int
    {
        return (int) DB::table('booking_seats')
            ->join('bookings', 'booking_seats.booking_id', '=', 'bookings.id')
            ->join('showtimes', 'bookings.showtime_id', '=', 'showtimes.id')
            ->leftJoin('payments', 'payments.booking_id', '=', 'bookings.id')
            ->whereNotIn('bookings.status', $this->excludedBookingStatuses)
            ->where(function ($query) {
                $query->where('bookings.status', 'PAID')
                    ->orWhere('bookings.payment_status', 'PAID')
                    ->orWhere('payments.status', 'SUCCESS');
            })
            ->whereBetween('bookings.created_at', [$filters['start_date'], $filters['end_date']])
            ->when($filters['cinema_id'], fn ($query) => $query->where('showtimes.cinema_id', $filters['cinema_id']))
            ->when($filters['movie_id'], fn ($query) => $query->where('showtimes.movie_id', $filters['movie_id']))
            ->count();
    }

    private function newBookings(array $filters): int
    {
        return (int) $this->filteredBookings($filters)
            ->whereBetween('bookings.created_at', [$filters['start_date'], $filters['end_date']])
            ->count();
    }

    private function occupancyRate(array $filters): float
    {
        $totalSeats = ShowtimeSeat::query()
            ->join('showtimes', 'showtime_seats.showtime_id', '=', 'showtimes.id')
            ->whereNotIn('showtime_seats.status', ['BLOCKED', 'LOCKED', 'BROKEN'])
            ->whereBetween('showtimes.start_time', [$filters['start_date'], $filters['end_date']])
            ->when($filters['cinema_id'], fn ($query) => $query->where('showtimes.cinema_id', $filters['cinema_id']))
            ->when($filters['movie_id'], fn ($query) => $query->where('showtimes.movie_id', $filters['movie_id']))
            ->count();

        if ($totalSeats === 0) {
            return 0.0;
        }

        $soldSeats = $this->soldTickets($filters);

        return round(($soldSeats / $totalSeats) * 100, 1);
    }

    private function filteredBookings(array $filters): Builder
    {
        return Booking::query()
            ->join('showtimes', 'bookings.showtime_id', '=', 'showtimes.id')
            ->whereNotIn('bookings.status', $this->excludedBookingStatuses)
            ->when($filters['cinema_id'], fn ($query) => $query->where('showtimes.cinema_id', $filters['cinema_id']))
            ->when($filters['movie_id'], fn ($query) => $query->where('showtimes.movie_id', $filters['movie_id']));
    }
}