# Giải thích chi tiết luồng bán sản phẩm và in hóa đơn

Tài liệu này mô tả đầy đủ controller và route của chức năng bán sản phẩm lẻ. Phần mã nguồn bên dưới được giữ nguyên theo file đang chạy, không lược bỏ dòng trong controller hoặc block route bán sản phẩm.

## 1. Luồng xử lý tổng quát

1. Nhân viên mở trang bán sản phẩm.
2. Controller lấy danh sách sản phẩm và combo.
3. Nhân viên chọn số lượng rồi gửi form.
4. Controller đọc lại giá từ database, không tin giá do trình duyệt gửi lên.
5. Controller tạo `Booking`, các dòng `BookingProduct` hoặc `BookingCombo`, `Payment` và `SepayOrder` trong một transaction.
6. Với tiền mặt, đơn được đánh dấu đã trả và hóa đơn được tạo ngay.
7. Với online, đơn chuyển sang trang QR; endpoint `checkStatus` hỏi SePay.
8. Khi SePay trả về `paid`, controller tạo hóa đơn nếu chưa có.
9. Trang thành công hiển thị nút in hóa đơn.
10. Route invoice tạo PDF bằng DomPDF và tải file về máy nhân viên.

## 2. Giải thích controller

File thực thi: `app/Http/Controllers/Staff/sellproduct/SellproductController.php`.

### 2.1. Namespace và import

- `namespace` xác định controller thuộc nhóm staff bán sản phẩm.
- `Controller` là lớp nền Laravel.
- `Booking`, `BookingCombo`, `BookingProduct` ghi phần đầu đơn và các dòng hàng.
- `Combo`, `Product` đọc dữ liệu catalog và giá hiện tại.
- `Payment` lưu phương thức và trạng thái thanh toán.
- `SepayOrder` lưu mã đơn dùng cho QR/SePay.
- `Showtime` được dùng để gắn đơn bán lẻ vào một suất đang mở vì schema `bookings` yêu cầu `showtime_id`.
- `SepayService` lấy đơn và kiểm tra thanh toán.
- `RetailInvoicePDFService` tạo hoặc lấy hóa đơn và dựng PDF.
- `Request` nhận dữ liệu POST.
- `Auth`, `DB`, `Str` lần lượt lấy người dùng hiện tại, bao transaction và sinh mã ngẫu nhiên.

### 2.2. Dependency injection

Controller giữ hai service ở property. Laravel tự inject chúng qua constructor. `SepayService` xử lý trạng thái thanh toán; `RetailInvoicePDFService` cô lập phần hóa đơn/PDF khỏi nghiệp vụ chọn hàng.

### 2.3. `sell_products`

Lấy toàn bộ `Product` và `Combo`, sắp xếp theo tên, rồi truyền sang view danh sách bán hàng.

### 2.4. `orderProducts`

Validate số lượng sản phẩm/combo. `sometimes|array` cho phép bỏ qua nhóm không chọn. Số lượng phải là số nguyên không âm. `buildOrderItems` bỏ các dòng có số lượng bằng 0 và đọc lại model từ database. Nếu không còn dòng nào, quay lại trang bán hàng với lỗi; nếu có, mở trang xác nhận.

### 2.5. `checkout`

Validate danh sách item, loại item, id, số lượng, thông tin khách hàng tùy chọn và phương thức `ONLINE` hoặc `CASH`.

Sau đó controller:

- Dựng lại item và giá từ database.
- Cộng tổng tiền.
- Sinh mã đơn không trùng.
- Mở transaction.
- Tìm suất chiếu `OPEN` để thỏa schema booking.
- Tạo `Booking` với trạng thái thanh toán tùy phương thức.
- Tạo `BookingCombo` hoặc `BookingProduct` cho từng dòng.
- Tạo `Payment`.
- Tạo `SepayOrder` cùng metadata chứa khách hàng, item và phương thức.

Nếu là tiền mặt, controller gọi `getOrCreateInvoice` ngay sau transaction rồi chuyển sang trang thành công. Nếu là online, chuyển sang QR để chờ SePay. Nếu có exception, transaction rollback và nhân viên nhận thông báo lỗi.

### 2.6. `payment`

Lấy đơn theo mã. Nếu không tồn tại thì quay về trang bán hàng. Nếu đã trả thì chuyển thẳng sang trang thành công. Nếu quá hạn, đánh dấu expired. Nếu còn hiệu lực, tạo URL QR và truyền thông tin ngân hàng, tài khoản, chu kỳ polling vào view.

### 2.7. `success`

Lấy đơn, tạo nhãn phương thức thanh toán. Khi đơn đã paid, controller lấy hoặc tạo invoice để view biết có thể hiển thị nút in. Đơn chưa paid vẫn có thể hiển thị trang thành công theo luồng hiện tại nhưng không có nút invoice.

