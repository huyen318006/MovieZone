<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CheckInLog;
use App\Models\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * UC-STAFF-01: Check-in Service.
 *
 * Core business logic cho check-in vé.
 * Xử lý: validate ticket, confirm check-in, batch check-in, history.
 */
class CheckInService
{
    /**
     * Cửa sổ check-in sớm (phút trước giờ chiếu).
     */
    private const EARLY_CHECKIN_MINUTES = 1440; // Kéo dài thời gian check-in lên trước 24h (1 ngày)

    public function __construct(
        protected QRCodeService $qrCodeService,
    ) {}

    /**
     * Validate QR content và trả về thông tin preview hoặc list vé (nếu là booking).
     *
     * @return array{can_checkin: bool, ticket: ?array, booking: ?array, tickets: ?array, error: ?array}
     */
    public function validateQRScan(string $qrContent): array
    {
        // Step 1: Validate QR format + checksum
        $qrResult = $this->qrCodeService->validateQR($qrContent);

        if (!$qrResult['valid']) {
            return [
                'can_checkin' => false,
                'ticket' => null,
                'booking' => null,
                'tickets' => null,
                'error' => [
                    'code' => $qrResult['error'],
                    'message' => $this->getErrorMessage($qrResult['error']),
                ],
            ];
        }

        // Step 2: Route dựa trên loại QR (ticket hay booking)
        if ($qrResult['type'] === 'booking') {
            return $this->lookupManual($qrResult['code'], 'booking_code');
        }

        // Default: type = ticket
        return $this->validateTicket($qrResult['code'], 'QR_SCAN', $qrContent);
    }

    /**
     * Lookup booking/ticket bằng mã thủ công.
     */
    public function lookupManual(string $code, string $type): array
    {
        if ($type === 'ticket_code') {
            return $this->validateTicket($code, 'MANUAL');
        }

        // Lookup by booking_code → trả danh sách tickets
        $booking = Booking::with([
            'tickets.bookingSeat',
            'tickets.checkedInByUser:id,name',
            'showtime.movie:id,title,poster_url',
            'showtime.cinema:id,name',
            'showtime.room:id,name,room_type',
            'user:id,name',
        ])->where('booking_code', $code)->first();

        if (!$booking) {
            return [
                'can_checkin' => false,
                'ticket' => null,
                'booking' => null,
                'error' => [
                    'code' => 'BOOKING_NOT_FOUND',
                    'message' => 'Không tìm thấy booking.',
                ],
            ];
        }

        // Luôn lấy danh sách tickets dù có lỗi hay không
        $tickets = $this->getAllBookingTickets($booking);

        // Validate booking-level
        $bookingError = $this->checkBookingStatus($booking);
        if ($bookingError) {
            return [
                'can_checkin' => false,
                'booking' => $this->formatBooking($booking),
                'tickets' => $tickets,
                'error' => $bookingError,
            ];
        }

        // Validate showtime
        $showtimeError = $this->checkShowtime($booking->showtime);
        if ($showtimeError) {
            return [
                'can_checkin' => false,
                'booking' => $this->formatBooking($booking),
                'tickets' => $tickets,
                'error' => $showtimeError,
            ];
        }

        return [
            'can_checkin' => $tickets->where('can_checkin', true)->isNotEmpty(),
            'booking' => $this->formatBooking($booking),
            'tickets' => $tickets,
            'error' => null,
        ];
    }

