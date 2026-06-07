<?php

namespace App\Http\Controllers;

use App\Services\SepayService;
use Illuminate\Http\Request;

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

        if (!$package) {
            return redirect()->route('sepay.index')
                ->with('error', 'Gói thanh toán không hợp lệ.');
        }

        $order = $this->sepayService->createOrder($packageId);

        if (!$order) {
            return redirect()->route('sepay.index')
                ->with('error', 'Không thể tạo đơn hàng. Vui lòng thử lại.');
        }

        return redirect()->route('sepay.payment', $order->order_code);
    }

    /**
     * Trang hiển thị QR code + chờ thanh toán
     */
    public function payment(string $orderCode)
    {
        $order = $this->sepayService->getOrderByCode($orderCode);

        if (!$order) {
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

        if (!$order) {
            return redirect()->route('sepay.index')
                ->with('error', 'Đơn hàng không tồn tại.');
        }

        if (!$order->isPaid()) {
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
        // Parse dữ liệu ghế từ form
        $seatsJson = $request->input('seats', '[]');
        $seats = json_decode($seatsJson, true);

        if (empty($seats)) {
            return redirect()->route('booking.seat')
                ->with('error', 'Vui lòng chọn ít nhất 1 ghế.');
        }

        $bookingData = [
            'movie_title' => $request->input('movie_title', 'Avatar: Dòng Chảy Của Nước'),
            'cinema'      => $request->input('cinema', 'CGV Vincom'),
            'room'        => $request->input('room', 'P05 - 2D'),
            'showtime'    => $request->input('showtime', '20:00 - 23:15'),
            'show_date'   => $request->input('show_date', now()->format('d/m/Y')),
            'format'      => $request->input('format', '2D'),
            'seats'       => $seats,
        ];

        $order = $this->sepayService->createBookingOrder($bookingData);

        if (!$order) {
            return redirect()->route('booking.seat')
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

        if (!$order) {
            return redirect()->route('booking.seat')
                ->with('error', 'Đơn hàng không tồn tại.');
        }

        if ($order->isPaid()) {
            return redirect()->route('booking.bill', $order->order_code);
        }

        if ($order->isExpired()) {
            $order->markAsExpired();
            return redirect()->route('booking.seat')
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

        if (!$order) {
            return redirect()->route('booking.seat')
                ->with('error', 'Đơn hàng không tồn tại.');
        }

        if (!$order->isPaid()) {
            return redirect()->route('booking.payment', $order->order_code);
        }

        return view('booking.bill', compact('order'));
    }
}
