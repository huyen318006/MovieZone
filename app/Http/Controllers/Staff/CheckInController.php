<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\ConfirmCheckInRequest;
use App\Http\Requests\Staff\ManualCheckInRequest;
use App\Http\Requests\Staff\ScanCheckInRequest;
use App\Services\AuditLogService;
use App\Services\CheckInService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * UC-STAFF-01: Check-in Vé QR Controller.
 *
 * 6 methods:
 *  1. index()        — Render trang check-in
 *  2. scan()         — API quét QR
 *  3. manual()       — API nhập mã thủ công
 *  4. confirm()      — API xác nhận check-in 1 vé
 *  5. confirmBatch() — API check-in hàng loạt
 *  6. history()      — API lịch sử check-in
 */
class CheckInController extends Controller
{
    public function __construct(
        protected CheckInService $checkInService,
    ) {}

    /**
     * Render trang Check-in.
     */
    public function index()
    {
        return view('staff.check-in');
    }

    /**
     * API: Quét QR → validate → trả thông tin preview.
     */
    public function scan(ScanCheckInRequest $request): JsonResponse
    {
        $result = $this->checkInService->validateQRScan($request->validated()['qr_content']);

        // Log scan attempt
        AuditLogService::log(
            $result['can_checkin'] ? 'TICKET_SCAN' : 'TICKET_SCAN_FAILED',
            'Ticket',
            $result['ticket']['id'] ?? 0,
            null,
            [
                'qr_content' => substr($request->qr_content, 0, 50),
                'result' => $result['can_checkin'] ? 'VALID' : ($result['error']['code'] ?? 'UNKNOWN'),
            ]
        );

        $statusCode = match (true) {
            $result['can_checkin'] => 200,
            ($result['error']['code'] ?? '') === 'TICKET_NOT_FOUND' => 404,
            ($result['error']['code'] ?? '') === 'TICKET_ALREADY_CHECKED_IN' => 409,
            default => 422,
        };

        return response()->json([
            'success'     => $result['can_checkin'],
            'can_checkin' => $result['can_checkin'],
            'data'        => $result['ticket'] ?? null,
            'booking'     => $result['booking'] ?? null,
            'tickets'     => $result['tickets'] ?? null,
            'scanned_ticket_code' => $result['scanned_ticket_code'] ?? null,
            'error'       => $result['error'] ?? null,
        ], $statusCode);
    }

    /**
     * API: Nhập mã thủ công → lookup booking/ticket.
     */
    public function manual(ManualCheckInRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->checkInService->lookupManual($validated['code'], $validated['type']);

        $statusCode = 200;
        if (!$result['can_checkin'] && isset($result['error'])) {
            $statusCode = match ($result['error']['code'] ?? '') {
                'BOOKING_NOT_FOUND', 'TICKET_NOT_FOUND' => 404,
                'TICKET_ALREADY_CHECKED_IN' => 409,
                default => 422,
            };
        }

        return response()->json([
            'success'     => $result['can_checkin'],
            'can_checkin' => $result['can_checkin'],
            'data'        => $result['ticket'] ?? null,
            'booking'     => $result['booking'] ?? null,
            'tickets'     => $result['tickets'] ?? null,
            'scanned_ticket_code' => $result['scanned_ticket_code'] ?? null,
            'error'       => $result['error'] ?? null,
        ], $statusCode);
    }

    /**
     * API: Xác nhận check-in 1 vé.
     */
    public function confirm(ConfirmCheckInRequest $request): JsonResponse
    {
        $result = $this->checkInService->confirmCheckIn(
            $request->validated()['ticket_id'],
            $request->user()->id,
            $request->input('scan_method', 'QR_SCAN')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * API: Check-in hàng loạt theo booking.
     */
    public function confirmBatch(Request $request): JsonResponse
    {
        $request->validate([
            'booking_id' => 'required|integer|exists:bookings,id',
            'ticket_ids' => 'required|array|min:1',
            'ticket_ids.*' => 'integer|exists:tickets,id',
        ]);

        $result = $this->checkInService->confirmBatchCheckIn(
            $request->booking_id,
            $request->ticket_ids,
            $request->user()->id
        );

        return response()->json([
            'success' => $result['checked_in'] > 0,
            'data' => $result,
        ]);
    }

    /**
     * API: Lịch sử check-in.
     */
    public function history(Request $request): JsonResponse
    {
        $history = $this->checkInService->getHistory($request->all());

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Download PDF vé cho 1 booking (sau khi check-in).
     */
    public function downloadPDF($bookingId): \Symfony\Component\HttpFoundation\Response
    {
        $booking = \App\Models\Booking::with('tickets')->find($bookingId);

        if (!$booking) {
            return response()->json(['message' => 'Không tìm thấy booking.'], 404);
        }

        if ($booking->tickets->isEmpty()) {
            return response()->json(['message' => 'Booking chưa có vé nào.'], 404);
        }

        $pdfService = app(\App\Services\TicketPDFService::class);
        $pdfPath = $pdfService->generateBookingTicketsPDF($booking);

        if (!$pdfPath || !file_exists($pdfPath)) {
            return response()->json(['message' => 'Không thể tạo file PDF.'], 500);
        }

        return response()->download($pdfPath, "tickets_{$booking->booking_code}.pdf", [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * In hoá đơn booking (render HTML) - hoạt động với mọi phương thức thanh toán.
     */
    public function printBill(string $bookingCode)
    {
        $booking = \App\Models\Booking::with([
            'user:id,name,email,phone',
            'showtime.movie:id,title,poster_url',
            'showtime.cinema:id,name',
            'showtime.room:id,name,room_type',
            'bookingSeats',
            'tickets.bookingSeat',
            'payment',
            'bookingCombos.combo',
        ])->where('booking_code', $bookingCode)->first();

        if (!$booking) {
            return response('<h2 style="text-align:center;padding:40px;">Không tìm thấy booking.</h2>', 404);
        }

        $autoPrint = request()->query('print') === 'true';
        $singleTicketCode = request()->query('ticket');

        return view('staff.print-bill', compact('booking', 'autoPrint', 'singleTicketCode'));
    }
}
