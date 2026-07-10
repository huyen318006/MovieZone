<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Ticket;
use App\Models\User;
use App\Models\VoucherUsage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * S2-02 / S2-03 / S2-04: Service xử lý logic tra cứu booking.
 *
 * Chịu trách nhiệm:
 * - Tìm kiếm booking theo nhiều tiêu chí (booking_code, ticket_code, phone, email)
 * - Lấy chi tiết booking đầy đủ (eager loading tối ưu)
 * - Lấy audit logs của booking
 * - Normalize phone format (+84 → 0)
 */
class BookingLookupService
{
    /**
     * S2-02: Tìm kiếm booking theo tiêu chí.
     *
     * @param array $criteria Tiêu chí tìm kiếm đã validate
     * @return LengthAwarePaginator
     */
    public function searchBookings(array $criteria): LengthAwarePaginator
    {
        $query = Booking::with([
            'user:id,name,phone,email',
            'showtime:id,movie_id,cinema_id,start_time,end_time',
            'showtime.movie:id,title',
            'showtime.cinema:id,name',
            'bookingSeats:id,booking_id,seat_code,seat_type',
        ])->withCount('tickets');

        // ── Tìm kiếm chính ──
        $searchType  = $criteria['search_type'];
        $searchValue = trim($criteria['search_value']);

        switch ($searchType) {
            case 'booking_code':
                $query->where('booking_code', $searchValue);
                break;

            case 'ticket_code':
                // Tìm booking qua ticket code
                $bookingIds = Ticket::where('ticket_code', $searchValue)->pluck('booking_id');
                $query->whereIn('id', $bookingIds);
                break;

            case 'phone':
                $phone = $this->normalizePhone($searchValue);
                $userIds = User::where('phone', $phone)->pluck('id');
                $query->whereIn('user_id', $userIds);
                break;

            case 'email':
                $userIds = User::where('email', $searchValue)->pluck('id');
                $query->whereIn('user_id', $userIds);
                break;
        }

        // ── Bộ lọc ──
        if (!empty($criteria['booking_status'])) {
            $query->where('status', $criteria['booking_status']);
        }

        if (!empty($criteria['payment_status'])) {
            $query->where('payment_status', $criteria['payment_status']);
        }

        if (!empty($criteria['cinema_id'])) {
            $query->whereHas('showtime', function ($q) use ($criteria) {
                $q->where('cinema_id', $criteria['cinema_id']);
            });
        }

        if (!empty($criteria['showtime_date_from'])) {
            $query->whereHas('showtime', function ($q) use ($criteria) {
                $q->whereDate('start_time', '>=', $criteria['showtime_date_from']);
            });
        }

        if (!empty($criteria['showtime_date_to'])) {
            $query->whereHas('showtime', function ($q) use ($criteria) {
                $q->whereDate('start_time', '<=', $criteria['showtime_date_to']);
            });
        }

        // ── Sắp xếp ──
        $sortBy  = $criteria['sort_by'] ?? 'created_at';
        $sortDir = $criteria['sort_dir'] ?? 'desc';

        if ($sortBy === 'start_time') {
            $query->join('showtimes', 'bookings.showtime_id', '=', 'showtimes.id')
                  ->orderBy('showtimes.start_time', $sortDir)
                  ->select('bookings.*');
        } else {
            $query->orderBy("bookings.{$sortBy}", $sortDir);
        }

        // ── Phân trang ──
        $perPage = (int) ($criteria['per_page'] ?? 15);

        return $query->paginate($perPage);
    }

    /**
     * S2-03: Lấy chi tiết booking đầy đủ.
     *
     * Eager loading tối ưu — tránh N+1 queries.
     *
     * @param int $bookingId
     * @return Booking|null
     */
    public function getBookingDetail(int $bookingId): ?Booking
    {
        return Booking::with([
            'user:id,name,phone,email',
            'showtime:id,cinema_id,movie_id,room_id,start_time,end_time,format,language_type,status,cancel_reason',
            'showtime.movie:id,title,poster_url',
            'showtime.cinema:id,name,address',
            'showtime.room:id,name,room_type',
            'bookingSeats:id,booking_id,seat_code,seat_type,price',
            'tickets:id,booking_id,booking_seat_id,ticket_code,qr_code,status,checked_in_at,checked_in_by',
            'tickets.checkedInByUser:id,name',
            'bookingCombos:id,booking_id,combo_id,quantity,unit_price,total_price',
            'bookingCombos.combo:id,name,description',
            'payment:id,booking_id,payment_method,amount,transaction_code,status,paid_at',
        ])->find($bookingId);
    }

