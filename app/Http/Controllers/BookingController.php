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
        return (int) config('booking.hold_minutes', 10);
    }

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
                    $tabToken = request('tab_token') ?? request()->attributes->get('tab_token');

                    if ($baseStatus === 'HELD' || $heldBy) {
                        $displayStatus = ($heldBy === $tabToken) ? 'HELD_BY_ME' : 'HELD';
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

        // ====================== INIT HOLD SESSION ======================
        $tabToken = request('tab_token') ?? request()->attributes->get('tab_token');
        if ($tabToken) {
            $holdSessionKey = "hold_session_{$tabToken}_{$showtime_id}";
            $existingSession = Cache::get($holdSessionKey);
            $holdMinutes = $this->holdMinutes();
            
            if ($existingSession) {
                $sessionData = json_decode($existingSession, true);
                if (\Carbon\Carbon::parse($sessionData['expiresAt'])->isPast()) {
                    $sessionData = [
                        'seats'     => [],
                        'expiresAt' => now()->addMinutes($holdMinutes)->toIso8601String(),
                        'startedAt' => now()->toIso8601String(),
                    ];
                    Cache::put($holdSessionKey, json_encode($sessionData), now()->addMinutes($holdMinutes));
                }
            } else {
                $sessionData = [
                    'seats'     => [],
                    'expiresAt' => now()->addMinutes($holdMinutes)->toIso8601String(),
                    'startedAt' => now()->toIso8601String(),
                ];
                Cache::put($holdSessionKey, json_encode($sessionData), now()->addMinutes($holdMinutes));
            }
            
            $expiresAt = $sessionData['expiresAt'];
            $holdToken = hash_hmac('sha256', "{$tabToken}:{$showtime_id}:{$expiresAt}", config('app.key'));
            $holdTotalSeconds = $holdMinutes * 60;
            $serverTime = now()->toIso8601String();
            
            view()->share(compact('expiresAt', 'holdToken', 'holdTotalSeconds', 'serverTime'));
        }

        return view('booking.seat', compact('showtime', 'seatMap'));
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
        $showtimeId  = $request->showtime_id;
        $seatId      = $request->seat_id;
        $action      = $request->action;
        $tabToken    = $request->query('tab_token') ?? $request->attributes->get('tab_token');
        $holdMinutes = $this->holdMinutes();
        $seatCacheKey = "seat_held_{$showtimeId}_{$seatId}";

        if ($action === 'hold') {
            // --- Validation: ghế bị khóa/hỏng ---
            $seat = ShowtimeSeat::with('seat')->find($seatId);
            if (! $seat || in_array($seat->seat->status ?? 'ACTIVE', ['BLOCKED', 'BROKEN'])) {
                return response()->json([
                    'success'    => false,
                    'error_type' => 'BLOCKED',
                    'message'    => 'Ghế này hiện không thể chọn.',
                ]);
            }

            // --- Validation: đã bán (booking chưa expired) ---
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
                return response()->json([
                    'success'    => false,
                    'error_type' => 'SOLD',
                    'message'    => 'E1: Ghế đã được bán.',
                ]);
            }

            // --- HOLD SESSION: tạo hoặc reuse ---
            $holdSessionKey  = "hold_session_{$tabToken}_{$showtimeId}";
            $existingSession = Cache::get($holdSessionKey);
            $isNew           = false;

            if ($existingSession) {
                $sessionData = json_decode($existingSession, true);
                $expiresAt   = Carbon::parse($sessionData['expiresAt']);

                if ($expiresAt->isPast()) {
                    // Session cũ hết hạn → cleanup và từ chối
                    Cache::forget($holdSessionKey);
                    return response()->json([
                        'success'    => false,
                        'error_type' => 'SESSION_EXPIRED',
                        'message'    => 'Phiên giữ ghế đã hết hạn. Vui lòng chọn lại.',
                    ]);
                }
            } else {
                // Tạo hold session MỚI
                $expiresAt = now()->addMinutes($holdMinutes);
                $isNew     = true;
            }

            // --- ATOMIC SEAT LOCK: Cache::add only ---
            $lockAcquired = Cache::add($seatCacheKey, $tabToken, $expiresAt);

            if (! $lockAcquired) {
                $currentHolder = Cache::get($seatCacheKey);

                if ($currentHolder === $tabToken) {
                    // Đã là của mình → chỉ cập nhật TTL (an toàn, cùng owner)
                    Cache::put($seatCacheKey, $tabToken, $expiresAt);
                } else {
                    // Người khác đang giữ → TỪ CHỐI
                    return response()->json([
                        'success'    => false,
                        'error_type' => 'HELD',
                        'message'    => 'Rất tiếc, ghế này đã được người khác giữ. Vui lòng chọn ghế khác.',
                    ]);
                }
            }

            // --- UPDATE HOLD SESSION (server-managed seat list) ---
            $startedAt = now();
            if ($existingSession) {
                $sessionData = json_decode($existingSession, true);
                $seats       = $sessionData['seats'];
                if (! in_array((int) $seatId, $seats)) {
                    $seats[] = (int) $seatId;
                }
                $startedAt = Carbon::parse($sessionData['startedAt'] ?? now()->toIso8601String());
            } else {
                $seats = [(int) $seatId];
            }

            $sessionValue = json_encode([
                'seats'     => array_values($seats),
                'startedAt' => $startedAt->toIso8601String(),
                'expiresAt' => $expiresAt->toIso8601String(),
            ]);
            Cache::put($holdSessionKey, $sessionValue, $expiresAt);

            // --- HMAC holdToken ---
            $holdToken = hash_hmac('sha256',
                "{$tabToken}:{$showtimeId}:{$expiresAt->toIso8601String()}",
                config('app.key')
            );

            return response()->json([
                'success'          => true,
                'expiresAt'        => $expiresAt->toIso8601String(),
                'serverTime'       => now()->toIso8601String(),
                'holdTotalSeconds' => $holdMinutes * 60,
                'isNewSession'     => $isNew,
                'holdToken'        => $holdToken,
            ]);

        } elseif ($action === 'release') {
            // --- RELEASE: chỉ xóa nếu chính mình đang giữ ---
            $currentHolder = Cache::get($seatCacheKey);
            if ($currentHolder === $tabToken) {
                Cache::forget($seatCacheKey);
            }

            // --- SERVER-MANAGED: update hold session seat list ---
            $holdSessionKey  = "hold_session_{$tabToken}_{$showtimeId}";
            $existingSession = Cache::get($holdSessionKey);
            $holdCleared     = false;

            if ($existingSession) {
                $sessionData = json_decode($existingSession, true);
                $seats       = array_values(array_diff($sessionData['seats'], [(int) $seatId]));

                if (empty($seats)) {
                    // Không destroy hold session để bộ đếm 10p vẫn chạy
                    $sessionData['seats'] = [];
                    $expiresAt = Carbon::parse($sessionData['expiresAt']);
                    Cache::put($holdSessionKey, json_encode($sessionData), $expiresAt);
                } else {
                    $sessionData['seats'] = $seats;
                    $expiresAt = Carbon::parse($sessionData['expiresAt']);
                    Cache::put($holdSessionKey, json_encode($sessionData), $expiresAt);
                }
            }

            return response()->json([
                'success'     => true,
                'holdCleared' => $holdCleared,
            ]);
        }
    }

    public function submitSeats(Request $request)
    {
        $request->validate([
            'showtime_id' => 'required',
            // BR01: Ít nhất 1 ghế, tối đa MAX_SEATS_PER_BOOKING (10 ghế)
            'seats' => 'required|array|min:1|max:'.self::MAX_SEATS_PER_BOOKING,
        ]);

        $tabToken   = $request->query('tab_token') ?? $request->attributes->get('tab_token');
        $showtimeId = $request->showtime_id;
        $seatIds    = $request->seats;
        $userId     = Auth::id();

        $seats = ShowtimeSeat::with('seat')->whereIn('id', $seatIds)->get();
        $invalidSeats = $seats->filter(function ($seat) {
            return in_array($seat->seat->status ?? 'ACTIVE', ['BLOCKED', 'BROKEN']);
        });

        if ($invalidSeats->isNotEmpty()) {
            return back()->withErrors(['error' => 'Một số ghế đã bị khóa hoặc hỏng, vui lòng chọn lại.']);
        }

        // Validate lẻ ghế
        if ($this->hasSingleSeatGap($showtimeId, $seatIds)) {
            return back()->withInput()->withErrors(['error' => 'Vị trí chọn không hợp lệ! Vui lòng không để trống duy nhất 1 ghế trống ở giữa hoặc ở đầu/cuối hàng.']);
        }

        // ⭐ STEP 1: Load hold session
        $holdSessionKey = "hold_session_{$tabToken}_{$showtimeId}";
        $holdSession = Cache::get($holdSessionKey);

        if (! $holdSession) {
            return back()->withErrors(['error' => 'Phiên giữ ghế không tồn tại. Vui lòng chọn ghế lại.']);
        }

        $sessionData = json_decode($holdSession, true);
        $expiresAt   = Carbon::parse($sessionData['expiresAt']);
        $startedAt   = Carbon::parse($sessionData['startedAt']);

        if ($expiresAt->isPast()) {
            Cache::forget($holdSessionKey);
            return back()->withErrors(['error' => 'Phiên giữ ghế đã hết hạn. Vui lòng chọn ghế lại.']);
        }

        // ⭐ STEP 2: Verify ALL seat cache entries belong to this tabToken
        foreach ($seatIds as $seatId) {
            $seatCacheKey = "seat_held_{$showtimeId}_{$seatId}";
            $holder = Cache::get($seatCacheKey);

            if ($holder !== $tabToken) {
                return back()->withErrors(['error' => 'Một hoặc nhiều ghế đã hết hạn giữ hoặc bị người khác chiếm. Vui lòng chọn lại.']);
            }
        }

        // ⭐ STEP 3: DB Transaction — lock SHOWTIME_SEATS rows (fixed inventory)
        DB::beginTransaction();
        try {
            // Lock showtime_seats rows — these ALWAYS exist (synced when page loads)
            $lockedSeats = ShowtimeSeat::with('seat')
                ->whereIn('id', $seatIds)
                ->where('showtime_id', $showtimeId)
                ->lockForUpdate()
                ->get();

            if ($lockedSeats->count() !== count($seatIds)) {
                throw new \Exception('Một số ghế không tồn tại.');
            }

            // Kiểm tra conflict: có booking ACTIVE nào giữ ghế này?
            $conflictExists = DB::table('booking_seats')
                ->join('bookings', 'bookings.id', '=', 'booking_seats.booking_id')
                ->where('bookings.showtime_id', $showtimeId)
                ->whereIn('booking_seats.showtime_seat_id', $seatIds)
                ->whereIn('bookings.status', ['PAID', 'PENDING', 'PENDING_PAYMENT', 'PENDING_CASH_PAYMENT'])
                ->where(function ($q) {
                    $q->whereNull('bookings.expired_at')
                      ->orWhere('bookings.expired_at', '>', now());
                })
                ->exists();

            if ($conflictExists) {
                throw new \Exception('Một hoặc nhiều ghế đã được người khác đặt. Vui lòng chọn lại.');
            }

            // Hủy booking PENDING cũ (nếu có) — cùng user, cùng showtime
            $oldBooking = Booking::where('user_id', $userId)
                ->where('showtime_id', $showtimeId)
                ->whereIn('status', ['PENDING', 'PENDING_PAYMENT'])
                ->first();
            if ($oldBooking) {
                $this->cancelAndRelease($oldBooking);
            }

            // Tính tier discount
            $totalSeatAmount = $lockedSeats->sum('price');
            $tierDiscountPercent = 0;
            $tierName = 'BRONZE';
            if ($userId) {
                $userMembership = \App\Models\UserMembership::with('level')->where('user_id', $userId)->first();
                $tierDiscountPercent = (float) ($userMembership?->level?->discount_percent ?? 0);
                $tierName = strtoupper($userMembership?->level?->name ?? 'BRONZE');
            }
            $tierDiscountAmount = (float) round(($totalSeatAmount * $tierDiscountPercent) / 100);

            // Tạo Booking — inherit hold session timestamps
            $booking = Booking::create([
                'booking_code'        => app(TicketService::class)->generateUniqueBookingCode(),
                'user_id'             => $userId,
                'showtime_id'         => $showtimeId,
                'status'              => 'PENDING',
                'payment_status'      => 'UNPAID',
                'total_ticket_amount' => $totalSeatAmount,
                'hold_started_at'     => $startedAt,    // ← FROM hold session
                'expired_at'          => $expiresAt,     // ← FROM hold session (KHÔNG reset)
            ]);

            // Insert BookingSeat
            foreach ($lockedSeats as $seat) {
                $row = $seat->seat->row_label ?? '';
                $seatType = $seat->seat->seat_type ?? 'STANDARD';
                if ($row === 'J' || $seatType === 'COUPLE') {
                    $seatType = 'COUPLE';
                } elseif ($seatType === 'DEMO') {
                    $seatType = 'DEMO';
                }

                DB::table('booking_seats')->insert([
                    'booking_id'       => $booking->id,
                    'showtime_seat_id' => $seat->id,
                    'seat_code'        => $seat->seat->seat_code ?? 'N/A',
                    'seat_type'        => $seatType,
                    'price'            => $seat->price ?? 80000,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }

            // Sync cache seat_held_ TTL = expiresAt
            foreach ($seatIds as $seatId) {
                Cache::put("seat_held_{$showtimeId}_{$seatId}", $tabToken, $expiresAt);
            }

            DB::commit();

            // Session update
            session(['pending_booking_id' => $booking->id]);
            session([
                'booking_tam' => [
                    'showtime_id' => $showtimeId,
                    'seats' => $seatIds,

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

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->to(\App\Helpers\TabAuthHelper::route('booking.seat', ['showtime_id' => $showtimeId]))
                ->with('error', $e->getMessage());
        }
    }

    // ==========================================
    // UC-CUS-09: CHỌN COMBO
    // ==========================================
    public function showCombo()
    {
        $bookingTam = session('booking_tam');

        // ⭐ EXPIRY GUARD: kiểm tra booking còn hạn không
        $bookingId = session('pending_booking_id');
        if (! $bookingId || ! $bookingTam) {
            return redirect()->route('home')->with('error', 'Phiên đặt vé không tồn tại.');
        }

        $booking = Booking::find($bookingId);
        if (! $booking || ! in_array($booking->status, ['PENDING', 'PENDING_PAYMENT'])
            || ($booking->expired_at && $booking->expired_at <= now())) {
            session()->forget(['pending_booking_id', 'booking_tam', 'pending_order_code']);
            $showtimeId = $booking->showtime_id ?? ($bookingTam['showtime_id'] ?? null);
            if ($showtimeId) {
                return redirect()->to(\App\Helpers\TabAuthHelper::route('booking.seat', ['showtime_id' => $showtimeId]))
                    ->with('error', 'Phiên giữ ghế đã hết hạn. Vui lòng chọn lại.');
            }
            return redirect()->route('home')->with('error', 'Phiên giữ ghế đã hết hạn.');
        }

        $showtime = null;
        if ($bookingTam && ! empty($bookingTam['showtime_id'])) {
            $showtime = Showtime::with(['movie', 'cinema', 'room'])
                ->find($bookingTam['showtime_id']);
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

        // Countdown data for view
        $expiresAt        = $booking->expired_at->toIso8601String();
        $serverTime       = now()->toIso8601String();
        $holdTotalSeconds = $this->holdMinutes() * 60;
        $warningSeconds   = config('booking.warning_seconds', 60);
        $seatPageUrl      = \App\Helpers\TabAuthHelper::route('booking.seat', ['showtime_id' => $bookingTam['showtime_id']]);

        return view('booking.combo', compact(
            'combos', 'bookingTam', 'showtime',
            'expiresAt', 'serverTime', 'holdTotalSeconds', 'warningSeconds', 'seatPageUrl'
        ));
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

        // ⭐ EXPIRY GUARD
        $bookingId = session('pending_booking_id');
        if (! $bookingId) {
            return redirect()->route('home')->with('error', 'Phiên đặt vé không tồn tại.');
        }

        $booking = Booking::find($bookingId);
        if (! $booking || ! in_array($booking->status, ['PENDING', 'PENDING_PAYMENT'])
            || ($booking->expired_at && $booking->expired_at <= now())) {
            session()->forget(['pending_booking_id', 'booking_tam', 'pending_order_code']);
            $showtimeId = $booking->showtime_id ?? ($bookingTam['showtime_id'] ?? null);
            if ($showtimeId) {
                return redirect()->to(\App\Helpers\TabAuthHelper::route('booking.seat', ['showtime_id' => $showtimeId]))
                    ->with('error', 'Phiên giữ ghế đã hết hạn. Vui lòng chọn lại.');
            }
            return redirect()->route('home')->with('error', 'Phiên giữ ghế đã hết hạn.');
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
        $discountAmount = $bookingTam['discount_amount'] ?? 0;

        $coinUsed = $bookingTam['coin_used'] ?? 0;
        $coinDiscountAmount = $bookingTam['coin_discount_amount'] ?? 0;

        $subtotal = $totalTicketPrice + $totalComboPrice;
        $afterDiscounts = max(0, $subtotal - $tierDiscountAmount - $discountAmount);
        $totalPrice = max(0, $afterDiscounts - $coinDiscountAmount);

        $coinService = app(CoinRedemptionService::class);
        $coinInfo = $coinService->calculateMaxRedeemable(Auth::id(), $afterDiscounts);

        // Countdown data
        $expiresAt        = $booking->expired_at->toIso8601String();
        $serverTime       = now()->toIso8601String();
        $holdTotalSeconds = $this->holdMinutes() * 60;
        $warningSeconds   = config('booking.warning_seconds', 60);
        $seatPageUrl      = \App\Helpers\TabAuthHelper::route('booking.seat', ['showtime_id' => $showtime_id]);

        return view('booking.confirm', compact(
            'showtime', 'showtime_id', 'seats', 'totalTicketPrice', 'combos',
            'totalComboPrice', 'tierDiscountAmount', 'tierName', 'tierPercent',
            'discountAmount', 'totalPrice',
            'coinUsed', 'coinDiscountAmount', 'coinInfo',
            'expiresAt', 'serverTime', 'holdTotalSeconds', 'warningSeconds', 'seatPageUrl'
        ));
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'customer_name'  => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_email' => 'required|email|max:255',
            'payment_method' => 'required|string',
        ]);

        $bookingTam = session('booking_tam');
        if (! $bookingTam) {
            return redirect()->route('home');
        }

        $bookingId = session('pending_booking_id');
        if (! $bookingId) {
            return redirect()->route('home')->with('error', 'Phiên đặt vé không hợp lệ.');
        }

        $booking = Booking::with('bookingSeats')->find($bookingId);

        // Guard expiry
        if (! $booking || ! in_array($booking->status, ['PENDING', 'PENDING_PAYMENT'])
            || ($booking->expired_at && $booking->expired_at <= now())) {
            session()->forget(['pending_booking_id', 'booking_tam', 'pending_order_code']);
            return redirect()->to(\App\Helpers\TabAuthHelper::route('booking.seat', ['showtime_id' => $bookingTam['showtime_id'] ?? null]))
                ->with('error', 'Phiên giữ ghế đã hết hạn. Vui lòng chọn lại.');
        }

        // Nếu đã có order cũ (do user back lại thanh toán bằng QR, v.v.), cancel order cũ (không cancel booking)
        $existingOrderCode = session('pending_order_code');
        if ($existingOrderCode) {
            $existingOrder = SepayOrder::where('order_code', $existingOrderCode)->first();
            if ($existingOrder) {
                if ($existingOrder->status === 'paid' || $booking->status === 'PAID') {
                    return redirect()->route('booking.bill', ['orderCode' => $existingOrderCode]);
                }
                if ($existingOrder->status === 'pending') {
                    $existingOrder->update(['status' => 'expired']);
                }
                session()->forget('pending_order_code');
            }
        }

        DB::beginTransaction();
        try {
            $showtimeId = $booking->showtime_id;
            $showtime = Showtime::findOrFail($showtimeId);

            // Các khoản tiền
            $totalTicketAmount  = $booking->total_ticket_amount;
            $totalComboAmount   = $bookingTam['total_combo_amount'] ?? 0;
            $tierDiscountAmount = $bookingTam['tier_discount_amount'] ?? 0;
            $voucherDiscount    = $bookingTam['discount_amount'] ?? 0;
            $coinUsed           = $bookingTam['coin_used'] ?? 0;
            $coinDiscountVND    = $bookingTam['coin_discount_amount'] ?? 0;

            $totalDiscount = $voucherDiscount + $coinDiscountVND;
            $finalAmount = $totalTicketAmount + $totalComboAmount - $totalDiscount;
            if ($finalAmount < 0) {
                $finalAmount = 0;
            }

            // Update booking (vì đã tạo ở submitSeats)
            $booking->update([
                'customer_name'       => $request->input('customer_name'),
                'customer_email'      => $request->input('customer_email'),
                'customer_phone'      => $request->input('customer_phone'),
                'total_combo_amount'  => $totalComboAmount,
                'discount_amount'     => $totalDiscount,
                'final_amount'        => $finalAmount,
                'status'              => 'PENDING_PAYMENT',
            ]);

            // Clear old combos if any (trường hợp back lại đổi combo)
            BookingCombo::where('booking_id', $booking->id)->delete();

            // Insert Combos
            foreach (($bookingTam['combos'] ?? []) as $comboItem) {
                BookingCombo::create([
                    'booking_id'  => $booking->id,
                    'combo_id'    => $comboItem['combo_id'],
                    'quantity'    => $comboItem['quantity'],
                    'unit_price'  => $comboItem['unit_price'],
                    'total_price' => $comboItem['total_price'],
                ]);
            }

            // Chuẩn bị metadata cho SepayOrder
            $seatDetails = [];
            foreach ($booking->bookingSeats as $s) {
                $seatType = 'standard';
                $seatKind = strtoupper($s->seat_type ?? 'STANDARD');
                $code = strtoupper($s->seat_code ?? '');
                
                if (str_contains($code, 'J') || str_contains($code, 'SW') || $seatKind === 'COUPLE' || $seatKind === 'SWEETBOX') {
                    $seatType = 'sweetbox';
                } elseif ($seatKind === 'VIP') {
                    $seatType = 'vip';
                } elseif ($seatKind === 'DEMO') {
                    $seatType = 'demo';
                }

                $seatDetails[] = [
                    'code'  => $s->seat_code ?? 'N/A',
                    'type'  => $seatType,
                    'price' => (int) $s->price,
                ];
            }

            $comboDetails = [];
            foreach (($bookingTam['combos'] ?? []) as $comboItem) {
                $comboDetails[] = [
                    'name'        => $comboItem['name'],
                    'quantity'    => $comboItem['quantity'],
                    'unit_price'  => $comboItem['unit_price'],
                    'total_price' => $comboItem['total_price'],
                ];
            }

            // Tạo SepayOrder
            $sepayOrder = SepayOrder::create([
                'order_code'   => $booking->booking_code,
                'booking_id'   => $booking->id,
                'package_id'   => 'booking',
                'package_name' => 'Vé xem phim',
                'amount'       => $finalAmount,
                'status'       => 'pending',
                'metadata'     => [
                    'movie_title'          => $showtime->movie->title ?? '',
                    'room'                 => $showtime->room->name ?? '',
                    'showtime'             => Carbon::parse($showtime->start_time)->format('H:i').' - '.Carbon::parse($showtime->end_time)->format('H:i'),
                    'show_date'            => Carbon::parse($showtime->start_time)->format('d/m/Y'),
                    'format'               => '2D',
                    'seats'                => $seatDetails,
                    'seat_count'           => count($seatDetails),
                    'combos'               => $comboDetails,
                    'showtime_id'          => $showtimeId,
                    'customer_email'       => $booking->customer_email,
                    'customer_name'        => $booking->customer_name,
                    'customer_phone'       => $booking->customer_phone,
                    'coin_used'            => $coinUsed,
                    'coin_discount_amount' => $coinDiscountVND,
                    'voucher_code'         => $bookingTam['voucher_code'] ?? null,
                    'discount_amount'      => $voucherDiscount,
                ],
            ]);

            DB::commit();

            session()->put('pending_order_code', $booking->booking_code);

            if ($finalAmount <= 0) {
                session()->forget(['booking_tam', 'pending_order_code', 'pending_booking_id']);
                return $this->handleZeroAmountBooking($booking, $coinUsed);
            }

            return redirect()->route('booking.payment', ['orderCode' => $booking->booking_code]);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->to(\App\Helpers\TabAuthHelper::route('booking.seat', ['showtime_id' => $bookingTam['showtime_id'] ?? null]))
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

        // Hủy booking bằng helper (atomic + idempotent)
        $booking = $order->booking;
        if ($booking) {
            $this->cancelAndRelease($booking);
        }

        // Hủy sepay order
        if ($order->status === 'pending') {
            $order->update(['status' => 'expired']);
        }

        // Xóa session booking tạm
        session()->forget(['booking_tam', 'pending_order_code', 'pending_booking_id']);

        // Redirect về đúng trang chọn ghế của suất chiếu
        if ($showtimeId) {
            return redirect()->to(\App\Helpers\TabAuthHelper::route('booking.seat', ['showtime_id' => $showtimeId]))
                ->with('success', 'Đã hủy đơn hàng. Bạn có thể chọn ghế mới.');
        }

        return redirect()->route('home')
            ->with('success', 'Đã hủy đơn hàng thành công.');
    }

    // ==========================================
    // QUICK CANCEL (sendBeacon từ browser back)
    // ==========================================

    /**
     * Cancel booking via sendBeacon khi user bấm browser Back.
     * Xác thực bằng HMAC holdToken — không phụ thuộc session.
     */
    public function quickCancel(Request $request)
    {
        $holdToken   = $request->input('hold_token');
        $showtimeId  = $request->input('showtime_id');
        $tabToken    = $request->input('tab_token');
        $expiresAtRaw = $request->input('expires_at');

        // ⭐ Verify HMAC — đảm bảo request hợp lệ
        $expectedToken = hash_hmac('sha256',
            "{$tabToken}:{$showtimeId}:{$expiresAtRaw}",
            config('app.key')
        );

        if (! hash_equals($expectedToken, $holdToken ?? '')) {
            return response('', 403);
        }

        // Cancel hold session + per-seat cache locks
        $holdSessionKey = "hold_session_{$tabToken}_{$showtimeId}";
        $holdSession = Cache::get($holdSessionKey);

        if ($holdSession) {
            $sessionData = json_decode($holdSession, true);

            foreach ($sessionData['seats'] as $seatId) {
                $seatCacheKey = "seat_held_{$showtimeId}_{$seatId}";
                $currentHolder = Cache::get($seatCacheKey);
                if ($currentHolder === $tabToken) {
                    Cache::forget($seatCacheKey);
                }
            }

            Cache::forget($holdSessionKey);
        }

        // Cancel booking nếu đã tồn tại (đã qua submitSeats)
        $userId = \App\Models\TabToken::where('token', $tabToken)->value('user_id');
        if ($userId) {
            $booking = Booking::where('user_id', $userId)
                ->where('showtime_id', $showtimeId)
                ->whereIn('status', ['PENDING', 'PENDING_PAYMENT'])
                ->where('expired_at', \Carbon\Carbon::parse($expiresAtRaw))
                ->first();

            if ($booking) {
                $this->cancelAndRelease($booking);

                // Cancel SepayOrder nếu có
                SepayOrder::where('booking_id', $booking->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'expired']);
            }
        }

        return response('', 204);
    }

    // ==========================================
    // CANCEL AND RELEASE HELPER (private)
    // ==========================================

    /**
     * Atomic + idempotent cancel booking + release seats.
     * Dùng conditional update — CANCELLED/PAID/EXPIRED → no-op.
     */
    private function cancelAndRelease(Booking $booking): void
    {
        // Atomic conditional update — KHÔNG cancel PAID/EXPIRED
        $affected = Booking::where('id', $booking->id)
            ->whereIn('status', ['PENDING', 'PENDING_PAYMENT', 'PENDING_CASH_PAYMENT'])
            ->update([
                'status'         => 'CANCELLED',
                'payment_status' => $booking->payment_status === 'PAID' ? 'REFUNDED' : 'FAILED',
            ]);

        if ($affected === 0) return; // Idempotent: đã bị thay đổi

        // Cancellation record
        BookingCancellation::updateOrCreate(
            ['booking_id' => $booking->id, 'type' => 'CANCELLATION'],
            [
                'type'        => 'CANCELLATION',
                'canceled_by' => Auth::id() ?? $booking->user_id,
                'reason'      => 'Abandon booking flow (browser back / cancel / timeout).',
            ]
        );

        // Hoàn coin
        $redeemTx = PointTransaction::where('booking_id', $booking->id)
            ->where('type', 'REDEEM')->first();
        if ($redeemTx && $booking->user_id) {
            app(CoinRedemptionService::class)->refundCoins(
                $booking->user_id, abs($redeemTx->points), $booking->id
            );
        }

        // Release seats từ cache
        $seatIds = DB::table('booking_seats')
            ->where('booking_id', $booking->id)->pluck('showtime_seat_id');
        foreach ($seatIds as $seatId) {
            Cache::forget("seat_held_{$booking->showtime_id}_{$seatId}");
        }
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
