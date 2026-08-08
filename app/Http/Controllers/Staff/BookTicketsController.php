<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Helpers\TabAuthHelper;
use App\Models\Combo;
use App\Models\SepayOrder;
use App\Services\SepayService;
use App\Services\TicketService;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Services\StaffBookingService as ServicesStaffBookingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class BookTicketsController extends Controller
{
    public function __construct(
        private ServicesStaffBookingService $staffBookingService,
        private SepayService $sepayService,
    ) {}

    // hàm lấy ra film của hệ thống
    public function index(Request $request)
    {

        $movies = $this->staffBookingService->getMovies($request->input('search'));
        return view('staff.sell-tickets', compact('movies'));
    }

// hàm lấy ra ghế của suất chiếu đó + thông tin phim, phòng
    public function sell_seat($id)
    {
        $data = $this->staffBookingService->sell_seat($id);

        $showtime = $data['showtime'];  // có sẵn ->movie (tên phim, poster...) và ->room (tên phòng)
        $seatMap  = $data['seatMap'];   // sơ đồ ghế theo hàng

        // TÍNH THỜI GIAN CÒN LẠI — truyền timestamp cho frontend (giống BookingController)
        $holdMinutes = (int) config('booking.hold_minutes', 5);
        $masterTimerKey = 'hold_timer_'.Auth::id().'_'.$id;
        $holdExpiresAt = null;
        $serverTime = now()->toIso8601String();
        $holdTotalSeconds = $holdMinutes * 60;
        if (Cache::has($masterTimerKey)) {
            $ts = Cache::get($masterTimerKey);
            if ($ts > now()->timestamp) {
                $holdExpiresAt = \Carbon\Carbon::createFromTimestamp($ts)->toIso8601String();
            }
        }

        return view('staff.sell-tickets-seats', compact('showtime', 'seatMap', 'holdExpiresAt', 'serverTime', 'holdTotalSeconds'));
    }

    /**
     * Nhận danh sách ghế staff đã chọn và chuyển sang bước combo.
     * Route: GET /staff/sell-tickets/submitseat
     */
    public function submitseat(Request $request)
    {
        $request->validate([
            'showtime_id' => 'required',
            'seats' => 'required|array|min:1',
        ]);

        $selectedSeatIds = array_values(array_unique(array_filter(array_map('intval', $request->input('seats', [])))));
        if (empty($selectedSeatIds)) {
            return back()->withInput()->with('error', 'Vui lòng chọn ít nhất 1 ghế.');
        }

        try {
            $this->staffBookingService->validateSeatSelection((int) $request->showtime_id, $selectedSeatIds);
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        // Lấy thông tin suất chiếu
        $showtime = \App\Models\Showtime::with(['movie', 'room'])
            ->findOrFail($request->showtime_id);

        // Lấy thông tin phim
        $movie = $showtime->movie;

        // Lưu tiến trình vào session
        session([
            'booking' => [
                'showtime_id' => $showtime->id,
                'movie_id'    => $movie->id,
                'movie_name'  => $movie->title,
                'start_time'  => $showtime->start_time,
                'end_time'    => $showtime->end_time,
                'room'        => $showtime->room,
                'seats'       => $selectedSeatIds,
            ]
        ]);

        $combo = Combo::all();
        $product = Product::all();

        return view('staff.sell-tickets-combo', compact('combo', 'product'));
    }

    //save các combo mà người dùng đặt và ly
    public function savecombo(Request $request)
    {
        // View gửi quantity theo dạng combo_quantities[id] / product_quantities[id]
        // Ta chỉ lưu các id có quantity > 0 để confirm hiển thị.

        $comboQuantities = $request->input('combo_quantities', []);
        $productQuantities = $request->input('product_quantities', []);

        $selectedCombos = [];
        if (!empty($comboQuantities)) {
            $comboModels = Combo::whereIn('id', array_keys($comboQuantities))->get()->keyBy('id');
            foreach ($comboQuantities as $comboId => $qty) {
                $qty = (int) $qty;
                if ($qty <= 0) continue;
                $combo = $comboModels->get((int)$comboId);
                $unit = (int) ($combo?->price ?? 0);
                $selectedCombos[] = [
                    'id' => (int) $comboId,
                    'name' => $combo?->name ?? 'Combo',
                    'quantity' => $qty,
                    'total_price' => $unit * $qty,
                ];
            }
        }

        $selectedProducts = [];
        if (!empty($productQuantities)) {
            $productModels = Product::whereIn('id', array_keys($productQuantities))->get()->keyBy('id');
            foreach ($productQuantities as $productId => $qty) {
                $qty = (int) $qty;
                if ($qty <= 0) continue;
                $product = $productModels->get((int)$productId);
                $unit = (int) ($product?->price ?? 0);
                $selectedProducts[] = [
                    'id' => (int) $productId,
                    'name' => $product?->name ?? 'Product',
                    'quantity' => $qty,
                    'total_price' => $unit * $qty,
                ];
            }
        }

        // Lưu tiến trình (giữ nguyên showtime_id + seats từ bước submitseat)
        // Combo / products được gắn thêm vào session booking.
        $booking = session('booking', []);
        $booking['combos'] = $selectedCombos;
        $booking['products'] = $selectedProducts;
        session(['booking' => $booking]);

        return redirect()->route('staff.sell-tickets.confirm');
    }

    //hiển thị thông tin xác nhận đặt vé
    public function confirm()
    {
        $booking = session('booking', []);

        if (empty($booking)) {
            return redirect()->route('staff.sell-tickets');
        }

        $movie_id   = $booking['movie_id'];
        $movie_name = $booking['movie_name'];

        $start_time = $booking['start_time'];
        $end_time   = $booking['end_time'];

        $room = $booking['room'];

        $showtimeId = $booking['showtime_id'];

        $seatIds = $booking['seats'] ?? [];

        $seats = [];

        if (!empty($seatIds)) {

            $seats = \App\Models\ShowtimeSeat::with('seat:id,seat_code')
                ->whereIn('id', $seatIds)
                ->get(['id', 'seat_id', 'showtime_id', 'price'])
                ->map(function ($ss) {

                    return (object)[
                        'id' => $ss->id,
                        'seat_code' => $ss->seat->seat_code,
                        'seat' => $ss->seat,
                        'price' => $ss->price,
                    ];
                })
                ->values()
                ->all();
        }

        $combos = $booking['combos'] ?? [];
        $products = $booking['products'] ?? [];

        $showtime = \App\Models\Showtime::with(['movie', 'room'])
            ->find($showtimeId);

        return view(
            'staff.sell-tickets-confirm',
            compact(
                'showtime',
                'seats',
                'combos',
                'products',
                'movie_name',
                'movie_id',
                'start_time',
                'end_time',
                'room'
            )
        );
    }

    /**
     * Xử lý checkout đặt vé hộ (Staff).
     *
     * - Validate input
     * - Gọi StaffBookingService::createBookingFromStaff()
     * - ONLINE → redirect sang trang QR Payment
     * - CASH → xác nhận thanh toán tiền mặt ngay + tạo vé
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'customer_name'  => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_email' => 'nullable|email|max:255',
            'payment_method' => 'required|in:ONLINE,CASH',
        ]);

        $booking = session('booking', []);
        if (empty($booking) || empty($booking['seats'])) {
            return redirect()->route('staff.sell-tickets')
                ->with('error', 'Phiên đặt vé đã hết hạn. Vui lòng bắt đầu lại.');
        }

        try {
            $result = $this->staffBookingService->createBookingFromStaff([
                'showtime_id'    => $booking['showtime_id'],
                'seats'          => $booking['seats'],
                'combos'         => $booking['combos'] ?? [],
                'products'       => $booking['products'] ?? [],
                'customer_name'  => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_email' => $request->customer_email ?? '',
                'payment_method' => $request->payment_method,
                'staff_user_id'  => Auth::id(),
            ]);

            if (!$result['success']) {
                return redirect()->route('staff.sell-tickets.confirm')
                    ->withInput()
                    ->with('error', $result['message']);
            }

            // Chỉ xóa session SAU KHI tạo booking thành công
            session()->forget('booking');

            $paymentMethod = $request->payment_method;

            if ($paymentMethod === 'CASH') {
                return $this->confirmCashPayment($result['booking_code']);
            }

            // Thanh toán ONLINE → chuyển sang trang QR Payment
            return redirect()->route('staff.sell-tickets.payment', $result['order_code']);

        } catch (\Exception $e) {
            Log::error('Staff checkout exception', [
                'error'     => $e->getMessage(),
                'staff_id'  => Auth::id(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return redirect()->route('staff.sell-tickets.confirm')
                ->withInput()
                ->with('error', 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    /**
     * Trang QR Payment cho Staff.
     * Tái sử dụng SepayService::generateQrUrl() và logic polling từ Customer.
     */
    public function payment(string $orderCode)
    {
        $order = $this->sepayService->getOrderByCode($orderCode);

        if (!$order) {
            return redirect()->route('staff.sell-tickets')
                ->with('error', 'Đơn hàng không tồn tại.');
        }

        // Eager-load booking relationship để tránh null trong Blade
        $order->load('booking');

        // Nếu đã thanh toán → chuyển sang in hóa đơn
        if ($order->isPaid()) {
            $booking = $order->booking;
            if ($booking) {
                return redirect()->route('staff.print-bill', $booking->booking_code);
            }
            return redirect()->route('staff.sell-tickets')
                ->with('success', 'Đơn hàng đã được thanh toán.');
        }

        // Nếu đã hết hạn
        if ($order->isExpired()) {
            $order->markAsExpired();
            return redirect()->route('staff.sell-tickets')
                ->with('error', 'Đơn hàng đã hết hạn. Vui lòng đặt vé lại.');
        }

        $qrUrl = $this->sepayService->generateQrUrl($order);
        $bankCode = config('sepay.bank_code');
        $bankAccount = config('sepay.bank_account');
        $pollingInterval = config('sepay.polling_interval', 5000);

        return view('staff.sell-tickets-payment', compact(
            'order', 'qrUrl', 'bankCode', 'bankAccount', 'pollingInterval'
        ));
    }

    /**
     * Xác nhận thanh toán tiền mặt (CASH).
     * Booking chuyển PAID ngay, sinh Tickets, gửi email hoá đơn.
     */
    private function confirmCashPayment(string $bookingCode): \Illuminate\Http\RedirectResponse
    {
        try {
            $booking = \App\Models\Booking::where('booking_code', $bookingCode)->firstOrFail();

            DB::transaction(function () use ($booking) {
                // Cập nhật trạng thái booking
                $booking->update([
                    'status'         => 'PAID',
                    'payment_status' => 'PAID',
                    'paid_at'        => now(),
                ]);

                // Tạo Payment record
                \App\Models\Payment::create([
                    'booking_id'     => $booking->id,
                    'amount'         => $booking->final_amount,
                    'payment_method' => 'CASH',
                    'status'         => 'SUCCESS',
                    'paid_at'        => now(),
                ]);

                // Tự động tích Coin Membership
                app(\App\Services\MembershipService::class)->awardBookingCoin($booking);

                // Cập nhật SepayOrder (nếu có)
                $sepayOrder = SepayOrder::where('booking_id', $booking->id)->first();
                if ($sepayOrder) {
                    $sepayOrder->update([
                        'status'         => 'paid',
                        'paid_at'        => now(),
                        'transaction_id' => 'CASH_' . time(),
                    ]);
                }
            });

            try {
                $ticketService = app(TicketService::class);
                $ticketService->generateTicketsForBooking($booking);
            } catch (\Exception $ticketEx) {
                Log::warning('Staff cash payment succeeded but ticket generation failed', [
                    'booking_code' => $bookingCode,
                    'error'        => $ticketEx->getMessage(),
                ]);
            }

            Log::info('Staff cash payment confirmed', [
                'booking_code' => $bookingCode,
                'staff_id'     => Auth::id(),
            ]);

            return redirect(TabAuthHelper::route('staff.print-bill', $bookingCode))
                ->with('success', 'Thanh toán tiền mặt thành công! Hóa đơn đã được tạo.');

        } catch (\Exception $e) {
            Log::error('Staff cash payment failed', [
                'booking_code' => $bookingCode,
                'error'        => $e->getMessage(),
            ]);

            return redirect(TabAuthHelper::route('staff.sell-tickets'))
                ->with('error', 'Lỗi xác nhận thanh toán: ' . $e->getMessage());
        }
    }
}

