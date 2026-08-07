<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Collection;

class IssueSupportService
{
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

        // A3: CHƯA THANH TOÁN → kiểm tra hết hạn giữ chỗ
        if ($booking->expired_at && now()->greaterThan($booking->expired_at)) {
            return [
                'type' => 'BOOKING_EXPIRED',
                'title' => 'Booking hết hạn',
                'summary' => 'Booking chưa thanh toán và đã hết hạn giữ chỗ trong hệ thống.',
                'actions' => [
                    'Thông báo khách cần đặt vé lại.',
                    'Không khôi phục booking nếu staff không có quyền.',
                    'Nếu staff được phép đặc biệt (E2), hướng dẫn chuyển Admin.',
                ],
            ];
        }

        // A2: Payment chưa cập nhật / không rõ
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
}

