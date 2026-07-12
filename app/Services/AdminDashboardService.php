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
            : now()->startOfDay();

        $endDate = !empty($filters['end_date'])
            ? Carbon::parse($filters['end_date'])->endOfDay()
            : now()->addWeek()->endOfDay();

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
            'top_movies' => $this->topMovies($filters),
            'recent_bookings' => $this->recentBookings($filters),
            'room_performance' => $this->roomPerformance($filters),
            'least_effective_room' => $this->leastEffectiveRoom($filters),
            'booking_status_stats' => $this->bookingStatusStats($filters),
            'revenue_breakdown' => $this->revenueBreakdown($filters),
            'voucher_stats' => $this->voucherStats($filters),
            'showtime_performance' => $this->showtimePerformance($filters),
            'time_slot_performance' => $this->timeSlotPerformance($filters),
            'combo_stats' => $this->comboStats($filters),
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
            'booking_status_stats' => [
                'total' => 0,
                'paid' => 0,
                'pending' => 0,
                'cancelled' => 0,
                'expired' => 0,
                'success_rate' => 0,
            ],
            'revenue_breakdown' => [
                'ticket_revenue' => 0,
                'combo_revenue' => 0,
                'product_revenue' => 0,
                'concession_revenue' => 0,
                'total_revenue' => 0,
            ],
            'voucher_stats' => [
                'usage_count' => 0,
                'discount_amount' => 0,
                'top_vouchers' => collect(),
            ],
            'showtime_performance' => collect(),
            'time_slot_performance' => collect(),
            'combo_stats' => [
                'top_combos' => collect(),
                'combo_quantity' => 0,
                'combo_revenue' => 0,
                'booking_with_combo_rate' => 0,
            ],
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

    private function topMovies(array $filters): Collection
    {
        return DB::table('booking_seats')
            ->join('bookings', 'booking_seats.booking_id', '=', 'bookings.id')
            ->join('showtimes', 'bookings.showtime_id', '=', 'showtimes.id')
            ->join('movies', 'showtimes.movie_id', '=', 'movies.id')
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
            ->groupBy('movies.id', 'movies.title')
            ->select([
                'movies.id',
                'movies.title',
                DB::raw('COUNT(booking_seats.id) as sold_tickets'),
                DB::raw('COALESCE(SUM(booking_seats.price), 0) as ticket_revenue'),
            ])
            ->orderByDesc('sold_tickets')
            ->limit(5)
            ->get();
    }

    private function recentBookings(array $filters): Collection
    {
        return Booking::query()
            ->with([
                'user:id,name,email',
                'showtime:id,movie_id,cinema_id,start_time',
                'showtime.movie:id,title',
                'showtime.cinema:id,name',
            ])
            ->whereHas('showtime', function ($query) use ($filters) {
                $query
                    ->when($filters['cinema_id'], fn ($query) => $query->where('cinema_id', $filters['cinema_id']))
                    ->when($filters['movie_id'], fn ($query) => $query->where('movie_id', $filters['movie_id']));
            })
            ->whereNotIn('status', $this->excludedBookingStatuses)
            ->whereBetween('created_at', [$filters['start_date'], $filters['end_date']])
            ->latest('created_at')
            ->limit(8)
            ->get();
    }

    private function roomPerformance(array $filters): Collection
    {
        $totalSeatsSubquery = DB::table('showtime_seats')
            ->join('showtimes', 'showtime_seats.showtime_id', '=', 'showtimes.id')
            ->join('rooms', 'showtimes.room_id', '=', 'rooms.id')
            ->whereNotIn('showtime_seats.status', ['BLOCKED', 'LOCKED', 'BROKEN'])
            ->whereBetween('showtimes.start_time', [$filters['start_date'], $filters['end_date']])
            ->when($filters['cinema_id'], fn ($query) => $query->where('showtimes.cinema_id', $filters['cinema_id']))
            ->when($filters['movie_id'], fn ($query) => $query->where('showtimes.movie_id', $filters['movie_id']))
            ->groupBy('rooms.id', 'rooms.name')
            ->select([
                'rooms.id as room_id',
                'rooms.name as room_name',
                DB::raw('COUNT(DISTINCT showtimes.id) as showtime_count'),
                DB::raw('COUNT(showtime_seats.id) as total_seats'),
            ]);

        $soldSeatsSubquery = DB::table('booking_seats')
            ->join('bookings', 'booking_seats.booking_id', '=', 'bookings.id')
            ->join('showtimes', 'bookings.showtime_id', '=', 'showtimes.id')
            ->join('rooms', 'showtimes.room_id', '=', 'rooms.id')
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
            ->groupBy('rooms.id')
            ->select([
                'rooms.id as room_id',
                DB::raw('COUNT(booking_seats.id) as sold_seats'),
            ]);

        return DB::query()
            ->fromSub($totalSeatsSubquery, 'room_totals')
            ->leftJoinSub($soldSeatsSubquery, 'room_sales', 'room_totals.room_id', '=', 'room_sales.room_id')
            ->select([
                'room_totals.room_id',
                'room_totals.room_name',
                'room_totals.showtime_count',
                'room_totals.total_seats',
                DB::raw('COALESCE(room_sales.sold_seats, 0) as sold_seats'),
                DB::raw('ROUND((COALESCE(room_sales.sold_seats, 0) / NULLIF(room_totals.total_seats, 0)) * 100, 1) as occupancy_rate'),
            ])
            ->orderBy('occupancy_rate')
            ->orderByDesc('showtime_count')
            ->limit(5)
            ->get();
    }

    private function leastEffectiveRoom(array $filters): ?object
    {
        return $this->roomPerformance($filters)->first();
    }

    private function bookingStatusStats(array $filters): array
    {
        $rows = Booking::query()
            ->join('showtimes', 'bookings.showtime_id', '=', 'showtimes.id')
            ->whereBetween('bookings.created_at', [$filters['start_date'], $filters['end_date']])
            ->when($filters['cinema_id'], fn ($query) => $query->where('showtimes.cinema_id', $filters['cinema_id']))
            ->when($filters['movie_id'], fn ($query) => $query->where('showtimes.movie_id', $filters['movie_id']))
            ->selectRaw("\n                COUNT(*) as total,\n                SUM(CASE WHEN bookings.status = 'PAID' OR bookings.payment_status = 'PAID' THEN 1 ELSE 0 END) as paid,\n                SUM(CASE WHEN bookings.status IN ('PENDING', 'PENDING_PAYMENT', 'PENDING_CASH_PAYMENT') OR bookings.payment_status = 'UNPAID' THEN 1 ELSE 0 END) as pending,\n                SUM(CASE WHEN bookings.status = 'CANCELLED' THEN 1 ELSE 0 END) as cancelled,\n                SUM(CASE WHEN bookings.status = 'EXPIRED' THEN 1 ELSE 0 END) as expired\n            ")
            ->first();

        $total = (int) ($rows->total ?? 0);
        $paid = (int) ($rows->paid ?? 0);

        return [
            'total' => $total,
            'paid' => $paid,
            'pending' => (int) ($rows->pending ?? 0),
            'cancelled' => (int) ($rows->cancelled ?? 0),
            'expired' => (int) ($rows->expired ?? 0),
            'success_rate' => $total > 0 ? round(($paid / $total) * 100, 1) : 0,
        ];
    }

    private function revenueBreakdown(array $filters): array
    {
        $ticketRevenue = (float) DB::table('booking_seats')
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
            ->sum('booking_seats.price');

        $comboRevenue = (float) DB::table('booking_combos')
            ->join('bookings', 'booking_combos.booking_id', '=', 'bookings.id')
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
            ->sum('booking_combos.total_price');

        $productRevenue = (float) DB::table('booking_products')
            ->join('bookings', 'booking_products.booking_id', '=', 'bookings.id')
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
            ->sum('booking_products.total_price');

        return [
            'ticket_revenue' => $ticketRevenue,
            'combo_revenue' => $comboRevenue,
            'product_revenue' => $productRevenue,
            'concession_revenue' => $comboRevenue + $productRevenue,
            'total_revenue' => $ticketRevenue + $comboRevenue + $productRevenue,
        ];
    }

    private function voucherStats(array $filters): array
    {
        $baseQuery = DB::table('voucher_usages')
            ->join('vouchers', 'voucher_usages.voucher_id', '=', 'vouchers.id')
            ->join('bookings', 'voucher_usages.booking_id', '=', 'bookings.id')
            ->join('showtimes', 'bookings.showtime_id', '=', 'showtimes.id')
            ->whereNotIn('bookings.status', $this->excludedBookingStatuses)
            ->whereBetween('voucher_usages.used_at', [$filters['start_date'], $filters['end_date']])
            ->when($filters['cinema_id'], fn ($query) => $query->where('showtimes.cinema_id', $filters['cinema_id']))
            ->when($filters['movie_id'], fn ($query) => $query->where('showtimes.movie_id', $filters['movie_id']));

        $summary = (clone $baseQuery)
            ->selectRaw('COUNT(voucher_usages.id) as usage_count, COALESCE(SUM(bookings.discount_amount), 0) as discount_amount')
            ->first();

        $topVouchers = (clone $baseQuery)
            ->groupBy('vouchers.id', 'vouchers.code')
            ->select([
                'vouchers.id',
                'vouchers.code',
                DB::raw('COUNT(voucher_usages.id) as usage_count'),
                DB::raw('COALESCE(SUM(bookings.discount_amount), 0) as discount_amount'),
            ])
            ->orderByDesc('usage_count')
            ->limit(5)
            ->get();

        return [
            'usage_count' => (int) ($summary->usage_count ?? 0),
            'discount_amount' => (float) ($summary->discount_amount ?? 0),
            'top_vouchers' => $topVouchers,
        ];
    }

    private function showtimePerformance(array $filters): Collection
    {
        $totalSeatsSubquery = DB::table('showtime_seats')
            ->join('showtimes', 'showtime_seats.showtime_id', '=', 'showtimes.id')
            ->whereNotIn('showtime_seats.status', ['BLOCKED', 'LOCKED', 'BROKEN'])
            ->whereBetween('showtimes.start_time', [$filters['start_date'], $filters['end_date']])
            ->when($filters['cinema_id'], fn ($query) => $query->where('showtimes.cinema_id', $filters['cinema_id']))
            ->when($filters['movie_id'], fn ($query) => $query->where('showtimes.movie_id', $filters['movie_id']))
            ->groupBy('showtimes.id')
            ->select([
                'showtimes.id as showtime_id',
                DB::raw('COUNT(showtime_seats.id) as total_seats'),
            ]);

        $soldSeatsSubquery = DB::table('booking_seats')
            ->join('bookings', 'booking_seats.booking_id', '=', 'bookings.id')
            ->join('showtimes', 'bookings.showtime_id', '=', 'showtimes.id')
            ->leftJoin('payments', 'payments.booking_id', '=', 'bookings.id')
            ->whereNotIn('bookings.status', $this->excludedBookingStatuses)
            ->where(function ($query) {
                $query->where('bookings.status', 'PAID')
                    ->orWhere('bookings.payment_status', 'PAID')
                    ->orWhere('payments.status', 'SUCCESS');
            })
            ->whereBetween('showtimes.start_time', [$filters['start_date'], $filters['end_date']])
            ->when($filters['cinema_id'], fn ($query) => $query->where('showtimes.cinema_id', $filters['cinema_id']))
            ->when($filters['movie_id'], fn ($query) => $query->where('showtimes.movie_id', $filters['movie_id']))
            ->groupBy('showtimes.id')
            ->select([
                'showtimes.id as showtime_id',
                DB::raw('COUNT(booking_seats.id) as sold_tickets'),
                DB::raw('COALESCE(SUM(booking_seats.price), 0) as ticket_revenue'),
            ]);

        return DB::table('showtimes')
            ->join('movies', 'showtimes.movie_id', '=', 'movies.id')
            ->join('cinemas', 'showtimes.cinema_id', '=', 'cinemas.id')
            ->join('rooms', 'showtimes.room_id', '=', 'rooms.id')
            ->joinSub($totalSeatsSubquery, 'seat_totals', 'showtimes.id', '=', 'seat_totals.showtime_id')
            ->leftJoinSub($soldSeatsSubquery, 'seat_sales', 'showtimes.id', '=', 'seat_sales.showtime_id')
            ->whereBetween('showtimes.start_time', [$filters['start_date'], $filters['end_date']])
            ->when($filters['cinema_id'], fn ($query) => $query->where('showtimes.cinema_id', $filters['cinema_id']))
            ->when($filters['movie_id'], fn ($query) => $query->where('showtimes.movie_id', $filters['movie_id']))
            ->select([
                'showtimes.id',
                'showtimes.start_time',
                'movies.title as movie_title',
                'cinemas.name as cinema_name',
                'rooms.name as room_name',
                'seat_totals.total_seats',
                DB::raw('COALESCE(seat_sales.sold_tickets, 0) as sold_tickets'),
                DB::raw('COALESCE(seat_sales.ticket_revenue, 0) as ticket_revenue'),
                DB::raw('ROUND((COALESCE(seat_sales.sold_tickets, 0) / NULLIF(seat_totals.total_seats, 0)) * 100, 1) as occupancy_rate'),
            ])
            ->orderByDesc('occupancy_rate')
            ->orderByDesc('sold_tickets')
            ->limit(8)
            ->get();
    }

    private function timeSlotPerformance(array $filters): Collection
    {
        $totalSeatsSubquery = DB::table('showtime_seats')
            ->join('showtimes', 'showtime_seats.showtime_id', '=', 'showtimes.id')
            ->whereNotIn('showtime_seats.status', ['BLOCKED', 'LOCKED', 'BROKEN'])
            ->whereBetween('showtimes.start_time', [$filters['start_date'], $filters['end_date']])
            ->when($filters['cinema_id'], fn ($query) => $query->where('showtimes.cinema_id', $filters['cinema_id']))
            ->when($filters['movie_id'], fn ($query) => $query->where('showtimes.movie_id', $filters['movie_id']))
            ->groupBy(DB::raw("CASE\n                WHEN HOUR(showtimes.start_time) BETWEEN 6 AND 11 THEN 'morning'\n                WHEN HOUR(showtimes.start_time) BETWEEN 12 AND 17 THEN 'afternoon'\n                ELSE 'evening'\n            END"))
            ->select([
                DB::raw("CASE\n                    WHEN HOUR(showtimes.start_time) BETWEEN 6 AND 11 THEN 'morning'\n                    WHEN HOUR(showtimes.start_time) BETWEEN 12 AND 17 THEN 'afternoon'\n                    ELSE 'evening'\n                END as slot_key"),
                DB::raw('COUNT(DISTINCT showtimes.id) as showtime_count'),
                DB::raw('COUNT(showtime_seats.id) as total_seats'),
            ]);

        $soldSeatsSubquery = DB::table('booking_seats')
            ->join('bookings', 'booking_seats.booking_id', '=', 'bookings.id')
            ->join('showtimes', 'bookings.showtime_id', '=', 'showtimes.id')
            ->leftJoin('payments', 'payments.booking_id', '=', 'bookings.id')
            ->whereNotIn('bookings.status', $this->excludedBookingStatuses)
            ->where(function ($query) {
                $query->where('bookings.status', 'PAID')
                    ->orWhere('bookings.payment_status', 'PAID')
                    ->orWhere('payments.status', 'SUCCESS');
            })
            ->whereBetween('showtimes.start_time', [$filters['start_date'], $filters['end_date']])
            ->when($filters['cinema_id'], fn ($query) => $query->where('showtimes.cinema_id', $filters['cinema_id']))
            ->when($filters['movie_id'], fn ($query) => $query->where('showtimes.movie_id', $filters['movie_id']))
            ->groupBy(DB::raw("CASE\n                WHEN HOUR(showtimes.start_time) BETWEEN 6 AND 11 THEN 'morning'\n                WHEN HOUR(showtimes.start_time) BETWEEN 12 AND 17 THEN 'afternoon'\n                ELSE 'evening'\n            END"))
            ->select([
                DB::raw("CASE\n                    WHEN HOUR(showtimes.start_time) BETWEEN 6 AND 11 THEN 'morning'\n                    WHEN HOUR(showtimes.start_time) BETWEEN 12 AND 17 THEN 'afternoon'\n                    ELSE 'evening'\n                END as slot_key"),
                DB::raw('COUNT(booking_seats.id) as sold_tickets'),
                DB::raw('COALESCE(SUM(booking_seats.price), 0) as ticket_revenue'),
            ]);

        $rows = DB::query()
            ->fromSub($totalSeatsSubquery, 'slot_totals')
            ->leftJoinSub($soldSeatsSubquery, 'slot_sales', 'slot_totals.slot_key', '=', 'slot_sales.slot_key')
            ->select([
                'slot_totals.slot_key',
                'slot_totals.showtime_count',
                'slot_totals.total_seats',
                DB::raw('COALESCE(slot_sales.sold_tickets, 0) as sold_tickets'),
                DB::raw('COALESCE(slot_sales.ticket_revenue, 0) as ticket_revenue'),
                DB::raw('ROUND((COALESCE(slot_sales.sold_tickets, 0) / NULLIF(slot_totals.total_seats, 0)) * 100, 1) as occupancy_rate'),
            ])
            ->get();

        $labels = [
            'morning' => 'Sáng',
            'afternoon' => 'Chiều',
            'evening' => 'Tối',
        ];

        return $rows
            ->map(function ($row) use ($labels) {
                $row->slot_label = $labels[$row->slot_key] ?? $row->slot_key;
                return $row;
            })
            ->sortByDesc('occupancy_rate')
            ->values();
    }

    private function comboStats(array $filters): array
    {
        $baseQuery = DB::table('booking_combos')
            ->join('bookings', 'booking_combos.booking_id', '=', 'bookings.id')
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
            ->when($filters['movie_id'], fn ($query) => $query->where('showtimes.movie_id', $filters['movie_id']));

        $summary = (clone $baseQuery)
            ->selectRaw('COALESCE(SUM(booking_combos.quantity), 0) as combo_quantity, COALESCE(SUM(booking_combos.total_price), 0) as combo_revenue')
            ->first();

        $bookingWithCombo = (clone $baseQuery)
            ->distinct('booking_combos.booking_id')
            ->count('booking_combos.booking_id');

        $totalPaidBookings = $this->filteredBookings($filters)
            ->leftJoin('payments', 'payments.booking_id', '=', 'bookings.id')
            ->where(function ($query) {
                $query->where('bookings.status', 'PAID')
                    ->orWhere('bookings.payment_status', 'PAID')
                    ->orWhere('payments.status', 'SUCCESS');
            })
            ->whereBetween('bookings.created_at', [$filters['start_date'], $filters['end_date']])
            ->count('bookings.id');

        $topCombos = (clone $baseQuery)
            ->join('combos', 'booking_combos.combo_id', '=', 'combos.id')
            ->groupBy('combos.id', 'combos.name')
            ->select([
                'combos.id',
                'combos.name',
                DB::raw('SUM(booking_combos.quantity) as quantity'),
                DB::raw('COALESCE(SUM(booking_combos.total_price), 0) as revenue'),
            ])
            ->orderByDesc('quantity')
            ->limit(5)
            ->get();

        return [
            'top_combos' => $topCombos,
            'combo_quantity' => (int) ($summary->combo_quantity ?? 0),
            'combo_revenue' => (float) ($summary->combo_revenue ?? 0),
            'booking_with_combo_rate' => $totalPaidBookings > 0 ? round(($bookingWithCombo / $totalPaidBookings) * 100, 1) : 0,
        ];
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