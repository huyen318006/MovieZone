<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class VNPayService
{
    /**
     * Tạo URL thanh toán VNPAY
     *
     * @param array $params
     *   - order_id    (string|int) Mã đơn hàng (vnp_TxnRef) — duy nhất trong ngày
     *   - amount      (int|float)  Số tiền thanh toán (VND) — ví dụ: 100000
     *   - order_info  (string)     Mô tả nội dung thanh toán (không dấu, không ký tự đặc biệt)
     *   - order_type  (string)     Mã danh mục hàng hóa (mặc định: 'other')
     *   - bank_code   (string|null) Mã ngân hàng — null = để VNPAY hiển thị chọn
     *                               'VNPAYQR' = QR Code, 'VNBANK' = ATM nội địa, 'INTCARD' = Quốc tế
     *   - locale      (string)     Ngôn ngữ: 'vn' hoặc 'en' (mặc định: 'vn')
     *   - ip_address  (string)     IP của khách hàng
     *
     * @return string URL thanh toán đầy đủ (redirect sang VNPAY)
     */
    public function createPaymentUrl(array $params): string
    {
        $vnpTmnCode    = config('vnpay.vnp_TmnCode');
        $vnpHashSecret = config('vnpay.vnp_HashSecret');
        $vnpUrl        = config('vnpay.vnp_Url');
        $vnpReturnUrl  = config('vnpay.vnp_ReturnUrl');

        $now       = Carbon::now('Asia/Ho_Chi_Minh');
        $expireAt  = $now->copy()->addMinutes(config('vnpay.vnp_ExpireMinutes', 15));

        // ── Build input data ──────────────────────────────────────────────
        $inputData = [
            'vnp_Version'    => config('vnpay.vnp_Version', '2.1.0'),
            'vnp_TmnCode'    => $vnpTmnCode,
            'vnp_Amount'     => (int) ($params['amount'] * 100), // Nhân 100 để triệt tiêu thập phân
            'vnp_Command'    => config('vnpay.vnp_Command', 'pay'),
            'vnp_CreateDate' => $now->format('YmdHis'),
            'vnp_CurrCode'   => config('vnpay.vnp_CurrCode', 'VND'),
            'vnp_IpAddr'     => $params['ip_address'] ?? request()->ip(),
            'vnp_Locale'     => $params['locale'] ?? config('vnpay.vnp_Locale', 'vn'),
            'vnp_OrderInfo'  => $params['order_info'] ?? 'Thanh toan don hang',
            'vnp_OrderType'  => $params['order_type'] ?? 'other',
            'vnp_ReturnUrl'  => $vnpReturnUrl,
            'vnp_TxnRef'     => (string) $params['order_id'],
            'vnp_ExpireDate' => $expireAt->format('YmdHis'),
        ];

        // Bank code (tùy chọn) — nếu có thì thêm vào
        if (!empty($params['bank_code'])) {
            $inputData['vnp_BankCode'] = $params['bank_code'];
        }

        // ── Sort theo tên tham số tăng dần (yêu cầu của VNPAY) ───────────
        ksort($inputData);

        // ── Build query string & hash data ───────────────────────────────
        $query    = '';
        $hashData = '';
        $i        = 0;

        foreach ($inputData as $key => $value) {
            if ($i === 1) {
                $hashData .= '&' . urlencode($key) . '=' . urlencode($value);
            } else {
                $hashData .= urlencode($key) . '=' . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . '=' . urlencode($value) . '&';
        }

        // ── Tạo secure hash (HMAC SHA512) ────────────────────────────────
        $vnpSecureHash = hash_hmac('sha512', $hashData, $vnpHashSecret);
        $paymentUrl    = $vnpUrl . '?' . $query . 'vnp_SecureHash=' . $vnpSecureHash;

        Log::info('VNPay: Payment URL created', [
            'order_id' => $params['order_id'],
            'amount'   => $params['amount'],
            'url'      => $paymentUrl,
        ]);

        return $paymentUrl;
    }

    /**
     * Xác thực chữ ký (checksum) của dữ liệu trả về từ VNPAY
     *
     * Sử dụng cho cả IPN URL và Return URL
     *
     * @param array $vnpayData Query params từ VNPAY (request()->all())
     * @return bool
     */
    public function validateSignature(array $vnpayData): bool
    {
        $vnpSecureHash = $vnpayData['vnp_SecureHash'] ?? '';
        $vnpHashSecret = config('vnpay.vnp_HashSecret');

        // Loại bỏ vnp_SecureHash và vnp_SecureHashType ra khỏi dữ liệu để tính lại hash
        $inputData = [];
        foreach ($vnpayData as $key => $value) {
            if (str_starts_with($key, 'vnp_') && $key !== 'vnp_SecureHash' && $key !== 'vnp_SecureHashType') {
                $inputData[$key] = $value;
            }
        }

        ksort($inputData);

        $hashData = '';
        $i = 0;
        foreach ($inputData as $key => $value) {
            if ($i === 1) {
                $hashData .= '&' . urlencode($key) . '=' . urlencode($value);
            } else {
                $hashData .= urlencode($key) . '=' . urlencode($value);
                $i = 1;
            }
        }

        $secureHash = hash_hmac('sha512', $hashData, $vnpHashSecret);

        return hash_equals($secureHash, $vnpSecureHash);
    }

    /**
     * Xử lý kết quả IPN từ VNPAY (server-to-server)
     *
     * Flow xử lý:
     *   1. Validate checksum
     *   2. Tìm đơn hàng trong DB
     *   3. Kiểm tra số tiền
     *   4. Kiểm tra trạng thái đơn hàng (tránh xử lý trùng lặp)
     *   5. Cập nhật kết quả vào DB
     *   6. Trả response JSON cho VNPAY
     *
     * @param array $vnpayData
     * @return array ['RspCode' => string, 'Message' => string]
     */
    public function handleIpn(array $vnpayData): array
    {
        try {
            // ── Bước 1: Kiểm tra checksum ─────────────────────────────────
            if (!$this->validateSignature($vnpayData)) {
                Log::warning('VNPay IPN: Invalid signature', $vnpayData);
                return ['RspCode' => '97', 'Message' => 'Invalid signature'];
            }

            $orderId        = $vnpayData['vnp_TxnRef'] ?? null;
            $vnpAmount      = ($vnpayData['vnp_Amount'] ?? 0) / 100; // VNPAY trả về đã nhân 100
            $responseCode   = $vnpayData['vnp_ResponseCode'] ?? '';
            $transactionStatus = $vnpayData['vnp_TransactionStatus'] ?? '';

            // ── Bước 2: Tìm đơn hàng trong DB ────────────────────────────
            // TODO: Thay bằng logic truy vấn đơn hàng thực tế của bạn
            // Ví dụ: $order = Order::where('order_code', $orderId)->first();
            $order = null; // ← Thay thế dòng này

            if ($order === null) {
                Log::warning('VNPay IPN: Order not found', ['order_id' => $orderId]);
                return ['RspCode' => '01', 'Message' => 'Order not found'];
            }

            // ── Bước 3: Kiểm tra số tiền ─────────────────────────────────
            // TODO: Thay $order->total_amount bằng field thực tế
            // if ((float) $order->total_amount !== (float) $vnpAmount) {
            //     Log::warning('VNPay IPN: Invalid amount', [
            //         'order_id'    => $orderId,
            //         'db_amount'   => $order->total_amount,
            //         'vnp_amount'  => $vnpAmount,
            //     ]);
            //     return ['RspCode' => '04', 'Message' => 'Invalid amount'];
            // }

            // ── Bước 4: Kiểm tra trạng thái đơn hàng ────────────────────
            // Tránh xử lý trùng lặp khi VNPAY gọi IPN nhiều lần
            // TODO: Thay bằng logic kiểm tra trạng thái thực tế
            // if ($order->payment_status !== 'pending') {
            //     return ['RspCode' => '02', 'Message' => 'Order already confirmed'];
            // }

            // ── Bước 5: Cập nhật kết quả thanh toán ──────────────────────
            if ($responseCode === '00' && $transactionStatus === '00') {
                // TODO: Cập nhật đơn hàng thành công
                // $order->update([
                //     'payment_status' => 'paid',
                //     'vnpay_transaction_no' => $vnpayData['vnp_TransactionNo'] ?? null,
                //     'vnpay_bank_code' => $vnpayData['vnp_BankCode'] ?? null,
                //     'vnpay_card_type' => $vnpayData['vnp_CardType'] ?? null,
                //     'vnpay_pay_date' => $vnpayData['vnp_PayDate'] ?? null,
                //     'paid_at' => now(),
                // ]);
                Log::info('VNPay IPN: Payment success', ['order_id' => $orderId]);
            } else {
                // TODO: Cập nhật đơn hàng thất bại
                // $order->update([
                //     'payment_status' => 'failed',
                //     'vnpay_response_code' => $responseCode,
                // ]);
                Log::info('VNPay IPN: Payment failed', [
                    'order_id'      => $orderId,
                    'response_code' => $responseCode,
                ]);
            }

            return ['RspCode' => '00', 'Message' => 'Confirm Success'];
        } catch (\Exception $e) {
            Log::error('VNPay IPN: Unknown error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return ['RspCode' => '99', 'Message' => 'Unknown error'];
        }
    }

    /**
     * Xử lý Return URL (redirect từ VNPAY về trình duyệt khách hàng)
     *
     * LƯU Ý: URL này chỉ để hiển thị kết quả — KHÔNG cập nhật DB ở đây!
     *
     * @param array $vnpayData
     * @return array Thông tin kết quả thanh toán
     */
    public function handleReturn(array $vnpayData): array
    {
        $isValidSignature = $this->validateSignature($vnpayData);

        return [
            'is_valid_signature' => $isValidSignature,
            'is_success'         => $isValidSignature
                                    && ($vnpayData['vnp_ResponseCode'] ?? '') === '00'
                                    && ($vnpayData['vnp_TransactionStatus'] ?? '') === '00',
            'order_id'           => $vnpayData['vnp_TxnRef'] ?? null,
            'amount'             => isset($vnpayData['vnp_Amount']) ? $vnpayData['vnp_Amount'] / 100 : 0,
            'order_info'         => $vnpayData['vnp_OrderInfo'] ?? '',
            'response_code'      => $vnpayData['vnp_ResponseCode'] ?? '',
            'transaction_no'     => $vnpayData['vnp_TransactionNo'] ?? '',
            'bank_code'          => $vnpayData['vnp_BankCode'] ?? '',
            'card_type'          => $vnpayData['vnp_CardType'] ?? '',
            'pay_date'           => $vnpayData['vnp_PayDate'] ?? '',
        ];
    }

    /**
     * Truy vấn kết quả giao dịch tại VNPAY (Query DR)
     *
     * @param string $orderId    Mã đơn hàng (vnp_TxnRef)
     * @param string $transDate  Ngày giao dịch (yyyyMMddHHmmss)
     * @param string $ipAddress  IP của server thực hiện truy vấn
     * @return array Kết quả truy vấn
     */
    public function queryTransaction(string $orderId, string $transDate, string $ipAddress): array
    {
        $vnpTmnCode    = config('vnpay.vnp_TmnCode');
        $vnpHashSecret = config('vnpay.vnp_HashSecret');
        $vnpApiUrl     = config('vnpay.vnp_ApiUrl');

        $requestId = Carbon::now('Asia/Ho_Chi_Minh')->format('YmdHis') . rand(1000, 9999);
        $createDate = Carbon::now('Asia/Ho_Chi_Minh')->format('YmdHis');

        $inputData = [
            'vnp_RequestId'   => $requestId,
            'vnp_Version'     => config('vnpay.vnp_Version', '2.1.0'),
            'vnp_Command'     => 'querydr',
            'vnp_TmnCode'     => $vnpTmnCode,
            'vnp_TxnRef'      => $orderId,
            'vnp_OrderInfo'   => 'Truy van giao dich: ' . $orderId,
            'vnp_TransactionDate' => $transDate,
            'vnp_CreateDate'  => $createDate,
            'vnp_IpAddr'      => $ipAddress,
        ];

        // Tạo checksum cho query
        $hashData = implode('|', [
            $inputData['vnp_RequestId'],
            $inputData['vnp_Version'],
            $inputData['vnp_Command'],
            $inputData['vnp_TmnCode'],
            $inputData['vnp_TxnRef'],
            $inputData['vnp_TransactionDate'],
            $inputData['vnp_CreateDate'],
            $inputData['vnp_IpAddr'],
            $inputData['vnp_OrderInfo'],
        ]);

        $inputData['vnp_SecureHash'] = hash_hmac('sha512', $hashData, $vnpHashSecret);

        // Gửi request đến VNPAY
        $response = \Illuminate\Support\Facades\Http::post($vnpApiUrl, $inputData);

        Log::info('VNPay QueryDR:', [
            'order_id' => $orderId,
            'response' => $response->json(),
        ]);

        return $response->json() ?? [];
    }

    /**
     * Hoàn tiền giao dịch (Refund)
     *
     * @param array $params
     *   - order_id      (string) Mã đơn hàng gốc
     *   - amount        (int)    Số tiền hoàn (VND)
     *   - trans_type    (string) '02' = Hoàn toàn phần, '03' = Hoàn một phần
     *   - trans_date    (string) Ngày giao dịch gốc (yyyyMMddHHmmss)
     *   - created_by    (string) Người thực hiện hoàn tiền
     *   - ip_address    (string) IP server
     * @return array Kết quả hoàn tiền
     */
    public function refundTransaction(array $params): array
    {
        $vnpTmnCode    = config('vnpay.vnp_TmnCode');
        $vnpHashSecret = config('vnpay.vnp_HashSecret');
        $vnpApiUrl     = config('vnpay.vnp_ApiUrl');

        $requestId = Carbon::now('Asia/Ho_Chi_Minh')->format('YmdHis') . rand(1000, 9999);
        $createDate = Carbon::now('Asia/Ho_Chi_Minh')->format('YmdHis');

        $inputData = [
            'vnp_RequestId'       => $requestId,
            'vnp_Version'         => config('vnpay.vnp_Version', '2.1.0'),
            'vnp_Command'         => 'refund',
            'vnp_TmnCode'         => $vnpTmnCode,
            'vnp_TransactionType' => $params['trans_type'] ?? '02',
            'vnp_TxnRef'          => $params['order_id'],
            'vnp_Amount'          => (int) ($params['amount'] * 100),
            'vnp_OrderInfo'       => 'Hoan tien giao dich: ' . $params['order_id'],
            'vnp_TransactionNo'   => $params['transaction_no'] ?? '',
            'vnp_TransactionDate' => $params['trans_date'],
            'vnp_CreateDate'      => $createDate,
            'vnp_CreateBy'        => $params['created_by'] ?? 'admin',
            'vnp_IpAddr'          => $params['ip_address'] ?? request()->ip(),
        ];

        // Tạo checksum cho refund
        $hashData = implode('|', [
            $inputData['vnp_RequestId'],
            $inputData['vnp_Version'],
            $inputData['vnp_Command'],
            $inputData['vnp_TmnCode'],
            $inputData['vnp_TransactionType'],
            $inputData['vnp_TxnRef'],
            $inputData['vnp_Amount'],
            $inputData['vnp_TransactionNo'],
            $inputData['vnp_TransactionDate'],
            $inputData['vnp_CreateBy'],
            $inputData['vnp_CreateDate'],
            $inputData['vnp_IpAddr'],
            $inputData['vnp_OrderInfo'],
        ]);

        $inputData['vnp_SecureHash'] = hash_hmac('sha512', $hashData, $vnpHashSecret);

        $response = \Illuminate\Support\Facades\Http::post($vnpApiUrl, $inputData);

        Log::info('VNPay Refund:', [
            'order_id' => $params['order_id'],
            'amount'   => $params['amount'],
            'response' => $response->json(),
        ]);

        return $response->json() ?? [];
    }
}
