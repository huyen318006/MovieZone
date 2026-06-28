<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StaffIssueSupportRequest;
use App\Services\AuditLogService;
use App\Services\BookingLookupService;
use App\Services\QRCodeService;
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
                $booking = $this->lookupService->searchBookings([
                    'search_type' => 'booking_code',
                    'search_value' => $qrValidation['code'],
                    'per_page' => 1,
                ])->getCollection()->first();

                if (! $booking) {
                    return $this->notFoundResponse();
                }
            } else {
                // ticket lookup: dùng searchBookings theo ticket_code để lấy booking
                $page = $this->lookupService->searchBookings([
                    'search_type' => 'ticket_code',
                    'search_value' => $qrValidation['code'],
                    'per_page' => 1,
                ]);
                $booking = $page->getCollection()->first();

                if (! $booking) {
                    return $this->notFoundResponse();
                }
            }
        } else {
            // 2) Lookup booking/vé từ input thường
            $searchType = $validated['input_type'];
            // input_type mapping → search_type của BookingLookupService
            $mapping = [
                'booking_code' => 'booking_code',
                'ticket_code'  => 'ticket_code',
                'phone'        => 'phone',
                'email'        => 'email',
            ];

            $criteria = [
                'search_type' => $mapping[$searchType],
                'search_value' => $validated['input_value'],
                'per_page' => 1,
            ];

            $found = $this->lookupService->searchBookings($criteria)->getCollection()->first();
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

        $bookingError = $this->determineIssue($bookingDetail);

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

    /**
     * Determine issue based on booking/ticket state.
     * Only recommends actions (no data mutation).
     */
    private function determineIssue(\App\Models\Booking $booking): array
    {
        // BR02/BR04: Không tự sửa/hoàn tiền; chỉ khuyến nghị

        // A3 Booking hết hạn
        if ($booking->expired_at && now()->greaterThan($booking->expired_at)) {
            return [
                'type' => 'BOOKING_EXPIRED',
                'title' => 'Booking hết hạn',
                'summary' => 'Booking đã hết hạn trong hệ thống.',
                'actions' => [
                    'Thông báo khách cần đặt vé lại.',
                    'Không khôi phục booking nếu staff không có quyền.',
                    'Nếu staff được phép đặc biệt (E2), hướng dẫn chuyển Admin.',
                ],
            ];
        }

        // A2 Payment chưa cập nhật
        if ($booking->payment_status !== 'PAID') {
            // pending/failed/unpaid/refunded đều thuộc phạm vi hướng dẫn
            if ($booking->payment_status === 'UNPAID') {
                return [
                    'type' => 'PAYMENT_NOT_UPDATED',
                    'title' => 'Thanh toán chưa cập nhật',
                    'summary' => 'Booking chưa ở trạng thái thanh toán thành công.',
                    'actions' => [
                        'Hướng dẫn khách chờ hoặc kiểm tra lại giao dịch.',
                        'Nếu cần đối soát, chuyển sự cố cho Admin/bộ phận đối soát.',
                        'Staff không tự xác nhận payment.',
                    ],
                ];
            }

            if (in_array($booking->payment_status, ['FAILED', 'REFUNDED'], true)) {
                return [
                    'type' => 'PAYMENT_FAILED_OR_REFUNDED',
                    'title' => 'Dữ liệu thanh toán không khớp',
                    'summary' => 'Trạng thái payment đang FAILED/REFUNDED.',
                    'actions' => [
                        'Chuyển cho Admin hoặc bộ phận đối soát.',
                        'Không tự xác nhận/không tự chỉnh sửa payment.',
                    ],
                ];
            }

            return [
                'type' => 'PAYMENT_UNKNOWN',
                'title' => 'Payment dữ liệu chưa rõ',
                'summary' => 'Trạng thái payment không khớp thông tin khách cung cấp.',
                'actions' => [
                    'Chuyển sự cố cho Admin hoặc bộ phận đối soát.',
                ],
            ];
        }

        // A4 Vé đã check-in
        // Nếu có ticket USED → issue check-in
        $usedTickets = $booking->tickets?->where('status', 'USED') ?? collect();
        if ($usedTickets->isNotEmpty()) {
            return [
                'type' => 'TICKET_ALREADY_CHECKED_IN',
                'title' => 'Vé đã check-in',
                'summary' => 'Một hoặc nhiều vé đã được sử dụng.',
                'actions' => [
                    'Thông báo vé đã được sử dụng.',
                    'Không check-in lại vé.',
                ],
            ];
        }

        // Sai thông tin suất chiếu (booking status/payment_status ok)
        if ($booking->showtime && $booking->showtime->status !== 'ACTIVE') {
            return [
                'type' => 'SHOWTIME_MISMATCH_OR_INACTIVE',
                'title' => 'Sai thông tin suất chiếu / suất chiếu không hoạt động',
                'summary' => 'Trạng thái suất chiếu không phù hợp.',
                'actions' => [
                    'Giải thích tình trạng cho khách dựa trên booking đang tồn tại.',
                    'Nếu cần can thiệp dữ liệu → chuyển Admin (E2).',
                ],
            ];
        }

        // Default: PAID + chưa check-in → có thể là QR hiển thị/khác
        return [
            'type' => 'READY_FOR_CHECKIN',
            'title' => 'Booking/vé hợp lệ, sẵn sàng xử lý',
            'summary' => 'Booking đã thanh toán và chưa có vé đã check-in.',
            'actions' => [
                'Hướng dẫn staff sử dụng màn hình check-in QR (UC-STAFF-01).',
                'Nếu QR không hiển thị, ghi nhận và chuyển Admin.',
            ],
        ];
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

