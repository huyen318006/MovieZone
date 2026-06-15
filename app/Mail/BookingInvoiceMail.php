<?php

namespace App\Mail;

use App\Models\SepayOrder;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public SepayOrder $order;
    public ?User $user;

    /**
     * Create a new message instance.
     */
    public function __construct(SepayOrder $order, ?User $user = null)
    {
        $this->order = $order;
        $this->user = $user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🎬 MovieZone - Hoá Đơn Vé Phim #' . $this->order->order_code,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-invoice',
            with: [
                'order' => $this->order,
                'user' => $this->user,
            ],
        );
    }
}
