<?php

namespace App\Notifications;

use App\Models\SepayOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BookingPaidNotification extends Notification
{
    use Queueable;

    protected SepayOrder $order;

    public function __construct(SepayOrder $order)
    {
        $this->order = $order;
    }

    /**
     * Kênh gửi thông báo
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * Dữ liệu lưu vào bảng notifications
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'booking_paid',
            'order_code' => $this->order->order_code,
            'movie_title' => $this->order->getBookingInfo('movie_title'),
            'cinema' => $this->order->getBookingInfo('cinema'),
            'showtime' => $this->order->getBookingInfo('showtime'),
            'show_date' => $this->order->getBookingInfo('show_date'),
            'seats' => $this->order->getSeatCodesFormatted(),
            'amount' => $this->order->amount,
            'message' => '🎬 Đặt vé thành công! Vé xem phim "' . $this->order->getBookingInfo('movie_title') . '" - ' . $this->order->getSeatCodesFormatted(),
            'paid_at' => $this->order->paid_at?->toIso8601String(),
        ];
    }
}
