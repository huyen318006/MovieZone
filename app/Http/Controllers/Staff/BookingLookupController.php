<?php

namespace App\Http\Controllers\Staff;

use App\Helpers\DataMaskHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\BookingSearchRequest;
use App\Models\Cinema;
use App\Models\Booking;
use App\Models\BookingCancellation;
use App\Services\AuditLogService;
use App\Services\BookingLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * S2-05: Controller cho UC-STAFF-03 Tra cứu Booking/Vé.
 *
 * Tất cả routes đều READ-ONLY (BR01).
 * Mọi hành động đều được ghi Audit Log (BR02).
 */
class BookingLookupController extends Controller
{
    public function __construct(
        protected BookingLookupService $lookupService,
    ) {}

    /**
     * Hiển thị trang tra cứu booking (Blade view).
     */
    public function index(Request $request)
    {
        $cinemas = Cinema::select('id', 'name')->orderBy('name')->get();

        return view('staff.booking-lookup', compact('cinemas'));
    }

    /**
     * Staff xác minh booking của suất bị gián đoạn và duyệt bồi thường 50%.
     */
    public function approveInterruptedCompensation(int $id): JsonResponse
    {
        try {
            $refundAmount = DB::transaction(function () use ($id) {
                $booking = Booking::with('showtime')->lockForUpdate()->find($id);
                if (! $booking) {
                    abort(404, 'Không tìm thấy booking.');
                }

                $cancellation = BookingCancellation::query()
                    ->where('booking_id', $booking->id)
                    ->where('type', 'CANCELLATION')
                    ->lockForUpdate()
                    ->first();

                $notes = $cancellation?->notes ?? [];
                $isEligible = $booking->status === 'CANCELLED'
                    && $booking->payment_status === 'PAID'
                    && ($cancellation?->refund_status === 'pending_verification')
                    && (($notes['source'] ?? null) === 'screening_interrupted');

                if (! $isEligible) {
                    abort(422, 'Booking không thuộc diện chờ xác minh bồi thường sự cố.');
                }
                $refundAmount = (int) round(((float) $booking->final_amount) * 0.5);

                $notes['compensation_percent'] = 50;
                $notes['compensation_amount'] = $refundAmount;
                $notes['compensation_method'] = 'CASH_COUNTER';
                $notes['verified_by'] = Auth::id();
                $notes['verified_at'] = now()->toIso8601String();
                $cancellation->update(['refund_status' => 'refunded', 'notes' => $notes]);

                AuditLogService::log('APPROVE_INTERRUPTED_COMPENSATION', 'Booking', $booking->id, null, [
                    'booking_code' => $booking->booking_code,
                    'compensation_percent' => 50,
                    'compensation_amount' => $refundAmount,
                    'verified_by' => Auth::id(),
                ]);

                return $refundAmount;
            });

            return response()->json(['success' => true, 'message' => "Đã xác nhận hoàn tiền mặt 50% ({$refundAmount}đ) tại quầy."]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getStatusCode());
        }
    }
    /**
     * API: Tìm kiếm booking.
     * GET /api/staff/bookings/search
     */
    public function search(BookingSearchRequest $request): JsonResponse
    {
        $criteria = $request->validated();

        $results = $this->lookupService->searchBookings($criteria);

        // Ghi audit log (BR02)
        AuditLogService::log(
            'BOOKING_SEARCH',
            'Booking',
            '0',
            null,
            [
                'search_type'  => $criteria['search_type'],
                'search_value' => $this->maskSearchValue($criteria['search_type'], $criteria['search_value']),
                'filters'      => array_filter([
                    'booking_status'     => $criteria['booking_status'] ?? null,
                    'payment_status'     => $criteria['payment_status'] ?? null,
                    'showtime_date_from' => $criteria['showtime_date_from'] ?? null,
                    'showtime_date_to'   => $criteria['showtime_date_to'] ?? null,
                    'cinema_id'          => $criteria['cinema_id'] ?? null,
                ]),
                'results_count' => $results->total(),
            ]
        );

        // Transform results — mask thông tin nhạy cảm (BR03)
        $items = $results->getCollection()->map(function ($booking) {
            $seatCodes = $booking->bookingSeats->pluck('seat_code')->toArray();
            $ticketStatuses = [];
            if ($booking->tickets_count > 0) {
                $ticketStatuses = $booking->tickets?->pluck('status')->toArray() ?? [];
            }

            return [
                'id'            => $booking->id,
                'booking_code'  => $booking->booking_code,
                'customer'      => [
                    'name'  => $booking->customer_name ?? $booking->user?->name ?? 'N/A',
                    'phone' => DataMaskHelper::maskPhone($booking->customer_phone ?? $booking->user?->phone),
                    'email' => DataMaskHelper::maskEmail($booking->customer_email ?? $booking->user?->email),
                ],
                'movie'         => [
                    'title' => $booking->showtime?->movie?->title ?? 'N/A',
                ],
                'showtime'      => [
                    'start_time'   => $booking->showtime?->start_time,
                    'end_time'     => $booking->showtime?->end_time,
                    'cinema_name'  => $booking->showtime?->cinema?->name ?? 'N/A',
                ],
                'seats'                => $seatCodes,
                'status'               => $booking->status,
                'payment_status'       => $booking->payment_status,
                'ticket_status_summary' => $this->summarizeTicketStatus($booking),
                'final_amount'         => $booking->final_amount,
                'created_at'           => $booking->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => $results->total() > 0
                ? "Tìm thấy {$results->total()} booking"
                : 'Không tìm thấy thông tin booking',
            'data'    => [
                'items'      => $items,
                'pagination' => [
                    'current_page' => $results->currentPage(),
                    'per_page'     => $results->perPage(),
                    'total'        => $results->total(),
                    'last_page'    => $results->lastPage(),
                ],
            ],
            'meta'    => [
                'search_type'     => $criteria['search_type'],
                'filters_applied' => array_filter([
                    'booking_status'     => $criteria['booking_status'] ?? null,
                    'payment_status'     => $criteria['payment_status'] ?? null,
                    'cinema_id'          => $criteria['cinema_id'] ?? null,
                ]),
            ],
        ]);
    }

    /**
     * API: Xem chi tiết booking.
     * GET /api/staff/bookings/{id}
     */
    public function detail(int $id): JsonResponse
    {
        $booking = $this->lookupService->getBookingDetail($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'NOT_FOUND',
                    'message' => 'Booking không tồn tại.',
                ],
            ], 404);
        }

        // Ghi audit log
        AuditLogService::log('BOOKING_VIEW_DETAIL', 'Booking', $booking->id, null, [
            'booking_code' => $booking->booking_code,
        ]);

        // Lấy voucher usages và timeline
        $voucherUsages = $this->lookupService->getVoucherUsages($booking->id);
        $timeline      = $this->lookupService->buildTimeline($booking);

        // Transform response (BR03: mask data)
        $response = [
            'id'           => $booking->id,
            'booking_code' => $booking->booking_code,

            'customer' => [
                'name'  => $booking->customer_name ?? $booking->user?->name ?? 'N/A',
                'phone' => DataMaskHelper::maskPhone($booking->customer_phone ?? $booking->user?->phone),
                'email' => DataMaskHelper::maskEmail($booking->customer_email ?? $booking->user?->email),
            ],

            'movie' => [
                'id'       => $booking->showtime?->movie?->id,
                'title'    => $booking->showtime?->movie?->title ?? 'N/A',
                'poster'   => $booking->showtime?->movie?->poster_url,
                'language' => $booking->showtime?->movie?->language,
            ],

            'showtime' => [
                'id'            => $booking->showtime?->id,
                'start_time'    => $booking->showtime?->start_time,
                'end_time'      => $booking->showtime?->end_time,
                'format'        => $booking->showtime?->room?->room_type,
                'language'      => $booking->showtime?->movie?->language,
                'status'        => $booking->showtime?->status,
                'cancel_reason' => $booking->showtime?->cancel_reason,
            ],

            'cinema' => [
                'id'      => $booking->showtime?->cinema?->id,
                'name'    => $booking->showtime?->cinema?->name ?? 'N/A',
                'address' => $booking->showtime?->cinema?->address,
            ],

            'room' => [
                'id'        => $booking->showtime?->room?->id,
                'name'      => $booking->showtime?->room?->name ?? 'N/A',
                'room_type' => $booking->showtime?->room?->room_type,
            ],

            'seats' => $booking->bookingSeats->map(fn($s) => [
                'seat_code' => $s->seat_code,
                'seat_type' => $s->seat_type,
                'price'     => $s->price,
            ]),

            'combos' => $booking->bookingCombos->map(fn($bc) => [
                'name'        => $bc->combo?->name ?? 'N/A',
                'quantity'    => $bc->quantity,
                'unit_price'  => $bc->unit_price,
                'total_price' => $bc->total_price,
            ]),

            'voucher' => $voucherUsages->map(fn($vu) => [
                'code'           => $vu->voucher?->code,
                'discount_type'  => $vu->voucher?->discount_type,
                'discount_value' => $vu->voucher?->discount_value,
                'used_at'        => $vu->used_at,
            ])->first(), // Thường chỉ 1 voucher/booking

            'pricing' => [
                'total_ticket_amount' => $booking->total_ticket_amount,
                'total_combo_amount'  => $booking->total_combo_amount,
                'discount_amount'     => $booking->discount_amount,
                'final_amount'        => $booking->final_amount,
            ],

            'payment' => $booking->payment ? [
                'payment_method'   => $booking->payment->payment_method,
                'amount'           => $booking->payment->amount,
                'transaction_code' => $booking->payment->transaction_code,
                'status'           => $booking->payment->status,
                'paid_at'          => $booking->payment->paid_at,
            ] : null,

            'tickets' => $booking->tickets->map(fn($t) => [
                'ticket_code'   => $t->ticket_code,
                'qr_code'       => $t->qr_code,
                'status'        => $t->status,
                'checked_in_at' => $t->checked_in_at,
                'checked_in_by' => $t->checkedInByUser ? [
                    'id'   => $t->checkedInByUser->id,
                    'name' => $t->checkedInByUser->name,
                ] : null,
                'seat_code' => $t->bookingSeat?->seat_code ?? $booking->bookingSeats
                    ->firstWhere('id', $t->booking_seat_id)?->seat_code,
            ]),

            'timeline' => $timeline,

            'status'         => $booking->status,
            'payment_status' => $booking->payment_status,
            'cancellation'   => $booking->cancellation ? [
                'refund_status' => $booking->cancellation->refund_status,
                'notes'         => $booking->cancellation->notes,
            ] : null,
            'expired_at'     => $booking->expired_at,
            'paid_at'        => $booking->paid_at,
            'created_at'     => $booking->created_at,
        ];

        return response()->json([
            'success' => true,
            'data'    => $response,
        ]);
    }

    /**
     * API: Xem audit logs của booking.
     * GET /api/staff/bookings/{id}/audit-logs
     */
    public function auditLogs(int $id): JsonResponse
    {
        $logs = $this->lookupService->getAuditLogs($id);

        AuditLogService::log('BOOKING_VIEW_AUDIT_LOG', 'Booking', $id);

        return response()->json([
            'success' => true,
            'data'    => $logs->map(fn($log) => [
                'id'           => $log->id,
                'action'       => $log->action,
                'performed_by' => $log->user?->name ?? 'Hệ thống',
                'created_at'   => $log->created_at,
                'old_value'    => $log->old_value ? json_decode($log->old_value, true) : null,
                'new_value'    => $log->new_value ? json_decode($log->new_value, true) : null,
            ]),
        ]);
    }

    /**
     * API: Danh sách rạp cho bộ lọc.
     * GET /api/staff/cinemas
     */
    public function cinemas(): JsonResponse
    {
        $cinemas = Cinema::select('id', 'name')
            ->where('status', 'ACTIVE')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $cinemas,
        ]);
    }

    // ── Private helpers ──

    /**
     * Tóm tắt trạng thái vé: "2/3 UNUSED"
     */
    private function summarizeTicketStatus($booking): string
    {
        $tickets = $booking->tickets ?? collect();
        if ($tickets->isEmpty()) {
            return 'N/A';
        }

        $total  = $tickets->count();
        $unused = $tickets->where('status', 'UNUSED')->count();
        $used   = $tickets->where('status', 'USED')->count();

        if ($used === $total) {
            return "{$total}/{$total} CHECKED_IN";
        }
        if ($unused === $total) {
            return "{$total}/{$total} UNUSED";
        }

        return "{$used}/{$total} CHECKED_IN";
    }

    /**
     * Mask search value cho audit log.
     */
    private function maskSearchValue(string $type, string $value): string
    {
        return match ($type) {
            'phone' => DataMaskHelper::maskPhone($value) ?? $value,
            'email' => DataMaskHelper::maskEmail($value) ?? $value,
            default => $value,
        };
    }
}
