<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingCancellation;
use App\Models\Payment;
use Illuminate\Support\Collection;

class IssueSupportService
{
    /**
     * Lấy danh sách các booking đang "CÓ VẤN ĐỀ" cần staff xử lý.
     * Hiển thị mặc định trên màn hình Hỗ trợ sự cố để staff không phải tìm thủ công.
     *
     * Bao gồm:
     * - Booking bị hủy (CANCELLED)
     * - Booking hết hạn chưa thanh toán (EXPIRED)
     * - Booking có thanh toán FAILED / chờ hoàn tiền
     * - Booking có cảnh báo thanh toán muộn (LATE_PAYMENT pending_refund)
     *
     * @param int $limit
     * @return Collection
     */
    public function listProblemBookings(int $limit = 20): Collection
    {
        // 1. Booking với trạng thái CANCELLED / EXPIRED / chưa đủ thanh toán
        $bookings = Booking::with([
            'user:id,name,phone,email',
            'showtime:id,movie_id,start_time,end_time',
            'showtime.movie:id,title',
            'showtime.cinema:id,name',
            'bookingSeats:id,booking_id,seat_code',
            'cancellation',
            'latePaymentAlert',
        ])->where(function ($q) {
            $q->whereIn('status', ['CANCELLED', 'EXPIRED'])
                ->orWhere('payment_status', 'FAILED')
                ->orWhere('payment_status', 'REFUNDED');
        })
        ->orderByDesc('updated_at')
        ->limit($limit)
        ->get();

        // 2. Gán loại vấn đề cho từng booking
        return $bookings->map(function ($booking) {
            $issueType = $this->classifyProblem($booking);

            return (object) [
                'id'             => $booking->id,
                'booking_code'   => $booking->booking_code,
                'status'         => $booking->status,
                'payment_status' => $booking->payment_status,
                'issue_type'     => $issueType,
                'issue_label'    => $this->problemLabel($issueType),
                'customer_name'  => $booking->user?->name ?? 'N/A',
                'customer_phone' => $booking->user?->phone ?? 'N/A',
                'movie_title'    => $booking->showtime?->movie?->title ?? 'N/A',
                'cinema_name'    => $booking->showtime?->cinema?->name ?? 'N/A',
                'showtime'       => $booking->showtime?->start_time,
                'final_amount'   => $booking->final_amount,
                'seats'          => $booking->bookingSeats->pluck('seat_code')->toArray(),
                'updated_at'     => $booking->updated_at,
            ];
        });
    }

    /**
     * Phân loại vấn đề của một booking.
     */
    public function classifyProblem(Booking $booking): string
    {
        // Thanh toán muộn / cần hoàn tiền ưu tiên cao nhất
        $late = $booking->latePaymentAlert;
        if ($late && $booking->payment_status === 'PAID') {
            return 'LATE_PAYMENT_REQUIRES_REFUND';
        }

        if ($booking->status === 'EXPIRED') {
            return 'BOOKING_EXPIRED';
        }

        if ($booking->status === 'CANCELLED') {
            if ($booking->payment_status === 'REFUNDED') {
                return 'CANCELLED_REFUNDED';
            }
            if ($booking->payment_status === 'PAID') {
                return 'CANCELLED_PAID_REQUIRES_REFUND';
            }
            return 'BOOKING_CANCELLED';
        }

        if ($booking->payment_status === 'FAILED') {
            return 'PAYMENT_FAILED';
        }

        if ($booking->payment_status === 'REFUNDED') {
            return 'PAYMENT_REFUNDED';
        }

        return 'UNKNOWN_ISSUE';
    }

    /**
     * Nhãn tiếng Việt cho từng loại vấn đề.
     */
    public function problemLabel(string $issueType): string
    {
        return match ($issueType) {
            'BOOKING_CANCELLED'                => 'Booking bị hủy',
            'CANCELLED_REFUNDED'               => 'Đã hủy & hoàn tiền',
            'CANCELLED_PAID_REQUIRES_REFUND'   => 'Đã hủy nhưng còn nợ hoàn tiền',
            'BOOKING_EXPIRED'                  => 'Hết hạn chưa thanh toán',
            'PAYMENT_FAILED'                   => 'Thanh toán thất bại',
            'PAYMENT_REFUNDED'                 => 'Đã hoàn tiền',
            'LATE_PAYMENT_REQUIRES_REFUND'     => 'Thanh toán muộn cần hoàn tiền',
            default                            => 'Có vấn đề',
        };
    }

