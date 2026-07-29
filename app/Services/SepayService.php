<?php

namespace App\Services;

use App\Mail\BookingInvoiceMail;
use App\Models\Payment;
use App\Models\SepayOrder;
use App\Notifications\BookingPaidNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SepayService
{
    /**
     * Lấy danh sách tất cả gói thanh toán
     */
    public function getPackages(): array
    {
        return config('sepay.packages', []);
    }

    /**
     * Lấy thông tin 1 gói theo ID
     */
    public function getPackage(string $packageId): ?array
    {
        $packages = $this->getPackages();

        foreach ($packages as $package) {
            if ($package['id'] === $packageId) {
                return $package;
            }
        }

        return null;
    }

    /**
     * Tạo đơn hàng mới
     */
    public function createOrder(string $packageId): ?SepayOrder
    {
        $package = $this->getPackage($packageId);

        if (! $package) {
            return null;
        }

        $orderCode = $this->generateOrderCode();

        return SepayOrder::create([
            'order_code' => $orderCode,
            'package_id' => $package['id'],
            'package_name' => $package['name'],
            'amount' => $package['amount'],
            'status' => 'pending',
        ]);
    }

    /**
     * Bảng giá ghế
     */
    const SEAT_PRICES = [
        'standard' => 10000,
        'vip' => 150000,
        'sweetbox' => 200000,
        'demo' => 10000,
    ];

    /**
     * Tạo đơn hàng booking vé phim
     */
    public function createBookingOrder(array $bookingData): ?SepayOrder
    {
        $seats = $bookingData['seats'] ?? [];

        if (empty($seats)) {
            return null;
        }

        // Tính tổng tiền từ danh sách ghế
        $totalAmount = 0;
        $seatDetails = [];

        foreach ($seats as $seat) {
            $type = $seat['type'] ?? 'standard';
            $price = self::SEAT_PRICES[$type] ?? self::SEAT_PRICES['standard'];
            $totalAmount += $price;
            $seatDetails[] = [
                'code' => $seat['code'],
                'type' => $type,
                'price' => $price,
            ];
        }

        $orderCode = $this->generateOrderCode();

        return SepayOrder::create([
            'order_code' => $orderCode,
            'package_id' => 'booking',
            'package_name' => 'Vé xem phim',
            'amount' => $totalAmount,
            'status' => 'pending',
            'metadata' => [
                'movie_title' => $bookingData['movie_title'] ?? '',
                'cinema' => $bookingData['cinema'] ?? '',
                'room' => $bookingData['room'] ?? '',
                'showtime' => $bookingData['showtime'] ?? '',
                'show_date' => $bookingData['show_date'] ?? '',
                'format' => $bookingData['format'] ?? '',
                'seats' => $seatDetails,
                'seat_count' => count($seatDetails),
                'customer_email' => $bookingData['customer_email'] ?? '',
                'customer_name' => $bookingData['customer_name'] ?? null,
            ],
        ]);
    }

    /**
     * Sinh mã đơn hàng unique
     */
    protected function generateOrderCode(): string
    {
        $prefix = config('sepay.order_prefix', 'DH');

        do {
            $code = $prefix.strtoupper(Str::random(8));
        } while (SepayOrder::where('order_code', $code)->exists());

        return $code;
    }

    /**
     * Tạo URL QR code VietQR
     */
    public function generateQrUrl(SepayOrder $order): string
    {
        $bankCode = config('sepay.bank_code', 'MBBank');
        $accountNumber = config('sepay.bank_account', '');
        $amount = $order->amount;
        $content = $order->order_code;

        // Sử dụng API VietQR để tạo QR code
        return "https://qr.sepay.vn/img?acc={$accountNumber}"
            ."&bank={$bankCode}"
            ."&amount={$amount}"
            ."&des={$content}";
    }

    /**
     * Kiểm tra thanh toán qua SePay API
     *
     * Gọi SePay User API để tìm giao dịch khớp với mã đơn hàng
     */
    public function checkPayment(string $orderCode): array
    {
        $order = SepayOrder::where('order_code', $orderCode)->first();

        if (! $order) {
            return ['status' => 'not_found', 'message' => 'Không tìm thấy đơn hàng'];
        }

        // Nếu đã thanh toán rồi, trả về luôn
        if ($order->isPaid()) {
            return [
                'status' => 'paid',
                'message' => 'Đã thanh toán',
                'order' => $order,
            ];
        }

        // Nếu đơn hết hạn
        if ($order->isExpired()) {
            $order->markAsExpired();

            return ['status' => 'expired', 'message' => 'Đơn hàng đã hết hạn'];
        }

        // Gọi SePay API kiểm tra giao dịch
        try {
            $apiToken = config('sepay.api_token');
            $apiUrl = config('sepay.api_url');
            $accountNumber = config('sepay.bank_account');

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiToken,
                'Content-Type' => 'application/json',
            ])->get($apiUrl, [
                'account_number' => $accountNumber,
                'transaction_date_min' => $order->created_at->format('Y-m-d H:i:s'),
            ]);

            if (! $response->successful()) {
                Log::warning('SePay API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return ['status' => 'pending', 'message' => 'Đang chờ thanh toán'];
            }

            $data = $response->json();
            $transactions = $data['transactions'] ?? [];

            // Tìm giao dịch khớp với mã đơn hàng
            foreach ($transactions as $transaction) {
                $content = $transaction['transaction_content'] ?? '';
                $amountIn = (int) ($transaction['amount_in'] ?? 0);

                // Kiểm tra nội dung chuyển khoản chứa mã đơn hàng
                // và số tiền >= số tiền đơn hàng
                if (
                    stripos($content, $orderCode) !== false
                    && $amountIn >= $order->amount
                ) {
                    // Tìm thấy giao dịch khớp → đánh dấu đã thanh toán
                    $transactionId = (string) ($transaction['id'] ?? $transaction['reference_number'] ?? '');

                    $order->markAsPaid($transactionId, $transaction);

                    // Sync trạng thái sang Booking model (nếu có liên kết)
                    if ($order->booking_id && $order->booking) {
                        $order->booking->update([
                            'status' => 'PAID',
                            'payment_status' => 'PAID',
                            'paid_at' => now(),
                        ]);

                        // Tạo Payment record
                        Payment::create([
                            'booking_id' => $order->booking_id,
                            'payment_method' => 'ONLINE',
                            'amount' => $order->amount,
                            'transaction_code' => $transactionId,
                            'status' => 'SUCCESS',
                            'paid_at' => now(),
                        ]);
                        // Tự động tích Coin Membership
                        app(\App\Services\MembershipService::class)->awardBookingCoin($order->booking);
                    }

                    Log::info('SePay payment confirmed', [
                        'order_code' => $orderCode,
                        'transaction_id' => $transactionId,
                        'amount' => $amountIn,
                        'booking_id' => $order->booking_id,
                    ]);

                    // === SINH MÃ VÉ (Ticket Code + QR Code) ===
                    $pdfPath = null;
                    if ($order->booking_id && $order->booking) {
                        try {
                            $ticketService = app(TicketService::class);
                            $tickets = DB::transaction(function () use ($ticketService, $order) {
                                return $ticketService->generateTicketsForBooking($order->booking);
                            });

                            Log::info('Tickets generated for booking', [
                                'order_code'   => $orderCode,
                                'booking_code' => $order->booking->booking_code,
                                'ticket_count' => $tickets->count(),
                            ]);

                            // Sinh PDF vé kèm QR Code
                            $pdfService = app(TicketPDFService::class);
                            $pdfPath = $pdfService->generateBookingTicketsPDF($order->booking->fresh());

                        } catch (\Exception $ticketEx) {
                            // Không throw — thanh toán vẫn thành công, ticket có thể retry sau
                            Log::error('Failed to generate tickets or PDF', [
                                'order_code' => $orderCode,
                                'booking_id' => $order->booking_id,
                                'error'      => $ticketEx->getMessage(),
                            ]);
                        }
                    }

                    // === GỬI EMAIL HOÁ ĐƠN TỰ ĐỘNG (kèm PDF vé) ===
                    try {
                        $customerEmail = $order->getCustomerEmail();
                        $user = $order->booking ? $order->booking->user : null;

                        if ($customerEmail) {
                            Mail::to($customerEmail)->send(
                                new BookingInvoiceMail($order->fresh(), $user, $pdfPath)
                            );

                            // Đánh dấu đã gửi email
                            $meta = $order->metadata ?? [];
                            $meta['email_sent'] = true;
                            $meta['email_sent_at'] = now()->toIso8601String();
                            $meta['email_sent_to'] = $customerEmail;
                            $meta['pdf_attached'] = !empty($pdfPath);
                            $order->update(['metadata' => $meta]);

                            Log::info('Booking invoice email sent', [
                                'order_code'   => $orderCode,
                                'email'        => $customerEmail,
                                'pdf_attached' => !empty($pdfPath),
                            ]);
                        }

                        // Tạo thông báo trong hệ thống cho user
                        if ($user) {
                            $user->notify(new BookingPaidNotification($order->fresh()));
                        }
                    } catch (\Exception $mailEx) {
                        // Không throw — email lỗi không ảnh hưởng đến thanh toán
                        Log::error('Failed to send booking invoice email', [
                            'order_code' => $orderCode,
                            'error' => $mailEx->getMessage(),
                        ]);
                    }

                    return [
                        'status' => 'paid',
                        'message' => 'Thanh toán thành công',
                        'order' => $order->fresh(),
                    ];
                }
            }

            return ['status' => 'pending', 'message' => 'Đang chờ thanh toán'];

        } catch (\Exception $e) {
            Log::error('SePay API exception', [
                'message' => $e->getMessage(),
                'order_code' => $orderCode,
            ]);

            return ['status' => 'pending', 'message' => 'Đang chờ thanh toán'];
        }
    }

    /**
     * Tìm order theo mã
     */
    public function getOrderByCode(string $orderCode): ?SepayOrder
    {
        return SepayOrder::where('order_code', $orderCode)->first();
    }

    /**
     * Tạo hoá đơn và gửi email cho khách hàng
     */
    protected function createInvoiceAndSendEmail(SepayOrder $order): void
    {
        try {
            // Chỉ tạo invoice cho đơn booking đã thanh toán
            if (! $order->isPaid()) {
                return;
            }

            // Kiểm tra đã tạo invoice chưa (tránh duplicate)
            if ($order->invoice) {
                return;
            }

            $invoice = Invoice::createFromOrder($order);

            // Lấy email khách hàng từ metadata
            $customerEmail = $order->metadata['customer_email'] ?? '';

            if (empty($customerEmail)) {
                Log::warning('No customer email for invoice', [
                    'order_code' => $order->order_code,
                    'invoice_code' => $invoice->invoice_code,
                ]);

                return;
            }

            // Gửi email
            Mail::to($customerEmail)->send(new InvoiceMail($invoice));
            $invoice->markEmailSent();

            Log::info('Invoice email sent successfully', [
                'invoice_code' => $invoice->invoice_code,
                'email' => $customerEmail,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create invoice or send email', [
                'order_code' => $order->order_code,
                'error' => $e->getMessage(),
            ]);

            // Nếu invoice đã tạo nhưng gửi mail lỗi
            if (isset($invoice)) {
                $invoice->markEmailFailed();
            }
        }
    }
}