    /**
     * Xác nhận check-in 1 vé.
     */
    public function confirmCheckIn(int $ticketId, int $staffId, string $scanMethod = 'QR_SCAN'): array
    {
        $ticket = Ticket::with([
            'booking.showtime.movie:id,title',
            'booking.showtime.cinema:id,name',
            'booking.showtime.room:id,name',
            'bookingSeat',
        ])->find($ticketId);

        if (!$ticket) {
            return ['success' => false, 'error' => ['code' => 'TICKET_NOT_FOUND', 'message' => 'Không tìm thấy vé.']];
        }

        // Re-validate trước khi confirm (tránh race condition)
        if ($ticket->status === 'USED') {
            return [
                'success' => false,
                'error' => [
                    'code' => 'TICKET_ALREADY_CHECKED_IN',
                    'message' => "Vé đã check-in lúc {$ticket->checked_in_at} bởi " . ($ticket->checkedInByUser?->name ?? 'N/A'),
                ],
            ];
        }

        if ($ticket->status !== 'UNUSED') {
            return ['success' => false, 'error' => ['code' => 'TICKET_CANCELLED', 'message' => 'Vé đã bị hủy.']];
        }

        try {
            DB::transaction(function () use ($ticket, $staffId, $scanMethod) {
                // 1. Update ticket status
                $ticket->update([
                    'status'        => 'USED',
                    'checked_in_at' => now(),
                    'checked_in_by' => $staffId,
                ]);

                // 2. Log check-in
                CheckInLog::create([
                    'ticket_id'   => $ticket->id,
                    'booking_id'  => $ticket->booking_id,
                    'showtime_id' => $ticket->booking->showtime_id,
                    'staff_id'    => $staffId,
                    'scan_method' => $scanMethod,
                    'result'      => 'SUCCESS',
                    'ip_address'  => request()->ip(),
                    'user_agent'  => request()->userAgent(),
                    'created_at'  => now(),
                ]);

                // 3. Audit log
                AuditLogService::log(
                    'TICKET_CHECK_IN',
                    'Ticket',
                    $ticket->id,
                    ['status' => 'UNUSED'],
                    ['status' => 'USED', 'staff_id' => $staffId, 'scan_method' => $scanMethod]
                );
            });

            return [
                'success' => true,
                'data' => [
                    'ticket_code' => $ticket->ticket_code,
                    'seat_code'   => $ticket->bookingSeat?->seat_code ?? 'N/A',
                    'movie_title' => $ticket->booking?->showtime?->movie?->title ?? 'N/A',
                    'cinema_name' => $ticket->booking?->showtime?->cinema?->name ?? 'N/A',
                    'room_name'   => $ticket->booking?->showtime?->room?->name ?? 'N/A',
                    'checked_in_at' => now()->toDateTimeString(),
                ],
            ];
        } catch (\Exception $e) {
            // Log failed attempt
            CheckInLog::create([
                'ticket_id'      => $ticket->id,
                'booking_id'     => $ticket->booking_id,
                'showtime_id'    => $ticket->booking->showtime_id ?? null,
                'staff_id'       => $staffId,
                'scan_method'    => $scanMethod,
                'result'         => 'FAILED',
                'failure_reason' => 'TRANSACTION_FAILED: ' . $e->getMessage(),
                'ip_address'     => request()->ip(),
                'user_agent'     => request()->userAgent(),
                'created_at'     => now(),
            ]);

            return ['success' => false, 'error' => ['code' => 'CHECK_IN_FAILED', 'message' => 'Lỗi hệ thống. Vui lòng thử lại.']];
        }
    }

    /**
     * Check-in hàng loạt.
     */
    public function confirmBatchCheckIn(int $bookingId, array $ticketIds, int $staffId): array
    {
        $results = ['checked_in' => 0, 'failed' => 0, 'details' => []];

        foreach ($ticketIds as $ticketId) {
            $result = $this->confirmCheckIn($ticketId, $staffId, 'MANUAL');
            if ($result['success']) {
                $results['checked_in']++;
            } else {
                $results['failed']++;
            }
            $results['details'][] = ['ticket_id' => $ticketId] + $result;
        }

        return $results;
    }

