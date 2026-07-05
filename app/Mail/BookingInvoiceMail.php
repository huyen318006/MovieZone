<?php

namespace App\Mail;

use App\Models\SepayOrder;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public SepayOrder $order;
    public ?User $user;
    public ?string $pdfPath;

    /**
     * Create a new message instance.
     */
    public function __construct(SepayOrder $order, ?User $user = null, ?string $pdfPath = null)
    {
        $this->order = $order;
        $this->user = $user;
        $this->pdfPath = $pdfPath;
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

    /**
     * Get the attachments for the message.
     *
     * Đính kèm file PDF vé (nếu có).
     */
    public function attachments(): array
    {
        if ($this->pdfPath && file_exists($this->pdfPath)) {
            return [
                Attachment::fromPath($this->pdfPath)
                    ->as('MovieZone_Tickets_' . $this->order->order_code . '.pdf')
                    ->withMime('application/pdf'),
            ];
        }

        return [];
    }
}

