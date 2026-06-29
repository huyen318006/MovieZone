<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CheckInLog;
use App\Models\Payment;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StaffDashboardService
{
    public function getDailyOverview(?Carbon $date = null): array
    {
        $date ??= today();
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        return [
            'date' => $date,
            'metrics' => [
                'checked_in_tickets' => $this->checkedInTicketsCount($startOfDay, $endOfDay),
                'pending_cash_bookings' => $this->pendingCashBookingsCount(),
                'new_bookings' => $this->newBookingsCount($startOfDay, $endOfDay),
                'support_issues' => 0,
            ],
            'recent_checkins' => $this->recentCheckins($startOfDay, $endOfDay),
            'recent_cash_payments' => $this->recentCashPayments($startOfDay, $endOfDay),
        ];
    }

    private function checkedInTicketsCount(Carbon $startOfDay, Carbon $endOfDay): int
    {
        return Ticket::query()
            ->where(function ($query) use ($startOfDay, $endOfDay) {
                $query->whereBetween('checked_in_at', [$startOfDay, $endOfDay])
                    ->orWhere(function ($fallbackQuery) use ($startOfDay, $endOfDay) {
                        $fallbackQuery->where('status', 'USED')
                            ->whereBetween('updated_at', [$startOfDay, $endOfDay]);
                    });
            })
            ->count();
    }

    private function pendingCashBookingsCount(): int
    {
        return Booking::query()
            ->where('status', 'PENDING_CASH_PAYMENT')
            ->count();
    }

    private function newBookingsCount(Carbon $startOfDay, Carbon $endOfDay): int
    {
        return Booking::query()
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->whereIn('status', ['PENDING_PAYMENT', 'PENDING_CASH_PAYMENT', 'PAID'])
            ->count();
    }

    private function recentCheckins(Carbon $startOfDay, Carbon $endOfDay): Collection
    {
        $logs = CheckInLog::query()
            ->with(['ticket.booking.user', 'booking.user', 'showtime.movie', 'staff'])
            ->where('result', 'SUCCESS')
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->latest('created_at')
            ->limit(8)
            ->get();

        if ($logs->isNotEmpty()) {
            return $logs;
        }

        return Ticket::query()
            ->with(['booking.user', 'booking.showtime.movie', 'checkedInByUser'])
            ->whereNotNull('checked_in_at')
            ->whereBetween('checked_in_at', [$startOfDay, $endOfDay])
            ->latest('checked_in_at')
            ->limit(8)
            ->get();
    }

    private function recentCashPayments(Carbon $startOfDay, Carbon $endOfDay): Collection
    {
        return Payment::query()
            ->with(['booking.user'])
            ->where('payment_method', 'CASH')
            ->where('status', 'SUCCESS')
            ->whereBetween('paid_at', [$startOfDay, $endOfDay])
            ->latest('paid_at')
            ->limit(8)
            ->get();
    }
}
