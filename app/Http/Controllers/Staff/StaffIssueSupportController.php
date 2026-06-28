<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StaffIssueSupportRequest;
use App\Services\AuditLogService;
use App\Services\BookingLookupService;
use App\Services\QRCodeService;
use App\Services\IssueSupportService;
use Illuminate\Http\JsonResponse;

class StaffIssueSupportController extends Controller
{
    public function __construct(
        protected BookingLookupService $lookupService,
        protected QRCodeService $qrCodeService,
    ) {}

    /**
     * GET màn hình Staff UC-STAFF-04
     */
    public function index()
    {
        return view('staff.issue-support');
    }

    /**
     * API: xác định sự cố đặt vé theo input của khách.
     * READ-ONLY.
     */
    public function diagnose(StaffIssueSupportRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Audit base
        AuditLogService::log(
            'STAFF_ISSUE_DIAGNOSE',
            'Booking',
            0,
            null,
            [
                'input_type' => $validated['input_type'],
                'input_value' => $this->maskInput($validated['input_type'], $validated['input_value']),
            ]
        );

        // 1) Nếu staff nhập QR content → validate checksum/format
        $booking = null;
        $ticket = null;
        $qrValidation = null;

        if ($validated['input_type'] === 'qr_content') {
            $qrValidation = $this->qrCodeService->validateQR($validated['input_value']);
            if (! $qrValidation['valid']) {
                return response()->json([
                    'success' => true,
                    'issue'   => [
                        'type' => 'QR_NOT_VISIBLE',
                        'title' => 'QR Code không hiển thị/không hợp lệ',
                        'summary' => $this->mapQrErrorToSummary($qrValidation['error']),
                        'actions' => [
                            'Nếu có thể, yêu cầu khách tải lại vé/QR từ email hoặc ứng dụng.',
                            'Nếu hệ thống báo lỗi QR, ghi nhận sự cố và chuyển Admin xử lý.',
                        ],
                    ],
                    'debug' => [
                        'qr_error' => $qrValidation['error'],
                    ],
                ]);
            }

            // Nếu QR hợp lệ, lookup theo code (booking hoặc ticket)
            if ($qrValidation['type'] === 'booking') {
                $bookingPaginator = $this->lookupService->searchBookings([
                    'search_type' => 'booking_code',
                    'search_value' => $qrValidation['code'],
                    'per_page' => 1,
                ]);

                $bookingItems = method_exists($bookingPaginator, 'items')
                    ? $bookingPaginator->items()
                    : [];

                $booking = collect($bookingItems)->first();

                if (! $booking) {
                    return $this->notFoundResponse();
                }
            } else {
                // ticket lookup: dùng searchBookings theo ticket_code để lấy booking
                $bookingPaginator = $this->lookupService->searchBookings([
                    'search_type' => 'ticket_code',
                    'search_value' => $qrValidation['code'],
                    'per_page' => 1,
                ]);

                $bookingItems = method_exists($bookingPaginator, 'items')
                    ? $bookingPaginator->items()
                    : [];

                $booking = collect($bookingItems)->first();

                if (! $booking) {
                    return $this->notFoundResponse();
                }
            }
        } else {
            // 2) Lookup booking/vé từ input thường
            $inputType = $validated['input_type'];

            $mapping = [
                'booking_code' => 'booking_code',
                'ticket_code'  => 'ticket_code',
                'phone'        => 'phone',
                'email'        => 'email',
            ];

            $criteria = [
                'search_type' => $mapping[$inputType],
                'search_value' => $validated['input_value'],
                'per_page' => 1,
            ];

            $paginator = $this->lookupService->searchBookings($criteria);
            $items = method_exists($paginator, 'items') ? $paginator->items() : [];
            $found = collect($items)->first();

            if (! $found) {
                return $this->notFoundResponse();
            }

            $booking = $this->lookupService->getBookingDetail((int) $found->id);

        }

        if (! $booking) {
            return $this->notFoundResponse();
        }

        // Lấy booking detail để evaluate trạng thái
        $bookingDetail = $booking instanceof \App\Models\Booking
            ? $booking
            : $this->lookupService->getBookingDetail((int) $booking->id);

        $bookingError = app(\App\Services\IssueSupportService::class)
            ->diagnoseFromBooking($bookingDetail);


        return response()->json([
            'success' => true,
            'issue' => $bookingError,
            'booking' => [
                'booking_code' => $bookingDetail->booking_code,
                'status' => $bookingDetail->status,
                'payment_status' => $bookingDetail->payment_status,
                'expired_at' => $bookingDetail->expired_at,
                'final_amount' => $bookingDetail->final_amount,
                'movie' => $bookingDetail->showtime?->movie?->title,
                'showtime' => $bookingDetail->showtime?->start_time,
            ],
        ]);
    }

    private function notFoundResponse(): JsonResponse
    {
        AuditLogService::log('STAFF_ISSUE_NOT_FOUND', 'Booking', 0, null, []);

        return response()->json([
            'success' => true,
            'issue' => [
                'type' => 'NOT_FOUND',
                'title' => 'Không tìm thấy booking/vé',
                'summary' => 'Hệ thống không tìm thấy dữ liệu liên quan.',
                'actions' => [
                    'Yêu cầu khách kiểm tra lại mã booking/email/số điện thoại.',
                    'Nếu có mã QR/ticket, yêu cầu cung cấp mã chính xác (TK-/BK-).',
                ],
            ],
        ]);
    }



    private function maskInput(string $type, string $value): string
    {
        return match ($type) {
            'phone' => preg_replace('/(\d{3})\d{4}(\d{2})/', '$1****$2', $value),
            'email' => preg_replace('/(^[^@]+).+(@.+$)/', '$1***$2', $value),
            default => $value,
        };
    }

    private function mapQrErrorToSummary(string $qrError): string
    {
        return match ($qrError) {
            'INVALID_QR_FORMAT' => 'QR Code không đúng định dạng hoặc không phải mã hợp lệ.',
            'QR_TAMPERED' => 'QR Code có thể bị thay đổi/giả mạo.',
            default => 'Không đọc được QR hoặc hệ thống trả về lỗi QR.',
        };
    }
}

