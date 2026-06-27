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

class AccountError extends Mailable
{
    use Queueable, SerializesModels;



    public $user;
    public $reason;

    // Nhận dữ liệu đơn hàng được truyền từ Controller sang
    public function __construct($user, $reason)
    {
        $this->user = $user;
        $this->reason = $reason;
    }

    public function build()
    {
        return $this->subject('🔔 [MovieZone] Thông báo tạm khóa tài khoản của bạn')
            ->view('admin.account.mailaccount'); // Gọi đúng file giao diện ở Bước 2
    }
}