    /**
     * Lấy voucher usage cho booking.
     */
    public function getVoucherUsages(int $bookingId): Collection
    {
        return VoucherUsage::with('voucher:id,code,discount_type,discount_value,status')
            ->where('booking_id', $bookingId)
            ->get();
    }

    /**
     * S2-04: Lấy audit logs của một booking.
     */
    public function getAuditLogs(int $bookingId): Collection
    {
        return AuditLog::with('user:id,name')
            ->where('entity_name', 'Booking')
            ->where('entity_id', (string) $bookingId)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
    }

    /**
     * Xây dựng timeline từ dữ liệu booking.
     *
     * @param Booking $booking Booking đã eager loaded
     * @return array
     */
    public function buildTimeline(Booking $booking): array
    {
        $timeline = [];

        // Helper: đảm bảo timestamp luôn là ISO string
        $toISO = function ($value): ?string {
            if (!$value) return null;
            if ($value instanceof \Carbon\Carbon || $value instanceof \DateTimeInterface) {
                return $value->toISOString();
            }
            // Nếu là string, thử parse
            try {
                return \Carbon\Carbon::parse($value)->toISOString();
            } catch (\Exception $e) {
                return (string) $value;
            }
        };

        // Booking created
        $timeline[] = [
            'event'       => 'BOOKING_CREATED',
            'timestamp'   => $toISO($booking->created_at),
            'description' => 'Booking được tạo',
            'icon'        => '📝',
        ];

        // Expired at
        if ($booking->expired_at) {
            $timeline[] = [
                'event'       => 'BOOKING_EXPIRED_AT',
                'timestamp'   => $toISO($booking->expired_at),
                'description' => 'Hết hạn thanh toán',
                'icon'        => '⏰',
            ];
        }

        // Payment
        if ($booking->paid_at) {
            $paymentMethod = $booking->payment?->payment_method ?? 'N/A';
            $timeline[] = [
                'event'       => 'PAYMENT_SUCCESS',
                'timestamp'   => $toISO($booking->paid_at),
                'description' => "Thanh toán thành công qua {$paymentMethod}",
                'icon'        => '💳',
            ];
        }

        // Tickets generated
        if ($booking->tickets && $booking->tickets->count() > 0) {
            $firstTicket = $booking->tickets->sortBy('created_at')->first();
            $ticketTimestamp = $toISO($firstTicket->created_at) ?? $toISO($booking->paid_at) ?? $toISO($booking->created_at);
            $timeline[] = [
                'event'       => 'TICKETS_GENERATED',
                'timestamp'   => $ticketTimestamp,
                'description' => $booking->tickets->count() . ' vé được tạo',
                'icon'        => '🎟️',
            ];

            // Check-ins
            foreach ($booking->tickets->where('status', 'USED') as $ticket) {
                $checkedBy = $ticket->checkedInByUser?->name ?? 'N/A';
                $timeline[] = [
                    'event'       => 'TICKET_CHECKED_IN',
                    'timestamp'   => $toISO($ticket->checked_in_at),
                    'description' => "Vé {$ticket->ticket_code} check-in bởi {$checkedBy}",
                    'icon'        => '✅',
                ];
            }
        }

        // Cancelled
        if ($booking->status === 'CANCELLED') {
            $cancelLog = AuditLog::where('entity_name', 'Booking')
                ->where('entity_id', (string) $booking->id)
                ->where('action', 'like', '%CANCEL%')
                ->orderByDesc('created_at')
                ->first();

            $timeline[] = [
                'event'       => 'BOOKING_CANCELLED',
                'timestamp'   => $toISO($cancelLog?->created_at ?? $booking->updated_at),
                'description' => 'Booking đã bị hủy',
                'icon'        => '❌',
            ];
        }

        // Sort by timestamp ascending (cũ nhất trước → mới nhất sau)
        usort($timeline, fn($a, $b) => strcmp($a['timestamp'] ?? '', $b['timestamp'] ?? ''));

        return $timeline;
    }

    /**
     * S2-08: Normalize phone format.
     * +84912345678 → 0912345678
     */
    public function normalizePhone(string $phone): string
    {
        $phone = trim($phone);

        if (str_starts_with($phone, '+84')) {
            $phone = '0' . substr($phone, 3);
        } elseif (str_starts_with($phone, '84') && strlen($phone) >= 11) {
            $phone = '0' . substr($phone, 2);
        }

        return $phone;
    }
}
