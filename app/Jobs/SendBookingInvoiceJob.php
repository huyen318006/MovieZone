<?php

namespace App\Jobs;

use App\Mail\BookingInvoiceMail;
use App\Models\Booking;
use App\Models\SepayOrder;
use App\Notifications\BookingPaidNotification;
use App\Services\TicketPDFService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Job chạy ngầm: Sinh PDF vé + Gửi email hoá đơn + Thông báo cho user.
 *
 * Được dispatch sau khi thanh toán thành công (cả online lẫn coin)
 * để người dùng không phải chờ đợi.
 */
class SendBookingInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Số lần thử lại nếu job fail (email server tạm lỗi, v.v.)
     */
    public int $tries = 3;

    /**
     * Thời gian chờ tối đa cho job (giây).
     * PDF rendering + email gửi có thể mất tới 30s.
     */
    public int $timeout = 120;

    protected int $orderId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $order = SepayOrder::find($this->orderId);

        if (! $order) {
            Log::warning('SendBookingInvoiceJob: Order not found', ['order_id' => $this->orderId]);
            return;
        }

        $booking = $order->booking;

        // Bước 1: Sinh PDF vé (nếu là đơn booking có vé)
        $pdfPath = null;
        if ($order->isBookingOrder() && $booking) {
            try {
                $pdfService = app(TicketPDFService::class);
                $pdfPath = $pdfService->generateBookingTicketsPDF($booking->fresh());
            } catch (\Exception $e) {
                Log::error('SendBookingInvoiceJob: Failed to generate PDF', [
                    'order_code' => $order->order_code,
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
                // Không throw — vẫn tiếp tục gửi email (không có PDF đính kèm)
            }
        }

        // Bước 2: Gửi email hoá đơn
        $customerEmail = $order->getCustomerEmail();
        $user = $booking ? $booking->user : null;

        if ($order->isBookingOrder() && $customerEmail) {
            try {
                Mail::to($customerEmail)->send(
                    new BookingInvoiceMail($order->fresh(), $user, $pdfPath)
                );

                // Đánh dấu đã gửi email vào metadata
                $meta = $order->metadata ?? [];
                $meta['email_sent'] = true;
                $meta['email_sent_at'] = now()->toIso8601String();
                $meta['email_sent_to'] = $customerEmail;
                $meta['pdf_attached'] = ! empty($pdfPath);
                $order->update(['metadata' => $meta]);

                Log::info('SendBookingInvoiceJob: Email sent', [
                    'order_code' => $order->order_code,
                    'email' => $customerEmail,
                    'pdf_attached' => ! empty($pdfPath),
                ]);
            } catch (\Exception $e) {
                Log::error('SendBookingInvoiceJob: Failed to send email', [
                    'order_code' => $order->order_code,
                    'error' => $e->getMessage(),
                ]);
                // Throw để queue tự retry theo $tries
                throw $e;
            }
        }

        // Bước 3: Gửi notification trong hệ thống cho user
        if ($order->isBookingOrder() && $user) {
            try {
                $user->notify(new BookingPaidNotification($order->fresh()));
            } catch (\Exception $e) {
                Log::error('SendBookingInvoiceJob: Failed to send notification', [
                    'order_code' => $order->order_code,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