### 2.8. `checkStatus`

Gọi `SepayService::checkPayment`. Khi kết quả là `paid`, lấy lại đơn và gọi `getOrCreateInvoice`. Hàm này tìm theo `sepay_order_id` trước, nên nhiều lần polling không tạo hóa đơn trùng. Kết quả JSON trả về cho JavaScript trong trang QR.

### 2.9. `downloadInvoice`

Lấy đơn theo mã. Nếu không tồn tại thì quay về trang bán hàng. Service chỉ cho tạo hóa đơn khi đơn đã paid. Nếu đơn chưa thanh toán, controller quay về trang thành công kèm thông báo. Khi có invoice, service dựng PDF từ Blade `pdf.retail-invoice` và response tải file với tên `hoa-don-{invoice_code}.pdf`.

### 2.10. Các hàm private

- `buildOrderItems`: đọc nhóm product/combo từ form đầu tiên.
- `buildOrderItemsFromRequest`: đọc các item từ form xác nhận và đọc lại model/giá từ database.
- `generateUniqueOrderCode`: sinh mã `SPxxxxxxxx`, kiểm tra cả `bookings` và `sepay_orders` để tránh trùng.

## 3. Toàn bộ controller hiện tại

```php
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
use Illuminate\Support\Str;

class SellproductController extends Controller
{
    protected SepayService $sepayService;

    protected RetailInvoicePDFService $retailInvoicePDFService;

    public function __construct(SepayService $sepayService, RetailInvoicePDFService $retailInvoicePDFService)
    {
        $this->sepayService = $sepayService;
        $this->retailInvoicePDFService = $retailInvoicePDFService;
    }

    public function sell_products()
    {
        $products = Product::query()->orderBy('name')->get();
        $combos = Combo::query()->orderBy('name')->get();

        return view('staff.sellproduct.index', compact('products', 'combos'));
    }

    public function orderProducts(Request $request)
    {
        $validated = $request->validate([
            'products' => 'sometimes|array',
            'products.*.quantity' => 'required_with:products|integer|min:0',
            'combos' => 'sometimes|array',
            'combos.*.quantity' => 'required_with:combos|integer|min:0',
        ]);

        $orderItems = $this->buildOrderItems($validated);

        if (empty($orderItems)) {
            return redirect()->route('staff.sell-products')
                ->with('error', 'Vui lòng chọn ít nhất một sản phẩm hoặc combo.');
        }

        return view('staff.sellproduct.confirm', compact('orderItems'));
    }

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
                    'status' => $paymentMethod === 'CASH' ? 'PAID' : 'PENDING',
                    'payment_status' => $paymentMethod === 'CASH' ? 'PAID' : 'UNPAID',
                    'expired_at' => $paymentMethod === 'ONLINE' ? now()->addMinutes(15) : null,
                    'paid_at' => $paymentMethod === 'CASH' ? now() : null,
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

                $paymentStatus = $paymentMethod === 'CASH' ? 'SUCCESS' : 'PENDING';
                $paymentPaidAt = $paymentMethod === 'CASH' ? now() : null;

                Payment::create([
                    'booking_id' => $booking->id,
                    'payment_method' => $paymentMethod,
                    'amount' => $totalAmount,
                    'transaction_code' => $paymentMethod === 'CASH' ? 'CASH_' . time() : null,
                    'status' => $paymentStatus,
                    'paid_at' => $paymentPaidAt,
                ]);

                SepayOrder::create([
                    'order_code' => $bookingCode,
                    'booking_id' => $booking->id,
                    'package_id' => 'retail_sale',
                    'package_name' => 'Bán sản phẩm lẻ',
                    'amount' => $totalAmount,
                    'status' => $paymentMethod === 'CASH' ? 'paid' : 'pending',
                    'transaction_id' => $paymentMethod === 'CASH' ? 'CASH_' . time() : null,
                    'paid_at' => $paymentPaidAt,
                    'metadata' => [
                        'customer_name' => $validated['customer_name'] ?? '',
                        'customer_phone' => $validated['customer_phone'] ?? '',
                        'customer_email' => $validated['customer_email'] ?? '',
                        'items' => $orderItems,
                        'payment_method' => $paymentMethod,
                    ],
                ]);
            });

            if ($paymentMethod === 'CASH') {
                $order = $this->sepayService->getOrderByCode($bookingCode);
                $this->retailInvoicePDFService->getOrCreateInvoice($order);

                return redirect()->route('staff.sell-products.success', ['orderCode' => $bookingCode, 'paymentMethod' => 'CASH']);
            }

            return redirect()->route('staff.sell-products.payment', $bookingCode);
        } catch (\Throwable $e) {
            return redirect()->route('staff.sell-products')
                ->with('error', 'Không thể tạo đơn hàng: ' . $e->getMessage());
        }
    }

    public function payment(string $orderCode)
    {
        $order = $this->sepayService->getOrderByCode($orderCode);

        if (! $order) {
            return redirect()->route('staff.sell-products')
                ->with('error', 'Đơn hàng không tồn tại.');
        }

        if ($order->isPaid()) {
            return redirect()->route('staff.sell-products.success', ['orderCode' => $order->order_code, 'paymentMethod' => 'ONLINE']);
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

        return view('staff.sellproduct.payment', compact(
            'order', 'qrUrl', 'bankCode', 'bankAccount', 'pollingInterval'
        ));
    }

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

    public function checkStatus(string $orderCode)
    {
        $result = $this->sepayService->checkPayment($orderCode);

        if (($result['status'] ?? null) === 'paid') {
            $order = $this->sepayService->getOrderByCode($orderCode);
            $this->retailInvoicePDFService->getOrCreateInvoice($order);
        }

        return response()->json($result);
    }

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

        $pdf = $this->retailInvoicePDFService->makePdf($invoice);

        return $pdf->download("hoa-don-{$invoice->invoice_code}.pdf");
    }

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

    private function generateUniqueOrderCode(string $prefix): string
    {
        do {
            $code = $prefix . strtoupper(Str::random(8));
        } while (Booking::where('booking_code', $code)->exists() || SepayOrder::where('order_code', $code)->exists());

        return $code;
    }
}
```

