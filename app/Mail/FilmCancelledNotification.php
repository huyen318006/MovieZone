<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FilmCancelledNotification extends Mailable
{
    use Queueable, SerializesModels;



    public $booking;

    // Nhận dữ liệu đơn hàng được truyền từ Controller sang
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    public function build()
    {
        return $this->subject('🔔 [MovieZone] Thông báo hủy suất chiếu và hoàn tiền vé')
            ->view('admin.film.email_cancelled'); // Gọi đúng file giao diện ở Bước 2
    }
}
