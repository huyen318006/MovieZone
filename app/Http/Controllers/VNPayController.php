<?php

namespace App\Http\Controllers;

use App\Services\VNPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VNPayController extends Controller
{
    public function __construct(
        protected VNPayService $vnpayService
    ) {}

    /**
     * (1) Tạo URL thanh toán và redirect sang VNPAY
     *
     * POST /api/vnpay/create-payment
     *
     * Body:
     *   - order_id    (required|string)  Mã đơn hàng — duy nhất trong ngày
     *   - amount      (required|numeric) Số tiền (VND)
     *   - order_info  (nullable|string)  Mô tả đơn hàng
     *   - order_type  (nullable|string)  Danh mục hàng hóa (mặc định: 'other')
     *   - bank_code   (nullable|string)  VNPAYQR | VNBANK | INTCARD | null
     *   - locale      (nullable|string)  'vn' | 'en'
     */
    public function createPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id'   => 'required|string|max:100',
            'amount'     => 'required|numeric|min:5000', // VNPAY yêu cầu tối thiểu 5,000 VND
            'order_info' => 'nullable|string|max:255',
            'order_type' => 'nullable|string|max:100',
            'bank_code'  => 'nullable|string|max:20',
            'locale'     => 'nullable|string|in:vn,en',
        ]);

        $validated['ip_address'] = $request->ip();

        $paymentUrl = $this->vnpayService->createPaymentUrl($validated);

        return response()->json([
            'success' => true,
            'message' => 'Payment URL created successfully',
            'data'    => [
                'payment_url' => $paymentUrl,
            ],
        ]);
    }

    /**
     * (2) IPN URL — VNPAY server gọi đến server của bạn (server-to-server)
     *
     * GET /api/vnpay/ipn
     *
     * VNPAY sẽ gọi URL này để thông báo kết quả thanh toán.
     * Bạn cần cập nhật trạng thái đơn hàng trong DB tại đây.
     *
     * Response: JSON { RspCode, Message }
     */
    public function ipn(Request $request): JsonResponse
    {
        Log::info('VNPay IPN received', $request->all());

        $result = $this->vnpayService->handleIpn($request->all());

        return response()->json($result);
    }

    /**
     * (3) Return URL — Redirect trình duyệt khách hàng về sau khi thanh toán
     *
     * GET /api/vnpay/return
     *
     * Chỉ hiển thị kết quả — KHÔNG cập nhật DB ở đây!
     * Việc cập nhật DB đã được xử lý bởi IPN URL.
     */
    public function paymentReturn(Request $request): JsonResponse
    {
        Log::info('VNPay Return received', $request->all());

        $result = $this->vnpayService->handleReturn($request->all());

        if ($result['is_success']) {
            return response()->json([
                'success' => true,
                'message' => 'Giao dịch thanh toán thành công',
                'data'    => $result,
            ]);
        }

        if (!$result['is_valid_signature']) {
            return response()->json([
                'success' => false,
                'message' => 'Chữ ký không hợp lệ',
                'data'    => $result,
            ], 400);
        }

        return response()->json([
            'success' => false,
            'message' => 'Giao dịch không thành công',
            'data'    => $result,
        ]);
    }

    /**
     * Truy vấn kết quả giao dịch (Query DR)
     *
     * POST /api/vnpay/query
     *
     * Body:
     *   - order_id   (required|string) Mã đơn hàng
     *   - trans_date (required|string) Ngày giao dịch (yyyyMMddHHmmss)
     */
    public function queryTransaction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id'   => 'required|string|max:100',
            'trans_date' => 'required|string|size:14',
        ]);

        $result = $this->vnpayService->queryTransaction(
            $validated['order_id'],
            $validated['trans_date'],
            $request->ip()
        );

        return response()->json([
            'success' => true,
            'data'    => $result,
        ]);
    }

    /**
     * Hoàn tiền giao dịch (Refund)
     *
     * POST /api/vnpay/refund
     *
     * Body:
     *   - order_id       (required|string)  Mã đơn hàng gốc
     *   - amount         (required|numeric) Số tiền hoàn (VND)
     *   - trans_type     (required|string)  '02' = toàn phần, '03' = một phần
     *   - trans_date     (required|string)  Ngày giao dịch gốc
     *   - transaction_no (nullable|string)  Mã giao dịch VNPAY
     *   - created_by     (nullable|string)  Người hoàn tiền
     */
    public function refundTransaction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id'       => 'required|string|max:100',
            'amount'         => 'required|numeric|min:1',
            'trans_type'     => 'required|string|in:02,03',
            'trans_date'     => 'required|string|size:14',
            'transaction_no' => 'nullable|string',
            'created_by'     => 'nullable|string|max:50',
        ]);

        $validated['ip_address'] = $request->ip();

        $result = $this->vnpayService->refundTransaction($validated);

        return response()->json([
            'success' => true,
            'data'    => $result,
        ]);
    }
}
