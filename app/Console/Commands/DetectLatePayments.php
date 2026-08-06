<?php

namespace App\Console\Commands;

use App\Models\BookingCancellation;
use App\Models\SepayOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DetectLatePayments extends Command
{
    protected $signature = 'booking:detect-late-payments';

    protected $description = 'Quét các đơn hàng đã hết hạn để phát hiện giao dịch thanh toán muộn (cần hoàn tiền)';

    public function handle(): int
    {
        $this->info('🔍 Đang quét thanh toán muộn...');

        // Lấy các đơn hàng expired trong 24h qua có booking liên kết
        // và chưa có bản ghi LATE_PAYMENT trong booking_cancellations
        $expiredOrders = SepayOrder::where('status', 'expired')
            ->whereNotNull('booking_id')
            ->where('created_at', '>=', now()->subDay())
            ->with('booking')
            ->get()
            ->filter(function ($order) {
                // Bỏ qua đơn đã được phát hiện thanh toán muộn rồi
                return !BookingCancellation::where('booking_id', $order->booking_id)
                    ->where('type', 'LATE_PAYMENT')
                    ->exists();
            });

        if ($expiredOrders->isEmpty()) {
            $this->info('✅ Không có đơn hàng expired nào cần kiểm tra.');
            return self::SUCCESS;
        }

        $this->info("📋 Tìm thấy {$expiredOrders->count()} đơn expired cần kiểm tra.");

        $apiToken = config('sepay.api_token');
        $apiUrl = config('sepay.api_url');
        $accountNumber = config('sepay.bank_account');

        $lateCount = 0;

        foreach ($expiredOrders as $order) {
            if (!$order->booking) {
                continue;
            }

            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiToken,
                    'Content-Type'  => 'application/json',
                ])->get($apiUrl, [
                    'account_number'       => $accountNumber,
                    'transaction_date_min' => $order->created_at->format('Y-m-d H:i:s'),
                ]);

                if (!$response->successful()) {
                    continue;
                }

                $transactions = $response->json('transactions', []);

                foreach ($transactions as $transaction) {
                    $content   = $transaction['transaction_content'] ?? '';
                    $amountIn  = (int) ($transaction['amount_in'] ?? 0);

                    if (
                        stripos($content, $order->order_code) !== false
                        && $amountIn >= $order->amount
                    ) {
                        // Ghi vào bảng booking_cancellations với type = LATE_PAYMENT
                        BookingCancellation::create([
                            'type'          => 'LATE_PAYMENT',
                            'booking_id'    => $order->booking_id,
                            'canceled_by'   => null, // hệ thống tự phát hiện, không có admin
                            'reason'        => "Khách chuyển khoản muộn sau khi đơn {$order->order_code} hết hạn. Cần hoàn tiền.",
                            'refund_status' => 'pending_refund',
                            'notes'         => [
                                'order_code'       => $order->order_code,
                                'order_amount'      => $order->amount,
                                'transaction_id'    => $transaction['id'] ?? $transaction['reference_number'] ?? '',
                                'transaction_amount' => $amountIn,
                                'transaction_content' => $content,
                                'transaction_date'  => $transaction['transaction_date'] ?? '',
                                'customer_email'    => $order->getCustomerEmail(),
                                'customer_name'     => $order->getCustomerName(),
                            ],
                        ]);

                        $lateCount++;

                        Log::warning('⚠️ Thanh toán muộn phát hiện', [
                            'order_code'    => $order->order_code,
                            'booking_id'    => $order->booking_id,
                            'amount'        => $amountIn,
                            'transaction_id' => $transaction['id'] ?? '',
                        ]);

                        $this->warn("⚠️  Đơn {$order->order_code}: Thanh toán muộn {$amountIn}đ — Đã lưu vào danh sách hoàn tiền!");
                        break; // chỉ cần 1 giao dịch khớp
                    }
                }
            } catch (\Exception $e) {
                Log::error('Lỗi khi quét thanh toán muộn', [
                    'order_code' => $order->order_code,
                    'error'      => $e->getMessage(),
                ]);
                $this->error("❌ Lỗi khi kiểm tra đơn {$order->order_code}: {$e->getMessage()}");
            }

            usleep(500000); // 0.5s giữa các request tránh spam API
        }

        if ($lateCount > 0) {
            $this->warn("🔔 Phát hiện {$lateCount} giao dịch thanh toán muộn — đã lưu vào bảng booking_cancellations (type=LATE_PAYMENT)!");
        } else {
            $this->info('✅ Không phát hiện thanh toán muộn nào.');
        }

        return self::SUCCESS;
    }
}