    /**
     * Lịch sử check-in.
     */
    public function getHistory(array $filters): LengthAwarePaginator
    {
        $query = CheckInLog::with([
            'ticket:id,ticket_code',
            'booking:id,booking_code',
            'staff:id,name',
            'showtime:id,movie_id,start_time',
            'showtime.movie:id,title',
        ])->orderByDesc('created_at');

        if (!empty($filters['showtime_id'])) {
            $query->where('showtime_id', $filters['showtime_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['result'])) {
            $query->where('result', $filters['result']);
        }

        return $query->paginate($filters['per_page'] ?? 20);
    }

    // ══════ PRIVATE HELPERS ══════

    /**
     * Validate một ticket cụ thể.
     */
    private function validateTicket(string $ticketCode, string $scanMethod, ?string $qrPayload = null): array
    {
        $ticket = Ticket::with([
            'booking:id,booking_code,user_id,showtime_id,status,payment_status,final_amount',
            'booking.user:id,name',
            'booking.showtime:id,cinema_id,room_id,movie_id,start_time,end_time,status',
            'booking.showtime.movie:id,title,poster_url,duration_minutes',
            'booking.showtime.cinema:id,name',
            'booking.showtime.room:id,name,room_type,status',
            'bookingSeat:id,booking_id,seat_code,seat_type,price',
            'checkedInByUser:id,name',
        ])->where('ticket_code', $ticketCode)->first();

        if (!$ticket) {
            return [
                'can_checkin' => false,
                'ticket' => null,
                'error' => ['code' => 'TICKET_NOT_FOUND', 'message' => 'Không tìm thấy vé trong hệ thống.'],
            ];
        }

        $booking = $ticket->booking;

        // Check booking status
        $bookingError = $this->checkBookingStatus($booking);
        if ($bookingError) {
            return [
                'can_checkin' => false,
                'ticket' => $this->formatTicketPreview($ticket),
                'booking' => $this->formatBooking($booking),
                'tickets' => $this->getAllBookingTickets($booking),
                'scanned_ticket_code' => $ticketCode,
                'error' => $bookingError,
            ];
        }

        // Check ticket status
        if ($ticket->status === 'USED') {
            $checkedBy = $ticket->checkedInByUser?->name ?? 'N/A';
            $checkedAt = $ticket->checked_in_at;
            return [
                'can_checkin' => false,
                'ticket' => $this->formatTicketPreview($ticket),
                'booking' => $this->formatBooking($booking),
                'tickets' => $this->getAllBookingTickets($booking),
                'scanned_ticket_code' => $ticketCode,
                'error' => [
                    'code' => 'TICKET_ALREADY_CHECKED_IN',
                    'message' => "Vé đã check-in lúc {$checkedAt} bởi {$checkedBy}.",
                ],
            ];
        }

        if ($ticket->status === 'CANCELLED') {
            return [
                'can_checkin' => false,
                'ticket' => $this->formatTicketPreview($ticket),
                'booking' => $this->formatBooking($booking),
                'tickets' => $this->getAllBookingTickets($booking),
                'scanned_ticket_code' => $ticketCode,
                'error' => ['code' => 'TICKET_CANCELLED', 'message' => 'Vé đã bị hủy.'],
            ];
        }

        // Check showtime
        $showtimeError = $this->checkShowtime($booking->showtime);
        if ($showtimeError) {
            return [
                'can_checkin' => false,
                'ticket' => $this->formatTicketPreview($ticket),
                'booking' => $this->formatBooking($booking),
                'tickets' => $this->getAllBookingTickets($booking),
                'scanned_ticket_code' => $ticketCode,
                'error' => $showtimeError,
            ];
        }

        // Check room
        if ($booking->showtime->room && $booking->showtime->room->status !== 'ACTIVE') {
            return [
                'can_checkin' => false,
                'ticket' => $this->formatTicketPreview($ticket),
                'booking' => $this->formatBooking($booking),
                'tickets' => $this->getAllBookingTickets($booking),
                'scanned_ticket_code' => $ticketCode,
                'error' => ['code' => 'ROOM_INACTIVE', 'message' => 'Phòng chiếu không hoạt động.'],
            ];
        }

        // All valid! Return with all booking tickets for batch view
        return [
            'can_checkin' => true,
            'ticket' => $this->formatTicketPreview($ticket),
            'booking' => $this->formatBooking($booking),
            'tickets' => $this->getAllBookingTickets($booking),
            'scanned_ticket_code' => $ticketCode,
            'error' => null,
        ];
    }

    /**
     * Lấy danh sách tất cả vé trong cùng booking.
     */
    private function getAllBookingTickets(Booking $booking): \Illuminate\Support\Collection
    {
        // Load tickets if not already loaded
        if (!$booking->relationLoaded('tickets')) {
            $booking->load(['tickets.bookingSeat', 'tickets.checkedInByUser:id,name']);
        }

        return $booking->tickets->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'ticket_code' => $ticket->ticket_code,
                'seat_code' => $ticket->bookingSeat?->seat_code ?? 'N/A',
                'seat_type' => $ticket->bookingSeat?->seat_type ?? 'N/A',
                'status' => $ticket->status,
                'can_checkin' => $ticket->status === 'UNUSED',
                'checked_in_at' => $ticket->checked_in_at,
                'checked_in_by_name' => $ticket->checkedInByUser?->name,
            ];
        });
    }

    private function checkBookingStatus($booking): ?array
    {
        if (!$booking) {
            return ['code' => 'BOOKING_NOT_FOUND', 'message' => 'Không tìm thấy booking.'];
        }

        if ($booking->status !== 'PAID' || $booking->payment_status !== 'PAID') {
            if ($booking->status === 'CANCELLED') {
                return ['code' => 'BOOKING_CANCELLED', 'message' => 'Booking đã bị hủy.'];
            }
            if ($booking->status === 'EXPIRED') {
                return ['code' => 'BOOKING_EXPIRED', 'message' => 'Booking đã hết hạn.'];
            }
            return ['code' => 'BOOKING_UNPAID', 'message' => 'Booking chưa thanh toán. Vui lòng thanh toán trước.'];
        }

        return null;
    }

    private function checkShowtime($showtime): ?array
    {
        if (!$showtime) {
            return ['code' => 'SHOWTIME_NOT_FOUND', 'message' => 'Không tìm thấy suất chiếu.'];
        }

        if ($showtime->status === 'CANCELLED') {
            return ['code' => 'SHOWTIME_CANCELLED', 'message' => 'Suất chiếu đã bị hủy.'];
        }

        if ($showtime->end_time && $showtime->end_time->isPast()) {
            return ['code' => 'SHOWTIME_ENDED', 'message' => 'Suất chiếu đã kết thúc.'];
        }

        $earlyWindow = $showtime->start_time->copy()->subMinutes(self::EARLY_CHECKIN_MINUTES);
        if (now()->lt($earlyWindow)) {
            $allowedTime = $earlyWindow->format('H:i d/m/Y');
            return [
                'code' => 'TOO_EARLY',
                'message' => "Chưa đến giờ check-in. Vui lòng quay lại lúc {$allowedTime}.",
            ];
        }

        return null;
    }

    private function formatTicketPreview(Ticket $ticket): array
    {
        $booking = $ticket->booking;
        $showtime = $booking?->showtime;

        return [
            'id'           => $ticket->id,
            'ticket_code'  => $ticket->ticket_code,
            'status'       => $ticket->status,
            'checked_in_at' => $ticket->checked_in_at,
            'checked_in_by_name' => $ticket->checkedInByUser?->name,
            'seat_code'    => $ticket->bookingSeat?->seat_code ?? 'N/A',
            'seat_type'    => $ticket->bookingSeat?->seat_type ?? 'N/A',
            'seat_price'   => $ticket->bookingSeat?->price,
            'booking' => [
                'id'             => $booking?->id,
                'booking_code'   => $booking?->booking_code,
                'customer_name'  => $booking?->user?->name ?? 'N/A',
                'status'         => $booking?->status,
                'payment_status' => $booking?->payment_status,
                'final_amount'   => $booking?->final_amount,
            ],
            'movie' => [
                'title'      => $showtime?->movie?->title ?? 'N/A',
                'poster_url' => $showtime?->movie?->poster_url,
                'duration'   => $showtime?->movie?->duration_minutes,
            ],
            'showtime' => [
                'start_time'    => $showtime?->start_time,
                'end_time'      => $showtime?->end_time,
            ],
            'cinema' => [
                'name' => $showtime?->cinema?->name ?? 'N/A',
            ],
            'room' => [
                'name'      => $showtime?->room?->name ?? 'N/A',
                'room_type' => $showtime?->room?->room_type,
            ],
        ];
    }

    private function formatBooking(Booking $booking): array
    {
        $showtime = $booking->showtime;
        return [
            'id'             => $booking->id,
            'booking_code'   => $booking->booking_code,
            'customer_name'  => $booking->user?->name ?? 'N/A',
            'status'         => $booking->status,
            'payment_status' => $booking->payment_status,
            'final_amount'   => $booking->final_amount,
            'movie_title'    => $showtime?->movie?->title ?? 'N/A',
            'poster_url'     => $showtime?->movie?->poster_url,
            'cinema_name'    => $showtime?->cinema?->name ?? 'N/A',
            'room_name'      => $showtime?->room?->name ?? 'N/A',
            'room_type'      => $showtime?->room?->room_type,
            'start_time'     => $showtime?->start_time,
            'end_time'       => $showtime?->end_time,
        ];
    }

    private function getErrorMessage(string $code): string
    {
        return match ($code) {
            'INVALID_QR_FORMAT' => 'Mã QR không hợp lệ. Vui lòng quét lại.',
            'QR_TAMPERED' => 'Mã QR đã bị thay đổi. Vé có thể bị giả mạo.',
            default => 'Lỗi không xác định.',
        };
    }
}
