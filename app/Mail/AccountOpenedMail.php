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

    class AccountOpenedMail extends Mailable
    {
        use Queueable, SerializesModels;



        public $user;

        // Nhận dữ liệu đơn hàng được truyền từ Controller sang
        public function __construct($user)
        {
            $this->user = $user;

        }

        public function build()
        {
            return $this->subject(' 🔓 [MovieZone] Tài khoản của bạn đã được mở')
                ->view('admin.account.AccountOpenedMail'); // Gọi đúng file giao diện ở Bước 2
        }
    }

    ?>
