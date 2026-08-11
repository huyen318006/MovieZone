<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingCancellation;
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
    // Giới hạn tối đa số ghế 1 khách hàng được chọn trong 1 lần đặt vé
    public const MAX_SEATS_PER_BOOKING = 10;

    /**
     * Lấy thời gian giữ ghế (phút) từ config.
     */
    private function holdMinutes(): int
    {
        return (int) config('booking.hold_minutes', 5);
    }

    // ==========================================
    // UC-CUS-08: CHỌN GHẾ
    // ==========================================
    // ==========================================
    // UC-CUS-08: CHỌN GHẾ
    // ==========================================
    public function showSeats(Request $request, $showtime_id)
    {
        $pendingOrderCode = session('pending_order_code');
        if ($pendingOrderCode) {
            $order = SepayOrder::where('order_code', $pendingOrderCode)->first();
            if ($order && $order->booking && in_array($order->booking->status, ['PENDING', 'PENDING_PAYMENT', 'PENDING_CASH_PAYMENT'])) {
                $booking = $order->booking;
                $booking->update([
                    'status' => 'CANCELLED',
                    'payment_status' => 'FAILED',
                ]);

                BookingCancellation::updateOrCreate(
                    ['booking_id' => $booking->id, 'type' => 'CANCELLATION'],
                    [
                        'type' => 'CANCELLATION',
                        'canceled_by' => Auth::id() ?? $booking->user_id,
                        'reason' => 'Khách quay lại từ payment qua browser back / seat page reload: tự hủy đơn pending và giải phóng ghế.',
                    ]
                );

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

                $bookingSeats = DB::table('booking_seats')
                    ->where('booking_id', $booking->id)
                    ->pluck('showtime_seat_id');

                foreach ($bookingSeats as $showtimeSeatId) {
                    $cacheKey = 'seat_held_' . $booking->showtime_id . '_' . $showtimeSeatId;
                    Cache::forget($cacheKey);
                }

                if (Auth::check() && $booking->showtime_id) {
                    $masterTimerKey = 'hold_timer_' . Auth::id() . '_' . $booking->showtime_id;
                    Cache::forget($masterTimerKey);
                }

                session()->forget('booking_tam');
                session()->forget('pending_order_code');
            } else {
                session()->forget('pending_order_code');
            }
        }

        $showtime = Showtime::with(['movie', 'room'])
            ->findOrFail($showtime_id);

        if (now()->greaterThan($showtime->start_time)) {
            return redirect()->back()->with('error', 'Suất chiếu này đã bắt đầu.');
        }

        // ====================== ĐẢM BẢO ĐẦY ĐỦ GHẾ ======================
        $this->syncShowtimeSeats($showtime);

        // Load lại dữ liệu sau khi sync
        $showtime->load('showtimeSeats.seat');

        // ====================== XỬ LÝ RESET / TIMER ======================
        // AJAX refresh (2.5s polling cập nhật ghế) → KHÔNG reset, KHÔNG chạm session/cache
        $isAjaxRefresh = $request->ajax() || $request->has('refresh');

        if (! $isAjaxRefresh) {
            // Flag ?reset=1 được gắn từ JS pageshow khi user bấm Back trên trình duyệt
            $shouldReset = $request->boolean('reset');

            if ($shouldReset) {
                // === RESET TOÀN BỘ QUÁ TRÌNH ===
                session()->forget('booking_tam');

                // Xóa pending order nếu có
                $existingOrderCode = session('pending_order_code');
                if ($existingOrderCode) {
                    $existingOrder = SepayOrder::where('order_code', $existingOrderCode)->first();
                    if ($existingOrder) {
                        $booking = $existingOrder->booking;
                        if ($booking && in_array($booking->status, ['PENDING', 'PENDING_PAYMENT', 'PENDING_CASH_PAYMENT'])) {
                            $booking->update([
                                'status' => 'CANCELLED',
                                'payment_status' => 'FAILED',
                            ]);
                        }
                        if ($existingOrder->status === 'pending') {
                            $existingOrder->update(['status' => 'expired']);
                        }
                    }
                    session()->forget('pending_order_code');
                }

                // Giải phóng các ghế user đang giữ
                $showtimeSeatIds = DB::table('showtime_seats')->where('showtime_id', $showtime_id)->pluck('id');
                foreach ($showtimeSeatIds as $stId) {
                    $seatKey = 'seat_held_' . $showtime_id . '_' . $stId;
                    if (Cache::get($seatKey) == Auth::id()) {
                        Cache::forget($seatKey);
                    }
                }

                // Xóa timer cũ để tạo mới bên dưới
                $masterTimerKey = 'hold_timer_'.Auth::id().'_'.$showtime_id;
                Cache::forget($masterTimerKey);
            }

            // === TẠO TIMER NẾU CHƯA CÓ ===
            // Lần đầu vào trang: chưa có timer → tạo mới 5 phút.
            // Đã có timer (F5, reload): giữ nguyên timer cũ, KHÔNG reset.
            // Sau khi reset (back từ combo/confirm): timer vừa bị xóa → sẽ tạo mới.
            $masterTimerKey = 'hold_timer_'.Auth::id().'_'.$showtime_id;
            if (! Cache::has($masterTimerKey)) {
                $expireAt = now()->addMinutes($this->holdMinutes());
                Cache::put($masterTimerKey, $expireAt->timestamp, $expireAt);
            }
        }

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
                $isPaid = DB::table('booking_seats')
                    ->join('bookings', 'bookings.id', '=', 'booking_seats.booking_id')
                    ->where('booking_seats.showtime_seat_id', $showtimeSeat->id)
                    ->where('bookings.showtime_id', $showtime_id)
                    ->where('bookings.status', 'PAID')
                    ->exists();

                $isPendingPayment = DB::table('booking_seats')
                    ->join('bookings', 'bookings.id', '=', 'booking_seats.booking_id')
                    ->where('booking_seats.showtime_seat_id', $showtimeSeat->id)
                    ->where('bookings.showtime_id', $showtime_id)
                    ->whereIn('bookings.status', ['PENDING', 'PENDING_PAYMENT', 'PENDING_CASH_PAYMENT'])
                    ->where(function ($q) {
                        $q->whereNull('bookings.expired_at')
                          ->orWhere('bookings.expired_at', '>', now());
                    })
                    ->exists();

                if ($isPaid) {
                    $displayStatus = 'SOLD';
                } elseif ($isPendingPayment) {
                    $displayStatus = 'HELD';
                } else {
                    $heldBy = $showtimeSeat->held_by ?? Cache::get('seat_held_'.$showtime_id.'_'.$showtimeSeat->id);
                    $baseStatus = $showtimeSeat->status ?? 'AVAILABLE';

                    if ($baseStatus === 'HELD' || $heldBy) {
                        $displayStatus = ($heldBy == Auth::id()) ? 'HELD_BY_ME' : 'HELD';
                    } else {
                        $displayStatus = 'AVAILABLE';
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
                    // Bỏ lối đi (aisle): mọi ghế trong hàng liền mạch, nhóm khách ngồi cạnh nhau, không bị tách
                    'is_aisle' => false,
                ];
            }
        }

        // TÍNH THỜI GIAN CÒN LẠI — truyền timestamp cho frontend
        $masterTimerKey = 'hold_timer_'.Auth::id().'_'.$showtime_id;
        $holdExpiresAt = null;
        $serverTime = now()->toIso8601String();
        $holdTotalSeconds = $this->holdMinutes() * 60;
        if (Cache::has($masterTimerKey)) {
            $ts = Cache::get($masterTimerKey);
            if ($ts > now()->timestamp) {
                $holdExpiresAt = Carbon::createFromTimestamp($ts)->toIso8601String();
            }
        }

        return response()->view('booking.seat', compact('showtime', 'seatMap', 'holdExpiresAt', 'serverTime', 'holdTotalSeconds'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
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

            // Check ngoại lệ E1: Đã bán (chỉ xét booking chưa expired)
            $isSold = DB::table('booking_seats')
                ->join('bookings', 'bookings.id', '=', 'booking_seats.booking_id')
                ->where('booking_seats.showtime_seat_id', $seatId)
                ->where('bookings.showtime_id', $showtimeId)
                ->whereIn('bookings.status', ['PAID', 'PENDING', 'PENDING_CASH_PAYMENT', 'PENDING_PAYMENT'])
                ->where(function ($q) {
                    $q->whereNull('bookings.expired_at')
                      ->orWhere('bookings.expired_at', '>', now());
                })
                ->exists();

            if ($isSold) {
                return response()->json(['success' => false, 'error_type' => 'SOLD', 'message' => 'E1: Ghế đã được bán.']);
            }

            // Check ngoại lệ E2: Đang bị người khác giữ
            // Sử dụng Cache::add() — atomic: chỉ set nếu key chưa tồn tại
            $heldBy = Cache::get($cacheKey);
            if ($heldBy && $heldBy != Auth::id()) {
                return response()->json(['success' => false, 'error_type' => 'HELD', 'message' => 'Rất tiếc, ghế này đã được người khác giữ. Vui lòng chọn ghế khác.']);
            }

            // Key lưu thời gian giữ ghế tổng của user cho suất chiếu này
            $masterTimerKey = 'hold_timer_'.Auth::id().'_'.$showtimeId;
            $holdMinutes = $this->holdMinutes();

            // Nếu chưa có timer tổng → tạo mới
            // Cache::add() chỉ thành công nếu key chưa tồn tại (atomic)
            $expireAt = now()->addMinutes($holdMinutes);
            $added = Cache::add($masterTimerKey, $expireAt->timestamp, $expireAt);

            if (! $added) {
                // Timer đã tồn tại → lấy lại thời điểm hết hạn cũ
                // KHÔNG tạo mới để tránh reset thời gian
                $existingTs = Cache::get($masterTimerKey);
                if ($existingTs) {
                    $expireAt = Carbon::createFromTimestamp($existingTs);
                }
                // Nếu cache đã expire giữa lúc add và get → dùng expireAt mới tạo ở trên
            }

            // Kiểm tra nếu timer đã hết hạn
            if ($expireAt->isPast()) {
                return response()->json([
                    'success' => false,
                    'error_type' => 'EXPIRED',
                    'message' => 'Hết thời gian giữ ghế. Vui lòng chọn lại.',
                ]);
            }

            // Lưu trạng thái giữ ghế — dùng Cache::add() cho ghế chưa ai giữ
            if (! $heldBy) {
                $seatAdded = Cache::add($cacheKey, Auth::id(), $expireAt);
                if (! $seatAdded) {
                    // Ai đó đã giữ trong khoảnh khắc giữa get và add
                    $actualHolder = Cache::get($cacheKey);
                    if ($actualHolder && $actualHolder != Auth::id()) {
                        return response()->json([
                            'success' => false,
                            'error_type' => 'HELD',
                            'message' => 'Rất tiếc, ghế này vừa được người khác giữ.',
                        ]);
                    }
                }
            } else {
                // User đã giữ ghế này → cập nhật TTL theo master timer
                Cache::put($cacheKey, Auth::id(), $expireAt);
            }

            return response()->json([
                'success' => true,
                'serverTime' => now()->toIso8601String(),
                'expiresAt' => $expireAt->toIso8601String(),
            ]);
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
            // BR01: Ít nhất 1 ghế, tối đa MAX_SEATS_PER_BOOKING (10 ghế)
            'seats' => 'required|array|min:1|max:'.self::MAX_SEATS_PER_BOOKING,
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

        $userId = \App\Helpers\TabAuthHelper::currentUser()?->id ?? Auth::id();
        $tierDiscountPercent = 0;
        $tierName = 'BRONZE';
        if ($userId) {
            $userMembership = \App\Models\UserMembership::with('level')->where('user_id', $userId)->first();
            $tierDiscountPercent = (float) ($userMembership?->level?->discount_percent ?? 0);
            $tierName = strtoupper($userMembership?->level?->name ?? 'BRONZE');
        }
        $tierDiscountAmount = (float) round(($totalSeatAmount * $tierDiscountPercent) / 100);

        session([
            'booking_tam' => [
                'showtime_id' => $request->showtime_id,
                'seats' => $request->seats,

                // Ticket
                'total_seat_amount' => $totalSeatAmount,

                // Tier Discount (% theo Hạng)
                'tier_name' => $tierName,
                'tier_percent' => $tierDiscountPercent,
                'tier_discount_amount' => $tierDiscountAmount,

                // Combo
                'combos' => [],
                'total_combo_amount' => 0,

                // Voucher
                'voucher_id' => null,
                'voucher_code' => null,
                'discount_amount' => 0,

                // Total
                'subtotal' => $totalSeatAmount,
                'total' => max(0, $totalSeatAmount - $tierDiscountAmount),
            ],
        ]);

        return redirect(\App\Helpers\TabAuthHelper::route('booking.combo'));
    }

    // ==========================================
    // UC-CUS-09: CHỌN COMBO
    // ==========================================
    public function showCombo()
    {
        $bookingTam = session('booking_tam');
        
        if (!$bookingTam || empty($bookingTam['showtime_id'])) {
            return redirect()->route('home')->with('error', 'Phiên đặt vé đã hết hạn hoặc không tồn tại.');
        }

        // TIMER: Tính thời gian còn lại từ master timer
        $holdExpiresAt = null;
        $serverTime = now()->toIso8601String();
        $holdTotalSeconds = $this->holdMinutes() * 60;

        $masterTimerKey = 'hold_timer_'.Auth::id().'_'.$bookingTam['showtime_id'];
        if (Cache::has($masterTimerKey)) {
            $ts = Cache::get($masterTimerKey);
            $secondsLeft = max(0, $ts - now()->timestamp);
            if ($secondsLeft > 0) {
                $holdExpiresAt = Carbon::createFromTimestamp($ts)->toIso8601String();
            }
        }

        // Nếu hết thời gian → redirect về trang ghế
        if (! $holdExpiresAt) {
            session()->forget('booking_tam');
            $holdMinutes = $this->holdMinutes();

            return redirect()->route('booking.seat', ['showtime_id' => $bookingTam['showtime_id']])
                ->with('error', "Hết thời gian giữ ghế ({$holdMinutes} phút). Vui lòng chọn lại.");
        }

        $showtime = Showtime::with(['movie', 'cinema', 'room'])
            ->find($bookingTam['showtime_id']);

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

        return response()->view('booking.combo', compact('combos', 'holdExpiresAt', 'serverTime', 'holdTotalSeconds', 'bookingTam', 'showtime'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
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

        // 🔥 BẮT BUỘC: lấy lại seat total + tier discount + voucher discount từ session hiện tại
        $seatTotal = $bookingTam['total_seat_amount'] ?? 0;
        $tierDiscountAmount = $bookingTam['tier_discount_amount'] ?? 0;
        $discount = $bookingTam['discount_amount'] ?? 0;

        $bookingTam['subtotal'] = $seatTotal + $comboTotal;
        $bookingTam['total'] = max(0, $bookingTam['subtotal'] - $tierDiscountAmount - $discount);

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

        // TIMER: Tính thời gian còn lại — truyền timestamp cho frontend
        $masterTimerKey = 'hold_timer_'.Auth::id().'_'.$bookingTam['showtime_id'];
        $holdExpiresAt = null;
        $serverTime = now()->toIso8601String();
        $holdTotalSeconds = $this->holdMinutes() * 60;
        if (Cache::has($masterTimerKey)) {
            $ts = Cache::get($masterTimerKey);
            $secondsLeft = max(0, $ts - now()->timestamp);
            if ($secondsLeft > 0) {
                $holdExpiresAt = Carbon::createFromTimestamp($ts)->toIso8601String();
            }
        }

        if (! $holdExpiresAt) {
            session()->forget('booking_tam');
            $holdMinutes = $this->holdMinutes();

            return redirect()->route('booking.seat', ['showtime_id' => $bookingTam['showtime_id']])
                ->with('error', "Hết thời gian giữ ghế ({$holdMinutes} phút). Vui lòng chọn lại.");
        }

        $showtime = Showtime::with(['movie', 'room'])->findOrFail($bookingTam['showtime_id']);
        $showtime_id = $bookingTam['showtime_id'];
        $seats = ShowtimeSeat::with('seat')
            ->whereIn('id', $bookingTam['seats'])
            ->get();

        $totalTicketPrice = $seats->sum('price');
        $combos = $bookingTam['combos'] ?? [];
        $totalComboPrice = $bookingTam['total_combo_amount'] ?? 0;
        $tierDiscountAmount = $bookingTam['tier_discount_amount'] ?? 0;
        $tierName = $bookingTam['tier_name'] ?? 'BRONZE';
        $tierPercent = $bookingTam['tier_percent'] ?? 0;
        $discountAmount = $bookingTam['discount_amount'] ?? 0; // Voucher discount

        // Coin discount (từ session nếu đã áp dụng)
        $coinUsed = $bookingTam['coin_used'] ?? 0;
        $coinDiscountAmount = $bookingTam['coin_discount_amount'] ?? 0;

        // Tính tổng: subtotal - giảm hạng - voucher - xu
        $subtotal = $totalTicketPrice + $totalComboPrice;
        $afterDiscounts = max(0, $subtotal - $tierDiscountAmount - $discountAmount);
        $totalPrice = max(0, $afterDiscounts - $coinDiscountAmount);

        // Tính thông tin xu cho UI
        $coinService = app(CoinRedemptionService::class);
        $coinInfo = $coinService->calculateMaxRedeemable(Auth::id(), $afterDiscounts);

        return response()->view('booking.confirm', compact(
            'showtime', 'showtime_id', 'seats', 'totalTicketPrice', 'combos',
            'totalComboPrice', 'tierDiscountAmount', 'tierName', 'tierPercent',
            'discountAmount', 'totalPrice', 'holdExpiresAt', 'serverTime', 'holdTotalSeconds',
            'coinUsed', 'coinDiscountAmount', 'coinInfo'
        ))->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
          ->header('Pragma', 'no-cache');
    }

    public function releaseOnBack(Request $request)
    {
        $showtimeId = $request->input('showtime_id');
        $seatIdsRaw = $request->input('seat_ids');

        $seatIds = [];
        if (is_array($seatIdsRaw)) {
            $seatIds = array_map('strval', $seatIdsRaw);
        } elseif (is_string($seatIdsRaw)) {
            $decoded = json_decode($seatIdsRaw, true);
            if (is_array($decoded)) {
                $seatIds = array_map('strval', $decoded);
            } else {
                $seatIds = [$seatIdsRaw];
            }
        }

        if (Auth::check() && $showtimeId) {
            foreach ($seatIds as $seatId) {
                $cacheKey = 'seat_held_' . $showtimeId . '_' . $seatId;
                Cache::forget($cacheKey);
            }

            $masterTimerKey = 'hold_timer_' . Auth::id() . '_' . $showtimeId;
            Cache::forget($masterTimerKey);
        }

        $orderCode = session('pending_order_code');
        if ($orderCode) {
            $order = SepayOrder::where('order_code', $orderCode)->first();
            if ($order && $order->booking && in_array($order->booking->status, ['PENDING', 'PENDING_PAYMENT', 'PENDING_CASH_PAYMENT'])) {
                $booking = $order->booking;
                $booking->update([
                    'status' => 'CANCELLED',
                    'payment_status' => 'FAILED',
                ]);

                BookingCancellation::updateOrCreate(
                    ['booking_id' => $booking->id, 'type' => 'CANCELLATION'],
                    [
                        'type' => 'CANCELLATION',
                        'canceled_by' => Auth::id() ?? $booking->user_id,
                        'reason' => 'Khách hàng quay lại bằng browser back / lịch sử trình duyệt, giải phóng giữ ghế.',
                    ]
                );

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

                $bookingSeats = DB::table('booking_seats')
                    ->where('booking_id', $booking->id)
                    ->pluck('showtime_seat_id');

                foreach ($bookingSeats as $showtimeSeatId) {
                    $cacheKey = 'seat_held_' . $booking->showtime_id . '_' . $showtimeSeatId;
                    Cache::forget($cacheKey);
                }
            }

            if ($order && $order->status === 'pending') {
                $order->update(['status' => 'expired']);
            }

            session()->forget('booking_tam');
            session()->forget('pending_order_code');
        } else {
            session()->forget('booking_tam');
        }

        return response()->json(['success' => true]);
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
        $masterTimerTs = Cache::get($masterTimerKey);
        if (! $masterTimerTs || $masterTimerTs <= now()->timestamp) {
            session()->forget('booking_tam');

            return redirect()->route('booking.seat', ['showtime_id' => $bookingTam['showtime_id']])
                ->with('error', 'Hết thời gian giữ ghế. Vui lòng chọn lại.');
        }

        // Cấp thêm 5 phút dành riêng cho bước chuyển khoản thanh toán QR
        $paymentExpireAt = now()->addMinutes($this->holdMinutes());

        // GUARD: Kiểm tra đã tạo booking cho session này chưa
        $existingOrderCode = session('pending_order_code');
        if ($existingOrderCode) {
            $existingOrder = SepayOrder::where('order_code', $existingOrderCode)->first();
            if ($existingOrder) {
                if ($existingOrder->status === 'paid' || ($existingOrder->booking && $existingOrder->booking->status === 'PAID')) {
                    // Nếu đã thanh toán, chuyển thẳng sang trang bill
                    return redirect()->route('booking.bill', ['orderCode' => $existingOrderCode]);
                }

                // Nếu chưa thanh toán (đang pending), hủy đơn cũ để tạo đơn mới với tùy chọn thanh toán mới
$booking = $existingOrder->booking;
                if ($booking && in_array($booking->status, ['PENDING', 'PENDING_PAYMENT', 'PENDING_CASH_PAYMENT'])) {
                    $booking->update([
                        'status' => 'CANCELLED',
                        'payment_status' => 'FAILED',
                    ]);

                    // Lưu lý do hủy vào bảng booking_cancellations (hủy đơn cũ để tạo đơn mới)
                    BookingCancellation::updateOrCreate(
                        ['booking_id' => $booking->id, 'type' => 'CANCELLATION'],
                        [
                            'type'        => 'CANCELLATION',
                            'canceled_by' => Auth::id() ?? $booking->user_id,
                            'reason'      => 'Khách hàng hủy đơn cũ khi tạo lại đơn mới với tùy chọn thanh toán khác.',
                        ]
                    );

                    // Hoàn xu nếu đã trừ cho đơn cũ
                    $redeemTx = \App\Models\PointTransaction::where('booking_id', $booking->id)
                        ->where('type', 'REDEEM')
                        ->first();
                    if ($redeemTx && $booking->user_id) {
                        app(\App\Services\CoinRedemptionService::class)->refundCoins(
                            $booking->user_id,
                            abs($redeemTx->points),
                            $booking->id
                        );
                    }
                }
                if ($existingOrder->status === 'pending') {
                    $existingOrder->update(['status' => 'expired']);
                }
                session()->forget('pending_order_code');
            }
        }


        $showtimeId = $bookingTam['showtime_id'];
        $seatIds = $bookingTam['seats'];

        DB::beginTransaction();
        try {
            // E3: Suất chiếu không còn khả dụng
            $showtime = Showtime::findOrFail($showtimeId);
            if (now()->greaterThan($showtime->start_time)) {
                throw new \Exception('Suất chiếu không còn khả dụng.');
            }

            // E1, E4: Kiểm tra lại toàn bộ ghế (cache)
            foreach ($seatIds as $seatId) {
                $cacheKey = 'seat_held_'.$showtimeId.'_'.$seatId;
                $heldBy = Cache::get($cacheKey);

                if (! $heldBy || $heldBy != Auth::id()) {
                    $holdMinutes = $this->holdMinutes();
                    throw new \Exception("Ghế không còn khả dụng do hết thời gian giữ ({$holdMinutes} phút) hoặc đã bị mua.");
                }
            }

            // CHỐNG DOUBLE BOOKING: Kiểm tra DB — có booking ACTIVE nào khác đang giữ cùng ghế không?
            $conflictExists = DB::table('booking_seats')
                ->join('bookings', 'bookings.id', '=', 'booking_seats.booking_id')
                ->where('bookings.showtime_id', $showtimeId)
                ->whereIn('booking_seats.showtime_seat_id', $seatIds)
                ->whereIn('bookings.status', ['PAID', 'PENDING', 'PENDING_PAYMENT', 'PENDING_CASH_PAYMENT'])
                ->where(function ($q) {
                    $q->whereNull('bookings.expired_at')
                      ->orWhere('bookings.expired_at', '>', now());
                })
                ->lockForUpdate()
                ->exists();

            if ($conflictExists) {
                throw new \Exception('Một hoặc nhiều ghế đã được người khác đặt. Vui lòng chọn lại.');
            }

            // Tính toán tổng tiền vé + combo + voucher + giảm hạng
            $seats = ShowtimeSeat::whereIn('id', $seatIds)->get();

            $totalTicketAmount = $seats->sum('price');
            $totalComboAmount = $bookingTam['total_combo_amount'] ?? 0;
            $tierDiscountAmount = $bookingTam['tier_discount_amount'] ?? 0;
            $voucherDiscount = $bookingTam['discount_amount'] ?? 0;

            // Coin discount (intent từ session)
            $coinUsed = $bookingTam['coin_used'] ?? 0;
            $coinDiscountVND = $bookingTam['coin_discount_amount'] ?? 0;

            // Tổng giảm giá = giảm hạng thành viên + voucher + xu
            $totalDiscount = $tierDiscountAmount + $voucherDiscount + $coinDiscountVND;
            $finalAmount = $totalTicketAmount + $totalComboAmount - $totalDiscount;

            if ($finalAmount < 0) {
                $finalAmount = 0;
            }

            // BR03: Mã định danh duy nhất (CSPRNG + safe alphabet + kiểm tra trùng)
            $bookingCode = app(TicketService::class)->generateUniqueBookingCode();

            $status = 'PENDING';

            // Lưu thông tin khách hàng từ form xác nhận
            $customerName = $request->input('customer_name');
            $customerPhone = $request->input('customer_phone');
            $customerEmail = $request->input('customer_email');

            // Luồng chính: Tạo booking mới
            // expired_at dùng thời điểm hết hạn gốc từ master timer (KHÔNG reset thêm thời gian)
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
'status' => $status,
                'payment_status' => 'UNPAID',
                'expired_at' => $paymentExpireAt,
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

                // Gia hạn cache giữ ghế theo thời gian thanh toán 5 phút mới
                $cacheKey = 'seat_held_' . $showtimeId . '_' . $seat->id;
                Cache::put($cacheKey, Auth::id(), $paymentExpireAt);
            }

            // Gia hạn master timer theo thời gian thanh toán mới
            Cache::put($masterTimerKey, $paymentExpireAt->timestamp, $paymentExpireAt);

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
                    'showtime_seat_id' => (int) $s->id,
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

            // Lưu order code vào session để guard chống tạo booking trùng + dùng khi quay lại
            session()->put('pending_order_code', $bookingCode);
            // GIỮ NGUYÊN booking_tam — chỉ xóa khi thanh toán thành công hoặc hủy đơn

            // Nếu tổng thanh toán = 0 (xu cover 100%) → tự động xác nhận PAID
            if ($finalAmount <= 0) {
                session()->forget('booking_tam');
                session()->forget('pending_order_code');
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
            ->whereIn('bookings.status', ['PAID', 'PENDING', 'PENDING_PAYMENT', 'PENDING_CASH_PAYMENT'])
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

                    // Chỉ coi là lỗi khi ghế trống cô lập này do CHÍNH ghế khách đang chọn (SELECTED) gây ra.
                    // Nếu nó bị chặn bởi ghế của người khác (SOLD/HELD/BLOCKED) thì KHÔNG báo lỗi,
                    // vì khách không kiểm soát được những ghế đó.
                    if ($leftBlocked && $rightBlocked) {
                        $leftIsSelected = ($i > 0) && ($rowSeats[$seatNumbers[$i - 1]] === 'SELECTED')
                            && ($seatNumbers[$i - 1] === $currentNum - 1);
                        $rightIsSelected = ($i < $totalInRow - 1) && ($rowSeats[$seatNumbers[$i + 1]] === 'SELECTED')
                            && ($seatNumbers[$i + 1] === $currentNum + 1);

                        if ($leftIsSelected || $rightIsSelected) {
                            return true; // Lỗ hổng do chính khách tạo ra
                        }
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

            // Lưu lý do hủy vào bảng booking_cancellations (khách tự hủy)
            BookingCancellation::updateOrCreate(
                ['booking_id' => $booking->id, 'type' => 'CANCELLATION'],
                [
                    'type'       => 'CANCELLATION',
                    'canceled_by' => Auth::id() ?? $booking->user_id,
                    'reason'     => 'Khách hàng tự hủy đơn hàng từ trang thanh toán / quay lại chọn ghế.',
                ]
            );

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

        // Xóa session booking tạm
        session()->forget('booking_tam');
        session()->forget('pending_order_code');

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

                // Đẩy PDF + Email + Notification vào hàng đợi chạy ngầm
                \App\Jobs\SendBookingInvoiceJob::dispatch($sepayOrder->id);
            }

            return redirect()->route('booking.bill', ['orderCode' => $booking->booking_code])
                ->with('success', 'Thanh toán bằng xu thành công!');

        } catch (\Exception $e) {
            return redirect()->route('home')
                ->with('error', 'Lỗi xử lý thanh toán: ' . $e->getMessage());
        }
    }
}
