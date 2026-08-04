<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingCombo;
use App\Models\Combo;
use App\Models\PointTransaction;
use App\Models\Seat;
use App\Models\SepayOrder;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Services\CoinRedemptionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\TicketService;

class BookingController extends Controller
{
    // ==========================================
    // UC-CUS-08: CHỌN GHẾ
    // ==========================================
    // ==========================================
    // UC-CUS-08: CHỌN GHẾ
    // ==========================================
    public function showSeats($showtime_id)
    {
        $showtime = Showtime::with(['movie', 'room'])
            ->findOrFail($showtime_id);

        if (now()->greaterThan($showtime->start_time)) {
            return redirect()->back()->with('error', 'Suất chiếu này đã bắt đầu.');
        }

        // ====================== ĐẢM BẢO ĐẦY ĐỦ GHẾ ======================
        $this->syncShowtimeSeats($showtime);

        // Load lại dữ liệu sau khi sync
        $showtime->load('showtimeSeats.seat');

        // ====================== XÂY DỰNG DANH SÁCH GHẾ ĐẦY ĐỦ CHO ROOM ======================
        // UI cần hiển thị đủ mọi ghế được admin add ở phòng (seat.room_id).
        // Sau đó map sang showtime_seats (nếu showtime_seats chưa có -> syncShowtimeSeats sẽ tạo).
        $roomSeats = Seat::where('room_id', $showtime->room_id)
            ->whereNull('deleted_at')
            ->get();

        // map showtime_seats theo seat_id để lookup nhanh
        $showtimeSeatsBySeatId = $showtime->showtimeSeats
            ->keyBy('seat_id');

        $allSeatsMatrix = [];
        foreach ($roomSeats as $seat) {
            $row = $seat->row_label;
            $num = $seat->seat_number;
            if (! $row || $num === null) {
                continue;
            }

            $showtimeSeat = $showtimeSeatsBySeatId->get($seat->id);
            if (! $showtimeSeat) {
                // syncShowtimeSeats đáng ra đã tạo đủ, nhưng fallback cho an toàn
                continue;
            }

            $baseStatus = $seat->status ?? 'ACTIVE';

            if (in_array($baseStatus, ['BLOCKED', 'BROKEN'])) {
                $displayStatus = $baseStatus;
            } else {
                $isSold = DB::table('booking_seats')
                    ->join('bookings', 'bookings.id', '=', 'booking_seats.booking_id')
                    ->where('booking_seats.showtime_seat_id', $showtimeSeat->id)
                    ->where('bookings.showtime_id', $showtime_id)
                    ->whereIn('bookings.status', ['PAID', 'PENDING_PAYMENT', 'PENDING_CASH_PAYMENT'])
                    ->exists();

                if ($isSold) {
                    $displayStatus = 'SOLD';
                } else {
                    $heldBy = Cache::get('seat_held_'.$showtime_id.'_'.$showtimeSeat->id);
                    if ($heldBy) {
                        $displayStatus = ($heldBy == Auth::id()) ? 'HELD_BY_ME' : 'HELD';
                    } else {
                        $displayStatus = $showtimeSeat->status ?? 'AVAILABLE';
                    }
                }
            }

            $allSeatsMatrix[$row][$num] = (object) [
                'id' => $showtimeSeat->id,
                'seat' => $seat,
                'price' => $showtimeSeat->price ?? $seat->price ?? 90000,
                'display_status' => $displayStatus,
            ];
        }

        // ====================== BUILD SEATMAP ======================

        $seatMap = [];
        ksort($allSeatsMatrix);

        foreach ($allSeatsMatrix as $row => $rowSeats) {
            ksort($rowSeats);
            $seatMap[$row] = [];

            foreach ($rowSeats as $i => $dbSeat) {
                $seatType = $dbSeat->seat->seat_type ?? 'STANDARD';
                $mappedType = 'standard';
                if ($seatType === 'VIP') {
                    $mappedType = 'vip';
                } elseif ($seatType === 'COUPLE' || $row === 'J') {
                    $mappedType = 'sweetbox';
                } elseif ($seatType === 'DEMO') {
                    $mappedType = 'demo';
                }

                $seatMap[$row][] = [
                    'id' => $dbSeat->id,
                    'code' => $dbSeat->seat->seat_code ?? ($row.str_pad($i, 2, '0', STR_PAD_LEFT)),
                    'price' => (int) $dbSeat->price,
                    'status' => $dbSeat->display_status,
                    'type' => $mappedType,
                    'label' => $i,
                    'is_aisle' => ($i == 5),
                ];
            }
        }

        // TÍNH THỜI GIAN CÒN LẠI
        $masterTimerKey = 'hold_timer_'.Auth::id().'_'.$showtime_id;
        $secondsLeft = 300;
        if (Cache::has($masterTimerKey)) {
            $secondsLeft = max(0, Cache::get($masterTimerKey) - now()->timestamp);
        }

        return view('booking.seat', compact('showtime', 'seatMap', 'secondsLeft'));
    }

