<?php

namespace App\Http\Controllers;

use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Models\SepayOrder;
use App\Services\SepayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SepayController extends Controller
{
    protected SepayService $sepayService;

    public function __construct(SepayService $sepayService)
    {
        $this->sepayService = $sepayService;
    }

    /**
     * Trang chọn gói thanh toán
     */
    public function index()
    {
        $packages = $this->sepayService->getPackages();

        return view('sepay.index', compact('packages'));
    }

    /**
     * Tạo đơn hàng và redirect sang trang thanh toán QR
     */
    public function checkout(string $packageId)
    {
        $package = $this->sepayService->getPackage($packageId);

        if (! $package) {
            return redirect()->route('sepay.index')
                ->with('error', 'Gói thanh toán không hợp lệ.');
        }

        $order = $this->sepayService->createOrder($packageId);

        if (! $order) {
            return redirect()->route('sepay.index')
                ->with('error', 'Không thể tạo đơn hàng. Vui lòng thử lại.');
        }

        return redirect()->route('sepay.payment', $order->order_code);
    }

    /**
     * Trang hiển thị QR code + chờ thanh toán
     */

    // payment là dành cho nạp tiền
    public function payment(string $orderCode)
    {
        $order = $this->sepayService->getOrderByCode($orderCode);

        if (! $order) {
            return redirect()->route('sepay.index')
                ->with('error', 'Đơn hàng không tồn tại.');
        }

        // Nếu đã thanh toán → chuyển sang bill
        if ($order->isPaid()) {
            return redirect()->route('sepay.bill', $order->order_code);
        }

        // Nếu đã hết hạn
        if ($order->isExpired()) {
            $order->markAsExpired();

            return redirect()->route('sepay.index')
                ->with('error', 'Đơn hàng đã hết hạn. Vui lòng tạo lại.');
        }

        $qrUrl = $this->sepayService->generateQrUrl($order);
        $bankCode = config('sepay.bank_code');
        $bankAccount = config('sepay.bank_account');
        $pollingInterval = config('sepay.polling_interval', 5000);
        $expiresAt = $order->getExpiresAt()->toIso8601String();

        return view('sepay.payment', compact(
            'order',
            'qrUrl',
            'bankCode',
            'bankAccount',
            'pollingInterval',
            'expiresAt'
        ));
    }

    /**
     * API endpoint: kiểm tra trạng thái thanh toán (AJAX polling)
     */
    public function checkStatus(string $orderCode)
    {
        $result = $this->sepayService->checkPayment($orderCode);

        return response()->json($result);
    }

    /**
     * Trang hiển thị Bill thanh toán
     */
    public function bill(string $orderCode)
    {
        $order = $this->sepayService->getOrderByCode($orderCode);

        if (! $order) {
            return redirect()->route('sepay.index')
                ->with('error', 'Đơn hàng không tồn tại.');
        }

        if (! $order->isPaid()) {
            return redirect()->route('sepay.payment', $order->order_code);
        }

        return view('sepay.bill', compact('order'));
    }

    /*
    |--------------------------------------------------------------------------
    | Booking Vé Phim
    |--------------------------------------------------------------------------
    */

    /**
     * Tạo đơn booking từ trang chọn ghế
     */
    public function bookingCheckout(Request $request)
    {
        // Validate email
        $request->validate([
            'customer_email' => 'required|email',
        ], [
            'customer_email.required' => 'Vui lòng nhập email để nhận hoá đơn.',
            'customer_email.email' => 'Email không hợp lệ.',
        ]);

        // Parse dữ liệu ghế từ form
        $seatsJson = $request->input('seats', '[]');
        $seats = json_decode($seatsJson, true);

        if (empty($seats)) {
            return redirect()->back()
                ->with('error', 'Vui lòng chọn ít nhất 1 ghế.');
        }

        $bookingData = [
            'movie_title'    => $request->input('movie_title', 'Avatar: Dòng Chảy Của Nước'),
            'cinema'         => $request->input('cinema', 'CGV Vincom'),
            'room'           => $request->input('room', 'P05 - 2D'),
            'showtime'       => $request->input('showtime', '20:00 - 23:15'),
            'show_date'      => $request->input('show_date', now()->format('d/m/Y')),
            'format'         => $request->input('format', '2D'),
            'seats'          => $seats,
            'customer_email' => $request->input('customer_email'),
            'customer_name'  => auth()->check() ? auth()->user()->name : null,
        ];

        $order = $this->sepayService->createBookingOrder($bookingData);

        if (! $order) {
            return redirect()->back()
                ->with('error', 'Không thể tạo đơn hàng. Vui lòng thử lại.');
        }

        return redirect()->route('booking.payment', $order->order_code);
    }

    /**
     * Trang QR thanh toán vé phim
     */
    public function bookingPayment(string $orderCode)
    {
        $order = $this->sepayService->getOrderByCode($orderCode);

        if (! $order) {
            return redirect()->route('home')
                ->with('error', 'Đơn hàng không tồn tại.');
        }

        if ($order->isPaid()) {
            return redirect()->route('booking.bill', $order->order_code);
        }

        if ($order->isExpired()) {
            $order->markAsExpired();

            $showtimeId = $order->metadata['showtime_id'] ?? null;
            if ($showtimeId) {
                return redirect()->route('booking.seat', ['showtime_id' => $showtimeId])
                    ->with('error', 'Đơn hàng đã hết hạn. Vui lòng đặt vé lại.');
            }

            return redirect()->route('home')
                ->with('error', 'Đơn hàng đã hết hạn. Vui lòng đặt vé lại.');
        }

        $qrUrl = $this->sepayService->generateQrUrl($order);
        $bankCode = config('sepay.bank_code');
        $bankAccount = config('sepay.bank_account');
        $pollingInterval = config('sepay.polling_interval', 5000);
        $expiresAt = $order->getExpiresAt()->toIso8601String();

        return view('booking.payment', compact(
            'order', 'qrUrl', 'bankCode', 'bankAccount',
            'pollingInterval', 'expiresAt'
        ));
    }

    /**
     * Hoá đơn vé phim sau thanh toán
     */
    public function bookingBill(string $orderCode)
    {
        $order = $this->sepayService->getOrderByCode($orderCode);

        if (! $order) {
            return redirect()->route('home')
                ->with('error', 'Đơn hàng không tồn tại.');
        }

        if (! $order->isPaid()) {
            return redirect()->route('booking.payment', $order->order_code);
        }

        // Thanh toán thành công → dọn dẹp session giữ ghế và booking tạm
        session()->forget('booking_tam');
        session()->forget('pending_order_code');

        // Giải phóng cache giữ ghế (nếu còn)
        if ($order->booking_id && Auth::check()) {
            $showtimeId = $order->metadata['showtime_id'] ?? null;
            if ($showtimeId) {
                $masterTimerKey = 'hold_timer_' . Auth::id() . '_' . $showtimeId;
                Cache::forget($masterTimerKey);
            }
        }

        return view('booking.bill', compact('order'));
    }

    /**
     * Demo bill — tạo đơn hàng giả đã thanh toán để xem trang hoá đơn
     */
    public function demoBill()
    {
        // Tìm đơn demo cũ hoặc tạo mới
        $order = SepayOrder::where('package_id', 'demo')->first();

        if (! $order) {
            $order = SepayOrder::create([
                'order_code' => 'DEMO'.strtoupper(Str::random(6)),
                'package_id' => 'demo',
                'package_name' => 'Vé xem phim (Demo)',
                'amount' => 430000,
                'status' => 'paid',
                'paid_at' => now(),
                'transaction_id' => 'TXN_DEMO_'.time(),
                'metadata' => [
                    'movie_title' => 'Avatar: Dòng Chảy Của Nước - The Way of Water',
                    'cinema' => 'CGV Vincom Center Bà Triệu',
                    'room' => 'P05 - 2D Digital',
                    'showtime' => '20:00 - 23:15',
                    'show_date' => now()->format('d/m/Y'),
                    'format' => '2D',
                    'seats' => [
                        ['code' => 'D7', 'type' => 'standard', 'price' => 10000],
                        ['code' => 'D8', 'type' => 'standard', 'price' => 10000],
                        ['code' => 'VIP3', 'type' => 'vip', 'price' => 150000],
                        ['code' => 'SW2', 'type' => 'sweetbox', 'price' => 120000],
                    ],
                    'seat_count' => 4,
                    'customer_email' => 'demo@moviezone.com',
                    'customer_name' => 'Khách Demo',
                ],
            ]);
        }

        // Tạo invoice nếu chưa có
        if (! $order->invoice) {
            $invoice = Invoice::createFromOrder($order);

            // Gửi email demo (nếu muốn test gửi mail thật, đổi email ở trên)
            try {
                Mail::to($invoice->customer_email)->send(new InvoiceMail($invoice));
                $invoice->markEmailSent();
                Log::info('Demo invoice email sent', ['invoice' => $invoice->invoice_code]);
            } catch (\Exception $e) {
                $invoice->markEmailFailed();
                Log::error('Demo invoice email failed', ['error' => $e->getMessage()]);
            }
        }

        return redirect()->route('booking.bill', $order->order_code);
    }
}
