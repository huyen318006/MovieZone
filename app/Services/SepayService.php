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

        // Nếu đơn hết hạn (còn pending nhưng quá thời gian)
        if ($order->isExpired()) {
            $order->markAsExpired();

            return ['status' => 'expired', 'message' => 'Đơn hàng đã hết hạn'];
        }

        // Nếu đơn đã bị đánh dấu expired trước đó → từ chối luôn, không kiểm tra giao dịch
        if ($order->status === 'expired') {
            return ['status' => 'expired', 'message' => 'Đơn hàng đã hết hạn, không thể thanh toán'];
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
                    // Tìm thấy giao dịch khớp → xử lý trong transaction để đảm bảo idempotent
                    $transactionId = (string) ($transaction['id'] ?? $transaction['reference_number'] ?? '');

                    // IDEMPOTENT: Wrap trong transaction + lock để tránh xử lý trùng khi polling đồng thời
                    $result = DB::transaction(function () use ($order, $orderCode, $transactionId, $transaction, $amountIn) {
                        // Lock order row để tránh concurrent update
                        $lockedOrder = SepayOrder::where('id', $order->id)->lockForUpdate()->first();

                        // Re-check sau khi lock: đã paid chưa?
                        if ($lockedOrder->isPaid()) {
                            return ['already_paid' => true, 'order' => $lockedOrder];
                        }

                        // Đánh dấu order đã thanh toán
                        $lockedOrder->markAsPaid($transactionId, $transaction);

                        // Sync trạng thái sang Booking model (nếu có liên kết)
                        if ($lockedOrder->booking_id && $lockedOrder->booking) {
                            $booking = $lockedOrder->booking;

                            // Kiểm tra booking còn hợp lệ để chuyển PAID không
                            if (in_array($booking->status, ['PENDING', 'PENDING_PAYMENT', 'PENDING_CASH_PAYMENT'])) {
                                $booking->update([
                                    'status' => 'PAID',
                                    'payment_status' => 'PAID',
                                    'paid_at' => now(),
                                ]);

                                // Tạo Payment record (check chưa tồn tại)
                                if (! Payment::where('booking_id', $lockedOrder->booking_id)->where('status', 'SUCCESS')->exists()) {
                                    Payment::create([
                                        'booking_id' => $lockedOrder->booking_id,
                                        'payment_method' => 'ONLINE',
                                        'amount' => $lockedOrder->amount,
                                        'transaction_code' => $transactionId,
                                        'status' => 'SUCCESS',
                                        'paid_at' => now(),
                                    ]);
                                }

                                // Tự động tích Coin Membership
                                app(\App\Services\MembershipService::class)->awardBookingCoin($booking);

                                // Trừ xu nếu khách dùng xu để giảm giá
                                $coinUsed = (int) ($lockedOrder->metadata['coin_used'] ?? 0);
                                if ($coinUsed > 0 && $booking->user_id) {
                                    app(\App\Services\CoinRedemptionService::class)->deductCoins(
                                        $booking->user_id,
                                        $coinUsed,
                                        $lockedOrder->booking_id
                                    );
                                }
                            } elseif (in_array($booking->status, ['EXPIRED', 'CANCELLED'])) {
                                // Booking đã expired/cancelled → thanh toán muộn
                                // Không chiếm lại ghế, để DetectLatePayments xử lý hoàn tiền
                                Log::warning('Payment received for expired/cancelled booking', [
                                    'order_code' => $orderCode,
                                    'booking_id' => $lockedOrder->booking_id,
                                    'booking_status' => $booking->status,
                                    'amount' => $amountIn,
                                ]);
                            }
                        }

                        return ['already_paid' => false, 'order' => $lockedOrder];
                    });

                    // Nếu đã xử lý trước đó, trả kết quả luôn
                    if ($result['already_paid']) {
                        return [
                            'status' => 'paid',
                            'message' => 'Đã thanh toán',
                            'order' => $result['order']->fresh(),
                        ];
                    }

                    Log::info('SePay payment confirmed', [
                        'order_code' => $orderCode,
                        'transaction_id' => $transactionId,
                        'amount' => $amountIn,
                        'booking_id' => $order->booking_id,
                    ]);

                    // Chỉ đơn mua vé mới sinh ticket — reload booking để lấy status mới nhất
                    $freshOrder = $result['order']->fresh();
                    if ($freshOrder->isBookingOrder() && $freshOrder->booking_id && $freshOrder->booking && $freshOrder->booking->status === 'PAID') {
                        try {
                            $ticketService = app(TicketService::class);
                            $tickets = DB::transaction(function () use ($ticketService, $freshOrder) {
                                return $ticketService->generateTicketsForBooking($freshOrder->booking);
                            });

                            Log::info('Tickets generated for booking', [
                                'order_code'   => $orderCode,
                                'booking_code' => $freshOrder->booking->booking_code,
                                'ticket_count' => $tickets->count(),
                            ]);
                        } catch (\Exception $ticketEx) {
                            // Không throw — thanh toán vẫn thành công, ticket có thể retry sau
                            Log::error('Failed to generate tickets', [
                                'order_code' => $orderCode,
                                'booking_id' => $freshOrder->booking_id,
                                'error'      => $ticketEx->getMessage(),
                            ]);
                        }
                    }

                    // Đẩy PDF + Email + Notification vào hàng đợi chạy ngầm
                    \App\Jobs\SendBookingInvoiceJob::dispatch($freshOrder->id);

                    return [
                        'status' => 'paid',
                        'message' => 'Thanh toán thành công',
                        'order' => $freshOrder,
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