    /**
     * Đồng bộ tất cả ghế từ phòng vào showtime_seats
     */
    private function syncShowtimeSeats(Showtime $showtime)
    {
        $roomSeats = Seat::where('room_id', $showtime->room_id)
            ->whereNull('deleted_at')
            ->get();

        foreach ($roomSeats as $seat) {
            ShowtimeSeat::firstOrCreate(
                [
                    'showtime_id' => $showtime->id,
                    'seat_id' => $seat->id,
                ],
                [
                    'price' => $seat->price ?? 90000,
                    'status' => 'AVAILABLE',
                ]
            );
        }
    }

    // /

    // AJAX API xử lý giữ ghế Realtime (UC-08 bước 5)
    public function holdSeat(Request $request)
    {
        $showtimeId = $request->showtime_id;
        $seatId = $request->seat_id;
        $action = $request->action;
        $cacheKey = 'seat_held_'.$showtimeId.'_'.$seatId;

        if ($action === 'hold') {
            $seat = ShowtimeSeat::with('seat')->find($seatId);
            if (! $seat || in_array($seat->seat->status ?? 'ACTIVE', ['BLOCKED', 'BROKEN'])) {
                return response()->json([
                    'success' => false,
                    'error_type' => 'BLOCKED',
                    'message' => 'Ghế này hiện không thể chọn.',
                ]);
            }

            // Check ngoại lệ E1: Đã bán
            $isSold = DB::table('booking_seats')
                ->join('bookings', 'bookings.id', '=', 'booking_seats.booking_id')
                ->where('booking_seats.showtime_seat_id', $seatId)
                ->where('bookings.showtime_id', $showtimeId)
                ->whereIn('bookings.status', ['PAID', 'PENDING_CASH_PAYMENT', 'PENDING_PAYMENT'])
                ->exists();

            if ($isSold) {
                return response()->json(['success' => false, 'error_type' => 'SOLD', 'message' => 'E1: Ghế đã được bán.']);
            }

            // Check ngoại lệ E2: Đang bị người khác giữ
            $heldBy = Cache::get($cacheKey);
            if ($heldBy && $heldBy != Auth::id()) {
                return response()->json(['success' => false, 'error_type' => 'HELD', 'message' => 'E2: Ghế đang được người khác giữ.']);
            }

            // Key lưu thời gian giữ ghế tổng của user cho suất chiếu này
            $masterTimerKey = 'hold_timer_'.Auth::id().'_'.$showtimeId;

            // Nếu chưa có timer tổng
            // (nghĩa là đây là ghế đầu tiên user chọn)
            if (! Cache::has($masterTimerKey)) {

                // Tính thời điểm hết hạn sau 5 phút
                $expireAt = now()->addMinutes(5);

                // Lưu timestamp vào cache
                // Ví dụ: 1789561200
                // để sau này JS tính được còn bao nhiêu giây
                Cache::put(
                    $masterTimerKey,
                    $expireAt->timestamp,
                    $expireAt
                );

            } else {

                // Nếu timer đã tồn tại
                // lấy lại thời điểm hết hạn cũ
                // KHÔNG tạo mới để tránh reset về 5 phút
                $expireAt = Carbon::createFromTimestamp(
                    Cache::get($masterTimerKey)
                );
            }

            // Lưu trạng thái giữ ghế
            Cache::put(

                // Ví dụ:
                // seat_held_5_12
                // (suất chiếu 5, ghế 12)
                $cacheKey,

                // User nào đang giữ ghế
                Auth::id(),

                // Ghế sẽ hết hạn cùng lúc với timer tổng
                // Không phải thêm 5 phút mới
                $expireAt
            );

            return response()->json(['success' => true]);
        } elseif ($action === 'release') {
            // Luồng phụ A2: Bỏ chọn ghế
            if (Cache::get($cacheKey) == Auth::id()) {
                Cache::forget($cacheKey);
            }

            return response()->json(['success' => true]);
        }
    }