    public function diagnoseFromBooking(Booking $booking): array
    {
        // A1: Booking đã hủy
        if ($booking->status === 'CANCELLED') {
            return [
                'type' => 'BOOKING_CANCELLED',
                'title' => 'Booking đã bị hủy',
                'summary' => 'Booking đã bị hủy trong hệ thống.',
                'actions' => [
                    'Thông báo cho khách biết booking đã bị hủy.',
                    'Nếu khách đã thanh toán, hướng dẫn chuyển Admin xử lý hoàn tiền.',
                ],
            ];
        }

        // A: ĐÃ THANH TOÁN → kiểm tra vé & suất chiếu (KHÔNG xét hết hạn,
        // vì expired_at là hạn giữ chỗ — booking đã PAID vẫn hợp lệ dù quá hạn)
        if ($booking->payment_status === 'PAID') {
            // A4: Vé đã check-in
            $usedTickets = $booking->tickets ? $booking->tickets->where('status', 'USED') : collect();
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

// Kiểm tra trạng thái suất chiếu theo đúng enum: OPEN / CLOSED / CANCELLED
            if ($booking->showtime && $booking->showtime->status === 'CANCELLED') {
                return [
                    'type' => 'SHOWTIME_CANCELLED',
                    'title' => 'Suất chiếu đã bị hủy',
                    'summary' => 'Suất chiếu của booking đã bị hủy trong hệ thống.',
                    'actions' => [
                        'Giải thích cho khách biết suất chiếu đã bị hủy.',
                        'Hướng dẫn khách chuyển sang suất chiếu/đặt vé khác.',
                        'Nếu khách đã thanh toán, hướng dẫn chuyển Admin xử lý hoàn tiền.',
                    ],
                ];
            }

            if ($booking->showtime && $booking->showtime->status === 'CLOSED') {
                return [
                    'type' => 'SHOWTIME_CLOSED',
                    'title' => 'Suất chiếu đã đóng',
                    'summary' => 'Suất chiếu không còn nhận khách/xem vé (đã đóng).',
                    'actions' => [
                        'Thông báo suất chiếu đã đóng.',
                        'Nếu cần can thiệp dữ liệu → chuyển Admin (E2).',
                    ],
                ];
            }

            // Default: PAID + chưa check-in
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

// A3: CHƯA THANH TOÁN → kiểm tra hết hạn giữ chỗ.
        // Áp dụng cho mọi trạng thái chưa thanh toán (PENDING, PENDING_PAYMENT, PENDING_CASH_PAYMENT, UNPAID)
        // để phát hiện booking vẫn "chờ thanh toán" nhưng thực chất đã hết thời hạn giữ chỗ.
        $pendingStatuses = ['PENDING', 'PENDING_PAYMENT', 'PENDING_CASH_PAYMENT'];
        $paymentNotPaid = $booking->payment_status !== 'PAID';

        if (
            $paymentNotPaid
            && in_array($booking->status, $pendingStatuses, true)
            && $booking->expired_at
            && now()->greaterThan($booking->expired_at)
        ) {
            return [
                'type' => 'BOOKING_EXPIRED',
                'title' => 'Booking hết hạn thanh toán',
                'summary' => 'Booking đang ở trạng thái chờ thanh toán nhưng đã hết hạn giữ chỗ trong hệ thống.',
                'actions' => [
                    'Thông báo khách đã hết thời gian thanh toán, cần đặt vé lại.',
                    'Ghế đã được giải phóng cho khách khác.',
                    'Nếu khách đã chuyển khoản nhưng đơn chưa cập nhật, ghi nhận để chuyển Admin đối soát.',
                ],
            ];
        }

        // A2: Vẫn trong thời hạn giữ chỗ → Payment chưa cập nhật / đang chờ thanh toán
        if ($paymentNotPaid && $booking->payment_status === 'UNPAID') {
            $waitingOnline = $booking->status === 'PENDING_PAYMENT';
            $waitingCash = $booking->status === 'PENDING_CASH_PAYMENT';

            return [
                'type' => $waitingCash ? 'PENDING_CASH_PAYMENT' : ($waitingOnline ? 'PENDING_PAYMENT' : 'PAYMENT_NOT_UPDATED'),
                'title' => $waitingCash
                    ? 'Booking đang chờ xác nhận thanh toán tiền mặt'
                    : ($waitingOnline ? 'Booking đang chờ thanh toán trực tuyến' : 'Thanh toán chưa cập nhật'),
                'summary' => $waitingCash
                    ? 'Booking đang chờ staff/bộ phận xác nhận thanh toán tiền mặt tại quầy.'
                    : ($waitingOnline
                        ? 'Booking đang trong thời hạn chờ khách thanh toán trực tuyến.'
                        : 'Booking chưa ở trạng thái thanh toán thành công.'),
                'actions' => [
                    $waitingCash
                        ? 'Kiểm tra và xác nhận thanh toán tiền mặt nếu khách đã nộp tiền.'
                        : 'Hướng dẫn khách hoàn tất thanh toán còn hiệu lực.',
                    'Nếu cần đối soát, chuyển sự cố cho Admin/bộ phận đối soát.',
                    'Staff không tự xác nhận payment trừ khi được phân quyền.',
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
}

