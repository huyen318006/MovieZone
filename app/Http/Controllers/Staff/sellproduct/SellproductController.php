<?php
namespace App\Http\Controllers\Staff\sellproduct;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingCombo;
use App\Models\BookingProduct;
use App\Models\Combo;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SepayOrder;
use App\Models\Showtime;
use App\Services\SepayService;
use App\Services\RetailInvoicePDFService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SellproductController extends Controller
{
    protected SepayService $sepayService;

    protected RetailInvoicePDFService $retailInvoicePDFService;

    /**
     * Nhận các service dùng chung cho kiểm tra SePay và tạo hóa đơn bán lẻ.
     */
    public function __construct(SepayService $sepayService, RetailInvoicePDFService $retailInvoicePDFService)
    {
        $this->sepayService = $sepayService;
        $this->retailInvoicePDFService = $retailInvoicePDFService;
    }
    /**
     * Hiển thị danh sách sản phẩm và combo để nhân viên chọn bán.
     */
    public function sell_products()
    {
        $products = Product::query()->orderBy('name')->get();
        $combos = Combo::query()->orderBy('name')->get();

        return view('staff.sellproduct.index', compact('products', 'combos'));
    }


    /**
     * Nhận lựa chọn sản phẩm từ form, lọc các mặt hàng có số lượng lớn hơn 0,
     * rồi chuyển sang trang xác nhận đơn hàng.
     */
    public function orderProducts(Request $request)
    {
        $validated = $request->validate([
            'products' => 'sometimes|array',
            'products.*.quantity' => 'required_with:products|integer|min:0',
            'combos' => 'sometimes|array',
            'combos.*.quantity' => 'required_with:combos|integer|min:0',
        ]);


        //ở đây gọi metho buildOrderItems để lấy ra các sản phẩm và combo đã chọn
        $orderItems = $this->buildOrderItems($validated);

        if (empty($orderItems)) {
            return redirect()->route('staff.sell-products')
                ->with('error', 'Vui lòng chọn ít nhất một sản phẩm hoặc combo.');
        }

        return view('staff.sellproduct.confirm', compact('orderItems'));
    }

    /**
     * Tạo Booking, chi tiết sản phẩm, Payment và SepayOrder trong một transaction.
     * Cả online và tiền mặt đều bắt đầu ở trạng thái pending; bước thanh toán
     * tiếp theo sẽ xác nhận trạng thái paid theo đúng phương thức đã chọn.
     */
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|in:product,combo',
            'items.*.id' => 'required|integer|min:1',
            'items.*.quantity' => 'required|integer|min:1',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_email' => 'nullable|email|max:255',
            'payment_method' => 'required|in:ONLINE,CASH',
        ]);

        $orderItems = $this->buildOrderItemsFromRequest($validated['items']);

        if (empty($orderItems)) {
            return redirect()->route('staff.sell-products')
                ->with('error', 'Không tìm thấy sản phẩm nào để thanh toán.');
        }

        $totalAmount = (int) round(array_sum(array_column($orderItems, 'total')));
        $paymentMethod = $validated['payment_method'];

        $bookingCode = $this->generateUniqueOrderCode('SP');

        try {
            DB::transaction(function () use ($orderItems, $totalAmount, $paymentMethod, $validated, $bookingCode) {
                $showtime = Showtime::query()
                    ->where('status', 'OPEN')
                    ->orderBy('start_time')
                    ->first();

                if (! $showtime) {
                    throw new \RuntimeException('Không có suất chiếu nào để gắn cho đơn bán hàng.');
                }

                $booking = Booking::create([
                    'booking_code' => $bookingCode,
                    'user_id' => Auth::id(),
                    'showtime_id' => $showtime->id,
                    'customer_name' => $validated['customer_name'] ?? null,
                    'customer_email' => $validated['customer_email'] ?? null,
                    'customer_phone' => $validated['customer_phone'] ?? null,
                    'total_ticket_amount' => 0,
                    'total_combo_amount' => (float) collect($orderItems)->where('type', 'combo')->sum('total'),
                    'discount_amount' => 0,
                    'final_amount' => $totalAmount,
                    'status' => 'PENDING',
                    'payment_status' => 'UNPAID',
                    'expired_at' => now()->addMinutes(15),
                    'paid_at' => null,
                ]);

                foreach ($orderItems as $item) {
                    if ($item['type'] === 'combo') {
                        BookingCombo::create([
                            'booking_id' => $booking->id,
                            'combo_id' => $item['id'],
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['price'],
                            'total_price' => $item['total'],
                        ]);
                    } else {
                        BookingProduct::create([
                            'booking_id' => $booking->id,
                            'product_id' => $item['id'],
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['price'],
                            'total_price' => $item['total'],
                        ]);
                    }
                }

                Payment::create([
                    'booking_id' => $booking->id,
                    'payment_method' => $paymentMethod,
                    'amount' => $totalAmount,
                    'transaction_code' => null,
                    'status' => 'PENDING',
                    'paid_at' => null,
                ]);

                SepayOrder::create([
                    'order_code' => $bookingCode,
                    'booking_id' => $booking->id,
                    'package_id' => 'retail_sale',
                    'package_name' => 'Bán sản phẩm lẻ',
                    'amount' => $totalAmount,
                    'status' => 'pending',
                    'transaction_id' => null,
                    'paid_at' => null,
                    'metadata' => [
                        'customer_name' => $validated['customer_name'] ?? '',
                        'customer_phone' => $validated['customer_phone'] ?? '',
                        'customer_email' => $validated['customer_email'] ?? '',
                        'items' => $orderItems,
                        'payment_method' => $paymentMethod,
                    ],
                ]);
            });

            return redirect()->route('staff.sell-products.payment', $bookingCode);
        } catch (\Throwable $e) {
            return redirect()->route('staff.sell-products')
                ->with('error', 'Không thể tạo đơn hàng: ' . $e->getMessage());
        }
    }

    /**
     * Hiển thị trang thanh toán của đơn: QR và polling cho online,
     * hoặc nút xác nhận thu tiền cho tiền mặt.
     */
    public function payment(string $orderCode)
    {
        $order = $this->sepayService->getOrderByCode($orderCode);

        if (! $order) {
            return redirect()->route('staff.sell-products')
                ->with('error', 'Đơn hàng không tồn tại.');
        }

        if ($order->isPaid()) {
            return redirect()->route('staff.sell-products.success', [
                'orderCode' => $order->order_code,
                'paymentMethod' => ($order->metadata['payment_method'] ?? 'ONLINE'),
            ]);
        }

        if ($order->isExpired()) {
            $order->markAsExpired();

            return redirect()->route('staff.sell-products')
                ->with('error', 'Đơn hàng đã hết hạn. Vui lòng tạo lại.');
        }

        $qrUrl = $this->sepayService->generateQrUrl($order);
        $bankCode = config('sepay.bank_code');
        $bankAccount = config('sepay.bank_account');
        $pollingInterval = config('sepay.polling_interval', 5000);
        $expiresAt = $order->getExpiresAt()->toIso8601String();

        return view('staff.sellproduct.payment', compact(
            'order', 'qrUrl', 'bankCode', 'bankAccount', 'pollingInterval', 'expiresAt'
        ));
    }

    /**
     * Xác nhận nhân viên đã thu tiền mặt, cập nhật các bảng liên quan sang paid,
     * tạo hóa đơn và chuyển đến trang thành công.
     */
    public function confirmCashPayment(string $orderCode)
    {
        $order = $this->sepayService->getOrderByCode($orderCode);

        if (! $order || ($order->metadata['payment_method'] ?? null) !== 'CASH') {
            return redirect()->route('staff.sell-products')
                ->with('error', 'Đơn tiền mặt không tồn tại.');
        }

        if (! $order->isPaid()) {
            $paidAt = now();
            $transactionId = 'CASH_' . $order->id . '_' . $paidAt->timestamp;

            DB::transaction(function () use ($order, $paidAt, $transactionId) {
                $order->update([
                    'status' => 'paid',
                    'transaction_id' => $transactionId,
                    'paid_at' => $paidAt,
                ]);

                $order->booking?->update([
                    'status' => 'PAID',
                    'payment_status' => 'PAID',
                    'paid_at' => $paidAt,
                ]);

                Payment::where('booking_id', $order->booking_id)
                    ->where('payment_method', 'CASH')
                    ->where('status', 'PENDING')
                    ->latest('id')
                    ->first()?->update([
                        'transaction_code' => $transactionId,
                        'status' => 'SUCCESS',
                        'paid_at' => $paidAt,
                    ]);
            });
        }

        $this->retailInvoicePDFService->getOrCreateInvoice($order->fresh());

        return redirect()->route('staff.sell-products.success', [
            'orderCode' => $orderCode,
            'paymentMethod' => 'CASH',
        ]);
    }

    /**
     * Hiển thị kết quả thanh toán và nút in hóa đơn nếu đơn đã paid.
     */
    public function success(string $orderCode, string $paymentMethod = 'CASH')
    {
        $order = $this->sepayService->getOrderByCode($orderCode);

        if (! $order) {
            return redirect()->route('staff.sell-products')
                ->with('error', 'Đơn hàng không tồn tại.');
        }

        $paymentLabel = $paymentMethod === 'ONLINE' ? 'Thanh toán online' : 'Thanh toán tiền mặt';
        $invoice = $order->isPaid()
            ? $this->retailInvoicePDFService->getOrCreateInvoice($order)
            : null;

        return view('staff.sellproduct.success', compact('order', 'paymentLabel', 'invoice'));
    }

    /**
     * Endpoint JSON để frontend polling trạng thái SePay và tạo hóa đơn
     * sau khi giao dịch online được xác nhận.
     */
    public function checkStatus(string $orderCode)
    {
        $result = $this->sepayService->checkPayment($orderCode);

        if (($result['status'] ?? null) === 'paid') {
            $order = $this->sepayService->getOrderByCode($orderCode);

            try {
                $this->retailInvoicePDFService->getOrCreateInvoice($order);
            } catch (\Throwable $e) {
                Log::error('Failed to create retail invoice after payment', [
                    'order_code' => $orderCode,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json($result);
    }

    /**
     * Kiểm tra đơn đã paid, render hóa đơn HTML và tự mở hộp thoại in.
     * Tên hàm giữ nguyên để tương thích với route hiện tại.
     */
    public function downloadInvoice(string $orderCode)
    {
        $order = $this->sepayService->getOrderByCode($orderCode);

        if (! $order) {
            return redirect()->route('staff.sell-products')
                ->with('error', 'Đơn hàng không tồn tại.');
        }

        try {
            $invoice = $this->retailInvoicePDFService->getOrCreateInvoice($order);
        } catch (\RuntimeException $e) {
            return redirect()->route('staff.sell-products.success', [
                'orderCode' => $orderCode,
                'paymentMethod' => $order->metadata['payment_method'] ?? 'ONLINE',
            ])->with('error', $e->getMessage());
        }

        return view('pdf.retail-invoice', [
            'invoice' => $invoice,
            'items' => $invoice->seats['items'] ?? [],
            'autoPrint' => true,
        ]);
    }

    /**
     * Chuyển dữ liệu quantity từ form danh sách sản phẩm thành danh sách item
     * có tên, giá, số lượng và thành tiền để hiển thị xác nhận.
     */

    // ở đây là hàm lấy ra các sản phẩm và combo đã chọn
    private function buildOrderItems(array $validated): array
    {
        $orderItems = [];

        if (! empty($validated['products'])) {
            foreach ($validated['products'] as $productId => $productData) {
                $quantity = (int) ($productData['quantity'] ?? 0);
                if ($quantity <= 0) {
                    continue;
                }

                $product = Product::find($productId);
                if (! $product) {
                    continue;
                }

                $orderItems[] = [
                    'type' => 'product',
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => (int) $product->price,
                    'quantity' => $quantity,
                    'total' => (int) $product->price * $quantity,
                ];
            }
        }

        if (! empty($validated['combos'])) {
            foreach ($validated['combos'] as $comboId => $comboData) {
                $quantity = (int) ($comboData['quantity'] ?? 0);
                if ($quantity <= 0) {
                    continue;
                }

                $combo = Combo::find($comboId);
                if (! $combo) {
                    continue;
                }

                $orderItems[] = [
                    'type' => 'combo',
                    'id' => $combo->id,
                    'name' => $combo->name,
                    'price' => (int) $combo->price,
                    'quantity' => $quantity,
                    'total' => (int) $combo->price * $quantity,
                ];
            }
        }

        return $orderItems;
    }

    /**
     * Đọc các item đã gửi từ trang xác nhận, tải lại model mới nhất từ database
     * và tạo danh sách dùng để tính tiền, tránh tin giá do client tự gửi lên.
     */
    private function buildOrderItemsFromRequest(array $items): array
    {
        $orderItems = [];

        foreach ($items as $item) {
            $quantity = (int) ($item['quantity'] ?? 0);
            if ($quantity <= 0) {
                continue;
            }

            $type = $item['type'] ?? null;
            $id = (int) ($item['id'] ?? 0);

            if ($type === 'combo') {
                $model = Combo::find($id);
            } else {
                $model = Product::find($id);
            }

            if (! $model) {
                continue;
            }

            $orderItems[] = [
                'type' => $type,
                'id' => $model->id,
                'name' => $model->name,
                'price' => (int) $model->price,
                'quantity' => $quantity,
                'total' => (int) $model->price * $quantity,
            ];
        }

        return $orderItems;
    }

    /**
     * Sinh mã đơn ngẫu nhiên và lặp lại nếu mã đã tồn tại ở Booking hoặc SepayOrder.
     */
    private function generateUniqueOrderCode(string $prefix): string
    {
        do {
            $code = $prefix . strtoupper(Str::random(8));
        } while (Booking::where('booking_code', $code)->exists() || SepayOrder::where('order_code', $code)->exists());

        return $code;
    }
}