    public function submitSeats(Request $request)
    {
        $request->validate([
            'showtime_id' => 'required',
            'seats' => 'required|array|min:1', // BR01: Ít nhất 1 ghế
        ]);

        $seats = ShowtimeSeat::with('seat')->whereIn('id', $request->seats)->get();
        $invalidSeats = $seats->filter(function ($seat) {
            return in_array($seat->seat->status ?? 'ACTIVE', ['BLOCKED', 'BROKEN']);
        });

        if ($invalidSeats->isNotEmpty()) {
            return back()->withErrors(['error' => 'Một số ghế đã bị khóa hoặc hỏng, vui lòng chọn lại.']);
        }

        // 🔥 THÊM VALIDATE LẺ GHẾ TẠI ĐÂY
        if ($this->hasSingleSeatGap($request->showtime_id, $request->seats)) {
            return back()->withInput()->withErrors(['error' => 'Vị trí chọn không hợp lệ! Vui lòng không để trống duy nhất 1 ghế trống ở giữa hoặc ở đầu/cuối hàng.']);
        }

        $totalSeatAmount = $seats->sum('price');

        session([
            'booking_tam' => [
                'showtime_id' => $request->showtime_id,
                'seats' => $request->seats,
                'seats' => $request->seats,

                // Ticket
                'total_seat_amount' => $totalSeatAmount,

                // Combo
                'combos' => [],
                'total_combo_amount' => 0,

                // Voucher
                'voucher_id' => null,
                'voucher_code' => null,
                'discount_amount' => 0,

                // Total
                'subtotal' => $totalSeatAmount,
                'total' => $totalSeatAmount,
            ],
        ]);

        return redirect()->route('booking.combo');
    }

    // ==========================================
    // UC-CUS-09: CHỌN COMBO
    // ==========================================
    public function showCombo()
    {
        $bookingTam = session('booking_tam');

        // TIMER: Tính thời gian còn lại từ master timer
        $secondsLeft = 0;
        if ($bookingTam && ! empty($bookingTam['showtime_id'])) {
            $masterTimerKey = 'hold_timer_'.Auth::id().'_'.$bookingTam['showtime_id'];
            if (Cache::has($masterTimerKey)) {
                $secondsLeft = max(0, Cache::get($masterTimerKey) - now()->timestamp);
            }

            // Nếu hết thời gian → redirect về trang ghế
            if ($secondsLeft <= 0) {
                session()->forget('booking_tam');

                return redirect()->route('booking.seat', ['showtime_id' => $bookingTam['showtime_id']])
                    ->with('error', 'Hết thời gian giữ ghế (5 phút). Vui lòng chọn lại.');
            }
        }

        $combos = Combo::where('status', 'ACTIVE')->get();

        if (! empty($bookingTam['showtime_id']) && ! empty($bookingTam['seats'])) {
            $seatLabels = ShowtimeSeat::with('seat')
                ->whereIn('id', $bookingTam['seats'])
                ->get()
                ->map(function ($item) {
                    return $item->seat->seat_code ?? ('Ghế #'.$item->id);
                })
                ->values()
                ->all();

            $bookingTam['seat_labels'] = $seatLabels;
            session()->put('booking_tam', $bookingTam);
        }

        return view('booking.combo', compact('combos', 'secondsLeft'));
    }

    public function saveCombo(Request $request)
    {
        $bookingTam = session()->get('booking_tam');

        if (! $bookingTam) {
            return redirect()->route('home')
                ->with('error', 'Phiên đặt vé không tồn tại.');
        }

        $seatCount = count($bookingTam['seats'] ?? []);
        $confirmOverSeat = $request->boolean('confirm_over_seat');

        $selectedCombos = [];
        $comboTotal = 0;
        $comboQuantityTotal = 0;

        foreach ($request->input('combos', []) as $comboId => $item) {
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($quantity > 0) {
                if ($quantity > 10) {
                    return back()
                        ->withInput()
                        ->with('error', 'Mỗi loại combo chỉ được chọn tối đa 10.');
                }

                $combo = Combo::where('status', 'ACTIVE')->find($comboId);

                if ($combo) {
                    $subtotal = $combo->price * $quantity;

                    $selectedCombos[] = [
                        'combo_id' => $combo->id,
                        'name' => $combo->name,
                        'quantity' => $quantity,
                        'unit_price' => $combo->price,
                        'total_price' => $subtotal,
                    ];

                    $comboTotal += $subtotal;
                    $comboQuantityTotal += $quantity;
                }
            }
        }

        if ($seatCount > 0 && $comboQuantityTotal > $seatCount && ! $confirmOverSeat) {
            return back()
                ->withInput()
                ->with('warning', 'Bạn đang chọn combo nhiều hơn số ghế đã đặt. Bạn có chắc chắn muốn tiếp tục không?');
        }

        // 🔥 UPDATE SESSION ĐÚNG CÁCH (KHÔNG GHI ĐÈ LUNG TUNG)
        $bookingTam['combos'] = $selectedCombos;
        $bookingTam['total_combo_amount'] = $comboTotal;

        // 🔥 BẮT BUỘC: lấy lại seat total + discount từ session hiện tại
        $seatTotal = $bookingTam['total_seat_amount'] ?? 0;
        $discount = $bookingTam['discount_amount'] ?? 0;

        $bookingTam['subtotal'] = $seatTotal + $comboTotal;
        $bookingTam['total'] = max(0, $bookingTam['subtotal'] - $discount);

        // 🔥 Reset lại Xu nếu khách hàng quay lại đổi Combo (tránh lệch tổng tiền)
        $bookingTam['coin_used'] = 0;
        $bookingTam['coin_discount_amount'] = 0;

        // 🔥 QUAN TRỌNG NHẤT: dùng put (KHÔNG dùng session([...]) kiểu overwrite)
        session()->put('booking_tam', $bookingTam);

        return redirect()->route('booking.confirm');
    }