## 4. Giải thích route

Các route dưới đây đều dùng `tab.auth`, vì staff phải đăng nhập trước khi bán hàng, xem trạng thái hoặc tải hóa đơn.

- `GET /staff/sell-products`: mở catalog.
- `POST /staff/sell-products/order`: nhận số lượng và mở trang xác nhận.
- `POST /staff/sell-products/checkout`: tạo đơn và chuyển sang tiền mặt hoặc QR.
- `GET /staff/sell-products/payment/{orderCode}`: hiển thị QR.
- `GET /staff/sell-products/check-status/{orderCode}`: endpoint JSON để JavaScript kiểm tra SePay.
- `GET /staff/sell-products/success/{orderCode}/{paymentMethod?}`: trang hoàn tất.
- `GET /staff/sell-products/invoice/{orderCode}`: dựng và tải PDF hóa đơn.

## 5. Toàn bộ block route hiện tại

```php
Route::middleware(['tab.auth'])->group(function () {
    Route::get('/staff/sell-products', [SellproductController::class, 'sell_products'])->name('staff.sell-products');
    Route::post('/staff/sell-products/order', [SellproductController::class, 'orderProducts'])->name('staff.sell-products.order');
    Route::post('/staff/sell-products/checkout', [SellproductController::class, 'checkout'])->name('staff.sell-products.checkout');
    Route::get('/staff/sell-products/payment/{orderCode}', [SellproductController::class, 'payment'])->name('staff.sell-products.payment');
    Route::get('/staff/sell-products/check-status/{orderCode}', [SellproductController::class, 'checkStatus'])->name('staff.sell-products.check-status');
    Route::get('/staff/sell-products/success/{orderCode}/{paymentMethod?}', [SellproductController::class, 'success'])->name('staff.sell-products.success');
    Route::get('/staff/sell-products/invoice/{orderCode}', [SellproductController::class, 'downloadInvoice'])->name('staff.sell-products.invoice');
});
```

## 6. Các file liên quan đến hóa đơn

- `app/Models/Invoice.php`: thêm `createRetailFromOrder`, lưu item bán lẻ vào JSON `seats.items` để tái sử dụng schema hiện tại.
- `app/Services/RetailInvoicePDFService.php`: lấy/tạo invoice không trùng và dựng DomPDF.
- `resources/views/pdf/retail-invoice.blade.php`: mẫu hóa đơn PDF.
- `resources/views/staff/sellproduct/success.blade.php`: hiển thị nút in khi invoice tồn tại.

## 7. Kiểm tra đã thực hiện

- `php -l app/Models/Invoice.php`: đạt.
- `php -l app/Services/RetailInvoicePDFService.php`: đạt.
- `php -l app/Http/Controllers/Staff/sellproduct/SellproductController.php`: đạt.
- `php -l routes/web.php`: đạt.
- `php artisan view:cache`: đạt.
- `php artisan route:list --name="staff.sell-products"`: hiển thị đủ 7 route, bao gồm route invoice.

## 8. Cách sử dụng

1. Chọn sản phẩm/combo.
2. Chọn `Tiền mặt` hoặc `Thanh toán online`.
3. Hoàn tất thanh toán.
4. Ở trang `Thanh toán thành công`, bấm `In hóa đơn`.
5. Trình duyệt tải file PDF `hoa-don-INV-xxxxxxxx.pdf`; từ trình xem PDF có thể chọn máy in.