    // ==========================================
    // UC-CUS-11: XÁC NHẬN ĐẶT VÉ VÀ TẠO BOOKING
    // ==========================================
    public function showConfirm()
    {
        $bookingTam = session('booking_tam');
        if (! $bookingTam) {
            return redirect()->route('home');
        }

        // TIMER: Tính thời gian còn lại
        $masterTimerKey = 'hold_timer_'.Auth::id().'_'.$bookingTam['showtime_id'];
        $secondsLeft = 0;
        if (Cache::has($masterTimerKey)) {
            $secondsLeft = max(0, Cache::get($masterTimerKey) - now()->timestamp);
        }

        if ($secondsLeft <= 0) {
            session()->forget('booking_tam');

            return redirect()->route('booking.seat', ['showtime_id' => $bookingTam['showtime_id']])
                ->with('error', 'Hết thời gian giữ ghế (5 phút). Vui lòng chọn lại.');
        }

        $showtime = Showtime::with(['movie', 'room'])->findOrFail($bookingTam['showtime_id']);
        $seats = ShowtimeSeat::with('seat')
            ->whereIn('id', $bookingTam['seats'])
            ->get();

        $totalTicketPrice = $seats->sum('price');
        $combos = $bookingTam['combos'] ?? [];
        $totalComboPrice = $bookingTam['total_combo_amount'] ?? 0;
        $discountAmount = $bookingTam['discount_amount'] ?? 0; // Voucher discount

        // Coin discount (từ session nếu đã áp dụng)
        $coinUsed = $bookingTam['coin_used'] ?? 0;
        $coinDiscountAmount = $bookingTam['coin_discount_amount'] ?? 0;

        // Tính tổng: subtotal - voucher - xu
        $subtotal = $totalTicketPrice + $totalComboPrice;
        $afterVoucher = max(0, $subtotal - $discountAmount);
        $totalPrice = max(0, $afterVoucher - $coinDiscountAmount);

        // Tính thông tin xu cho UI
        $coinService = app(CoinRedemptionService::class);
        $coinInfo = $coinService->calculateMaxRedeemable(Auth::id(), $afterVoucher);

        return view('booking.confirm', compact(
            'showtime', 'seats', 'totalTicketPrice', 'combos',
            'totalComboPrice', 'discountAmount', 'totalPrice', 'secondsLeft',
            'coinUsed', 'coinDiscountAmount', 'coinInfo'
        ));
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_email' => 'required|email|max:255',
            'payment_method' => 'required|string',
        ]);

        $bookingTam = session('booking_tam');
        if (! $bookingTam) {
            return redirect()->route('home');
        }

        // TIMER: Kiểm tra còn thời gian giữ ghế không
        $masterTimerKey = 'hold_timer_'.Auth::id().'_'.$bookingTam['showtime_id'];
        if (! Cache::has($masterTimerKey) || Cache::get($masterTimerKey) <= now()->timestamp) {
            session()->forget('booking_tam');

            return redirect()->route('booking.seat', ['showtime_id' => $bookingTam['showtime_id']])
                ->with('error', 'Hết thời gian giữ ghế. Vui lòng chọn lại.');
        }

        // Lưu thời gian hết hạn vào session để trang payment dùng chung timer
        $holdExpireTimestamp = Cache::get($masterTimerKey);
        session()->put('hold_expire_at', $holdExpireTimestamp);

        $showtimeId = $bookingTam['showtime_id'];
        $seatIds = $bookingTam['seats'];

        DB::beginTransaction();
        try {
            // E3: Suất chiếu không còn khả dụng
            $showtime = Showtime::findOrFail($showtimeId);
            if (now()->greaterThan($showtime->start_time)) {
                throw new \Exception('Suất chiếu không còn khả dụng.');
            }

            // E1, E4: Kiểm tra lại toàn bộ ghế
            foreach ($seatIds as $seatId) {
                $cacheKey = 'seat_held_'.$showtimeId.'_'.$seatId;
                $heldBy = Cache::get($cacheKey);

                if (! $heldBy || $heldBy != Auth::id()) {
                    throw new \Exception('Ghế không còn khả dụng do hết thời gian giữ (5 phút) hoặc đã bị mua.');
                }
            }

            // Tính toán tổng tiền vé + combo + voucher
            $seats = ShowtimeSeat::whereIn('id', $seatIds)->get();

            $totalTicketAmount = $seats->sum('price');
            $totalComboAmount = $bookingTam['total_combo_amount'] ?? 0;
            $voucherDiscount = $bookingTam['discount_amount'] ?? 0;

            // Coin discount (intent từ session)
            $coinUsed = $bookingTam['coin_used'] ?? 0;
            $coinDiscountVND = $bookingTam['coin_discount_amount'] ?? 0;

            // Tổng giảm giá = voucher + xu
            $totalDiscount = $voucherDiscount + $coinDiscountVND;
            $finalAmount = $totalTicketAmount + $totalComboAmount - $totalDiscount;

            if ($finalAmount < 0) {
                $finalAmount = 0;
            }

            // BR03: Mã định danh duy nhất (CSPRNG + safe alphabet + kiểm tra trùng)
            $bookingCode = app(TicketService::class)->generateUniqueBookingCode();

            // BR07: Trạng thái dựa vào phương thức thanh toán
            $paymentMethod = $request->input('payment_method', 'ONLINE');
            $status = ($paymentMethod == 'CASH') ? 'PENDING_CASH_PAYMENT' : 'PENDING_PAYMENT';

            // Lưu thông tin khách hàng từ form xác nhận
            $customerName = $request->input('customer_name');
            $customerPhone = $request->input('customer_phone');
            $customerEmail = $request->input('customer_email');

            // Luồng chính: Tạo booking mới
            $booking = Booking::create([
                'booking_code' => $bookingCode,
                'user_id' => Auth::id(),
                'showtime_id' => $showtimeId,
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'customer_phone' => $customerPhone,
                'total_ticket_amount' => $totalTicketAmount,
                'total_combo_amount' => $totalComboAmount,
                'discount_amount' => $totalDiscount,
                'final_amount' => $finalAmount,
                'status' => 'PENDING',
                'payment_status' => 'UNPAID',
                'expired_at' => now()->addMinutes(5),
            ]);

            // Lưu danh sách ghế vào bảng trung gian
            foreach ($seats as $seat) {
                $row = $seat->seat->row_label ?? '';
                $seatType = $seat->seat->seat_type ?? 'STANDARD';
                if ($row === 'J' || $seatType === 'COUPLE') {
                    $seatType = 'COUPLE';
                } elseif ($seatType === 'DEMO') {
                    $seatType = 'DEMO';
                }

                DB::table('booking_seats')->insert([
                    'booking_id' => $booking->id,
                    'showtime_seat_id' => $seat->id,
                    'seat_code' => $seat->seat->seat_code ?? ('N/A'),
                    'seat_type' => $seatType,
                    'price' => $seat->price ?? 80000,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Giải phóng ghế khỏi Cache sau khi lưu DB thành công
                Cache::forget('seat_held_'.$showtimeId.'_'.$seat->id);
            }

            foreach (($bookingTam['combos'] ?? []) as $comboItem) {
                BookingCombo::create([
                    'booking_id' => $booking->id,
                    'combo_id' => $comboItem['combo_id'],
                    'quantity' => $comboItem['quantity'],
                    'unit_price' => $comboItem['unit_price'],
                    'total_price' => $comboItem['total_price'],
                ]);
            }

            // Tạo SepayOrder để trang QR Payment hoạt động
            $seatDetails = [];
            foreach ($seats as $s) {
                $seatCode = $s->seat->seat_code ?? 'N/A';
                $seatType = 'standard';
                $row = $s->seat->row_label ?? '';
                $seatKind = $s->seat->seat_type ?? 'STANDARD';
                if ($row === 'J' || $seatKind === 'COUPLE') {
                    $seatType = 'sweetbox';
                } elseif ($seatKind === 'VIP') {
                    $seatType = 'vip';
                } elseif ($seatKind === 'DEMO') {
                    $seatType = 'demo';
                }

                $seatDetails[] = [
                    'code' => $seatCode,
                    'type' => $seatType,
                    'price' => (int) $s->price,
                ];
            }

            $comboDetails = [];
            foreach (($bookingTam['combos'] ?? []) as $comboItem) {
                $comboDetails[] = [
                    'name' => $comboItem['name'],
                    'quantity' => $comboItem['quantity'],
                    'unit_price' => $comboItem['unit_price'],
                    'total_price' => $comboItem['total_price'],
                ];
            }

            $sepayOrder = SepayOrder::create([
                'order_code' => $bookingCode,
                'booking_id' => $booking->id,
                'package_id' => 'booking',
                'package_name' => 'Vé xem phim',
                'amount' => $finalAmount,
                'status' => 'pending',
                'metadata' => [
                    'movie_title' => $showtime->movie->title ?? '',
                    'room' => $showtime->room->name ?? '',
                    'showtime' => Carbon::parse($showtime->start_time)->format('H:i').' - '.Carbon::parse($showtime->end_time)->format('H:i'),
                    'show_date' => Carbon::parse($showtime->start_time)->format('d/m/Y'),
                    'format' => '2D',
                    'seats' => $seatDetails,
                    'seat_count' => count($seatDetails),
                    'combos' => $comboDetails,
                    'showtime_id' => $showtimeId,
                    'customer_email' => $customerEmail,
                    'customer_name' => $customerName,
                    'customer_phone' => $customerPhone,
                    'coin_used' => $coinUsed,
                    'coin_discount_amount' => $coinDiscountVND,
                    'voucher_code' => $bookingTam['voucher_code'] ?? null,
                    'discount_amount' => $voucherDiscount,
                ],
            ]);

            DB::commit();
            session()->forget('booking_tam');

            // Nếu tổng thanh toán = 0 (xu cover 100%) → tự động xác nhận PAID
            if ($finalAmount <= 0) {
                return $this->handleZeroAmountBooking($booking, $coinUsed);
            }

            return redirect()->route('booking.payment', ['orderCode' => $bookingCode]);

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('booking.seat', ['showtime_id' => $showtimeId])
                ->with('error', 'Lỗi: '.$e->getMessage());
        }
    }

    /**
     * Thuật toán kiểm tra xem cấu trúc ghế khách chọn có để lại "ghế trống cô đơn" nào không.
     * Trả về true nếu PHÁT HIỆN lỗi lẻ ghế, false nếu HỢP LỆ.
     */
    private function hasSingleSeatGap($showtimeId, $selectedSeatIds)
    {
        // 1. Lấy tất cả ghế của suất chiếu này để dựng lại sơ đồ phòng
        $allShowtimeSeats = ShowtimeSeat::with('seat')
            ->where('showtime_id', $showtimeId)
            ->get();

        // 2. TỐI ƯU: Lấy toàn bộ các ghế đã bán/đang thanh toán bằng 1 query duy nhất (Tránh lỗi N+1)
        $soldSeatIds = DB::table('booking_seats')
            ->join('bookings', 'bookings.id', '=', 'booking_seats.booking_id')
            ->where('bookings.showtime_id', $showtimeId)
            ->whereIn('bookings.status', ['PAID', 'PENDING_PAYMENT', 'PENDING_CASH_PAYMENT'])
            ->pluck('booking_seats.showtime_seat_id')
            ->all();

        // 3. Đổ dữ liệu vào ma trận hàng dọc/hàng ngang
        $matrix = [];
        foreach ($allShowtimeSeats as $seat) {
            $row = $seat->seat->row_label ?? null;
            $num = $seat->seat->seat_number ?? null;
            if (! $row || $num === null) {
                continue;
            }

            // Nếu ghế nằm trong danh sách khách đang chọn click sended lên
            if (in_array($seat->id, $selectedSeatIds)) {
                $status = 'SELECTED';
            } else {
                $baseSeatStatus = $seat->seat->status ?? 'ACTIVE';
                if ($baseSeatStatus === 'BLOCKED' || $baseSeatStatus === 'BROKEN') {
                    $status = 'BLOCKED';
                } elseif (in_array($seat->id, $soldSeatIds)) {
                    $status = 'SOLD';
                } else {
                    // Check xem có ai khác đang giữ trong cache không
                    $heldBy = Cache::get('seat_held_'.$showtimeId.'_'.$seat->id);
                    if ($heldBy && $heldBy != Auth::id()) {
                        $status = 'HELD';
                    } else {
                        $status = 'AVAILABLE'; // Ghế thực sự trống
                    }
                }
            }
            $matrix[$row][$num] = $status;
        }

        // 4. Quét từng hàng ghế để tìm lỗi "lẻ 1 ghế trống"
        foreach ($matrix as $row => $rowSeats) {
            ksort($rowSeats); // Sắp xếp lại số ghế theo thứ tự tăng dần (1, 2, 3...)

            $seatNumbers = array_keys($rowSeats);
            $totalInRow = count($seatNumbers);

            for ($i = 0; $i < $totalInRow; $i++) {
                $currentNum = $seatNumbers[$i];

                // Chúng ta chỉ săm soi những ghế đang có trạng thái trống (AVAILABLE)
                if ($rowSeats[$currentNum] === 'AVAILABLE') {

                    // --- KIỂM TRA BÊN TRÁI ---
                    // Bị chặn trái nếu: đầu hàng vật lý (i==0), số ghế không liên tục (lối đi rạp),
                    // hoặc ghế bên trái không phải là ghế trống (đã mua/đang chọn)
                    $leftBlocked = false;
                    if ($i === 0) {
                        $leftBlocked = true;
                    } else {
                        $prevNum = $seatNumbers[$i - 1];
                        if ($prevNum != $currentNum - 1) {
                            $leftBlocked = true; // Bị ngắt bởi lối đi rạp phim
                        } else {
                            $leftBlocked = ($rowSeats[$prevNum] !== 'AVAILABLE');
                        }
                    }

                    // --- KIỂM TRA BÊN PHẢI ---
                    // Tương tự, bị chặn phải nếu: cuối hàng vật lý, số ghế không liên tục,
                    // hoặc ghế bên phải không phải là ghế trống
                    $rightBlocked = false;
                    if ($i === $totalInRow - 1) {
                        $rightBlocked = true;
                    } else {
                        $nextNum = $seatNumbers[$i + 1];
                        if ($nextNum != $currentNum + 1) {
                            $rightBlocked = true; // Bị ngắt bởi lối đi rạp phim
                        } else {
                            $rightBlocked = ($rowSeats[$nextNum] !== 'AVAILABLE');
                        }
                    }

                    // Nếu phát hiện 1 ghế trống đơn lẻ bị kẹp thịt ở giữa -> Trả về lỗi luôn lập tức
                    if ($leftBlocked && $rightBlocked) {
                        return true;
                    }
                }
            }
        }

        return false; // Toàn bộ hàng ghế đều hợp lệ
    }

    // ==========================================
    // HỦY THANH TOÁN VÀ QUAY LẠI CHỌN GHẾ
    // ==========================================

    /**
     * Hủy booking từ trang thanh toán, giải phóng ghế, quay lại trang chọn ghế đúng suất chiếu.
     */
    public function cancelBookingAndRelease(string $orderCode)
    {
        $order = SepayOrder::where('order_code', $orderCode)->first();

        if (! $order) {
            return redirect()->route('home')
                ->with('error', 'Đơn hàng không tồn tại.');
        }

        $showtimeId = $order->getBookingInfo('showtime_id');

        // Hủy booking nếu còn ở trạng thái chờ thanh toán
        $booking = $order->booking;
        if ($booking && in_array($booking->status, ['PENDING', 'PENDING_PAYMENT'])) {
            $booking->update([
                'status' => 'CANCELLED',
                'payment_status' => $booking->payment_status === 'PAID' ? 'REFUNDED' : 'FAILED',
            ]);

            // Hoàn xu nếu đã trừ
            $redeemTx = PointTransaction::where('booking_id', $booking->id)
                ->where('type', 'REDEEM')
                ->first();
            if ($redeemTx && $booking->user_id) {
                app(CoinRedemptionService::class)->refundCoins(
                    $booking->user_id,
                    abs($redeemTx->points),
                    $booking->id
                );
            }

            // Giải phóng ghế trong cache (nếu còn held)
            $bookingSeats = DB::table('booking_seats')
                ->where('booking_id', $booking->id)
                ->pluck('showtime_seat_id');

            foreach ($bookingSeats as $showtimeSeatId) {
                $cacheKey = 'seat_held_' . $booking->showtime_id . '_' . $showtimeSeatId;
                Cache::forget($cacheKey);
            }
        }

        // Hủy sepay order
        if ($order->status === 'pending') {
            $order->update(['status' => 'expired']);
        }

        // Xóa session hold timer
        session()->forget('hold_expire_at');
        session()->forget('booking_tam');

        // Giải phóng master timer
        if (Auth::check() && $showtimeId) {
            $masterTimerKey = 'hold_timer_' . Auth::id() . '_' . $showtimeId;
            Cache::forget($masterTimerKey);
        }

        // Redirect về đúng trang chọn ghế của suất chiếu
        if ($showtimeId) {
            return redirect()->route('booking.seat', ['showtime_id' => $showtimeId])
                ->with('success', 'Đã hủy đơn hàng. Bạn có thể chọn ghế mới.');
        }

        return redirect()->route('home')
            ->with('success', 'Đã hủy đơn hàng thành công.');
    }

    // ==========================================
    // COIN REDEMPTION: ÁP DỤNG / HUỶ XU
    // ==========================================

    /**
     * Áp dụng xu giảm giá tại trang xác nhận đặt vé.
     */
    public function applyCoin(Request $request)
    {
        $request->validate([
            'coin_amount' => 'required|integer|min:1',
        ]);

        $bookingTam = session('booking_tam');
        if (! $bookingTam) {
            return back()->with('error', 'Không tìm thấy thông tin booking.');
        }

        $coinService = app(CoinRedemptionService::class);
        $result = $coinService->applyCoinDiscount(
            Auth::id(),
            (int) $request->coin_amount,
            $bookingTam
        );

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        session()->put('booking_tam', $result['bookingData']);

        return back()->with('success', $result['message']);
    }

    /**
     * Huỷ xu giảm giá đã áp dụng.
     */
    public function removeCoin()
    {
        $bookingTam = session('booking_tam');
        if (! $bookingTam) {
            return back();
        }

        $coinService = app(CoinRedemptionService::class);
        $bookingTam = $coinService->removeCoinDiscount($bookingTam);

        session()->put('booking_tam', $bookingTam);

        return back()->with('success', 'Đã huỷ sử dụng xu.');
    }

    /**
     * Xử lý khi tổng thanh toán = 0 (xu cover 100%).
     * Tự động xác nhận PAID, trừ xu, sinh vé.
     */
    private function handleZeroAmountBooking(Booking $booking, int $coinUsed)
    {
        try {
            DB::transaction(function () use ($booking, $coinUsed) {
                // Cập nhật booking sang PAID
                $booking->update([
                    'status' => 'PAID',
                    'payment_status' => 'PAID',
                    'paid_at' => now(),
                ]);

                // Tạo Payment record
                \App\Models\Payment::create([
                    'booking_id' => $booking->id,
                    'amount' => 0,
                    'payment_method' => 'COIN',
                    'status' => 'SUCCESS',
                    'paid_at' => now(),
                ]);

                // Trừ xu
                if ($coinUsed > 0) {
                    app(CoinRedemptionService::class)->deductCoins(
                        $booking->user_id,
                        $coinUsed,
                        $booking->id
                    );
                }

                // Tích xu membership (dù final_amount = 0, vẫn ghi nhận)
                app(\App\Services\MembershipService::class)->awardBookingCoin($booking);

                // Sinh vé
                $ticketService = app(TicketService::class);
                $ticketService->generateTicketsForBooking($booking);
            });

            // Cập nhật SepayOrder nếu có
            $sepayOrder = SepayOrder::where('booking_id', $booking->id)->first();
            if ($sepayOrder) {
                $sepayOrder->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'transaction_id' => 'COIN_' . time(),
                ]);

                // Sinh PDF vé và gửi email (giống SepayService flow)
                try {
                    $pdfPath = null;
                    $pdfService = app(\App\Services\TicketPDFService::class);
                    $pdfPath = $pdfService->generateBookingTicketsPDF($booking->fresh());

                    $customerEmail = $sepayOrder->getCustomerEmail();
                    $user = $booking->user;

                    if ($customerEmail) {
                        \Illuminate\Support\Facades\Mail::to($customerEmail)->send(
                            new \App\Mail\BookingInvoiceMail($sepayOrder->fresh(), $user, $pdfPath)
                        );

                        // Đánh dấu đã gửi email
                        $meta = $sepayOrder->metadata ?? [];
                        $meta['email_sent'] = true;
                        $meta['email_sent_at'] = now()->toIso8601String();
                        $meta['email_sent_to'] = $customerEmail;
                        $meta['pdf_attached'] = !empty($pdfPath);
                        $sepayOrder->update(['metadata' => $meta]);
                    }

                    if ($user) {
                        $user->notify(new \App\Notifications\BookingPaidNotification($sepayOrder->fresh()));
                    }
                } catch (\Exception $mailEx) {
                    \Illuminate\Support\Facades\Log::error('Failed to send email for coin payment', [
                        'booking_id' => $booking->id,
                        'error' => $mailEx->getMessage(),
                    ]);
                }
            }

            return redirect()->route('booking.bill', ['orderCode' => $booking->booking_code])
                ->with('success', 'Thanh toán bằng xu thành công!');

        } catch (\Exception $e) {
            return redirect()->route('home')
                ->with('error', 'Lỗi xử lý thanh toán: ' . $e->getMessage());
        }
    }
}
