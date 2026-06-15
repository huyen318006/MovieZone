<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Showtime;
use App\Models\Seat;
use App\Models\Booking;
use App\Models\Combo;
use Illuminate\Support\Str;
use App\Models\ShowtimeSeat;
use App\Models\BookingCombo;
use App\Models\SepayOrder;

class BookingController extends Controller
{
    // ==========================================
    // UC-CUS-08: CHỌN GHẾ
    // ==========================================
    public function showSeats($showtime_id)
    {
        $showtime = Showtime::with(['movie', 'cinema', 'room', 'showtimeSeats.seat'])->findOrFail($showtime_id);

        if (now()->greaterThan($showtime->start_time)) {
            return redirect()->back()->with('error', 'Suất chiếu này đã bắt đầu.');
        }

        $seats = $showtime->showtimeSeats;
        
        // 1. Phân loại ghế từ DB vào ma trận để dễ xử lý
        $allSeatsMatrix = [];
        foreach ($seats as $seat) {
            $row = optional($seat->seat)->row_label;
            $num = optional($seat->seat)->seat_number;

            // Kiểm tra trạng thái SOLD (BR05, E1)
            $isSold = DB::table('booking_seats')
                ->join('bookings', 'bookings.id', '=', 'booking_seats.booking_id')
                ->where('booking_seats.showtime_seat_id', $seat->id)
                ->where('bookings.showtime_id', $showtime_id)
                ->whereIn('bookings.status', ['PAID', 'PENDING_PAYMENT', 'PENDING_CASH_PAYMENT'])
                ->exists();

            if ($isSold) {
                $seat->display_status = 'SOLD';
            } else {
                // Kiểm tra trạng thái HELD trong Cache (E2)
                $heldBy = Cache::get('seat_held_' . $showtime_id . '_' . $seat->id);
                if ($heldBy) {
                    $seat->display_status = ($heldBy == Auth::id()) ? 'HELD_BY_ME' : 'HELD';
                } else {
                    // Trạng thái từ DB (AVAILABLE hoặc BLOCKED - BR06, E3)
                    $seat->display_status = $seat->status ?? 'AVAILABLE';
                }
            }

            $allSeatsMatrix[$row][$num] = $seat;
        }

        // 2. Xây dựng mảng UI chuẩn bị sẵn cho View (Tách logic khỏi Blade)
        $seatMap = [];
        
        // CÁC HÀNG A -> I
        foreach (range('A', 'I') as $row) {
            $seatMap[$row] = [];
            for ($i = 1; $i <= 10; $i++) {
                $dbSeat = $allSeatsMatrix[$row][$i] ?? null;
                if ($dbSeat) {
                    $seatMap[$row][] = [
                        'id' => $dbSeat->id,
                        'code' => $dbSeat->seat->seat_code ?? ($row . str_pad($i, 2, '0', STR_PAD_LEFT)),
                        'price' => (int) $dbSeat->price, // LẤY CHÍNH XÁC GIÁ TỪ DB
                        'status' => $dbSeat->display_status,
                        'type' => ($row === 'F') ? 'vip' : 'standard', // ÉP CỨNG CHỈ HÀNG F LÀ VIP
                        'label' => $i,
                        'is_aisle' => ($i == 5) // Đường luồng
                    ];
                }
            }
        }

        // HÀNG J: GHẾ SWEETBOX (Ghép 2 ghế thành 1)
        $seatMap['J'] = [];
        for ($i = 1; $i <= 10; $i += 2) {
            $left = $allSeatsMatrix['J'][$i] ?? null;
            $right = $allSeatsMatrix['J'][$i + 1] ?? null;

            if ($left || $right) {
                $statusL = $left->display_status ?? 'AVAILABLE';
                $statusR = $right->display_status ?? 'AVAILABLE';

                // Gộp trạng thái 2 ghế
                $combinedStatus = 'AVAILABLE';
                if (in_array('SOLD', [$statusL, $statusR])) $combinedStatus = 'SOLD';
                elseif (in_array('BLOCKED', [$statusL, $statusR])) $combinedStatus = 'BLOCKED';
                elseif (in_array('HELD', [$statusL, $statusR])) $combinedStatus = 'HELD';
                elseif ($statusL === 'HELD_BY_ME' || $statusR === 'HELD_BY_ME') $combinedStatus = 'HELD_BY_ME';

                $combinedPrice = ($left ? (int)$left->price : 0) + ($right ? (int)$right->price : 0);
                
                $ids = [];
                if ($left) $ids[] = $left->id;
                if ($right) $ids[] = $right->id;

                $seatMap['J'][] = [
                    'id' => implode(',', $ids),
                    'code' => 'J' . str_pad($i, 2, '0', STR_PAD_LEFT) . '-' . str_pad($i + 1, 2, '0', STR_PAD_LEFT),
                    'price' => $combinedPrice,
                    'status' => $combinedStatus,
                    'type' => 'sweetbox',
                    'label' => 'J' . $i . '-' . ($i + 1),
                    'is_aisle' => false
                ];
            }
        }


        // TÍNH THỜI GIAN CÒN LẠI
        $masterTimerKey = 'hold_timer_' . Auth::id() . '_' . $showtime_id;
        $secondsLeft = 300; // Mặc định 5 phút
        if (Cache::has($masterTimerKey)) {
            $secondsLeft = max(0, Cache::get($masterTimerKey) - now()->timestamp);
        }
        $masterTimerKey = 'hold_timer_' . Auth::id() . '_' . $showtime_id;

        $secondsLeft = 300;

        if (Cache::has($masterTimerKey)) {

            $secondsLeft = max(
                0,
                Cache::get($masterTimerKey) - now()->timestamp
            );

        }
        return view('booking.seat',compact('showtime','seatMap','secondsLeft'));
    }

    // AJAX API xử lý giữ ghế Realtime (UC-08 bước 5)
    public function holdSeat(Request $request)
    {
        $showtimeId = $request->showtime_id;
        $seatId = $request->seat_id; 
        $action = $request->action;
        $cacheKey = 'seat_held_' . $showtimeId . '_' . $seatId;

        if ($action === 'hold') {
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
            $masterTimerKey = 'hold_timer_' . Auth::id() . '_' . $showtimeId;

            // Nếu chưa có timer tổng
            // (nghĩa là đây là ghế đầu tiên user chọn)
            if (!Cache::has($masterTimerKey)) {

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
                $expireAt = \Carbon\Carbon::createFromTimestamp(
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
            'seats' => 'required|array|min:1' // BR01: Ít nhất 1 ghế
        ]);

        $seats = ShowtimeSeat::whereIn('id', $request->seats)->get();
        $totalSeatAmount = $seats->sum('price');

        session([
        'booking_tam' => [
            'showtime_id'       => $request->showtime_id,
            'seats'             => $request->seats,
            'customer_email'    => $request->input('customer_email', ''),

            // Ticket
            'total_seat_amount' => $totalSeatAmount,

            // Combo
            'combos'            => [],
            'total_combo_amount'=> 0,

            // Voucher
            'voucher_id'        => null,
            'voucher_code'      => null,
            'discount_amount'   => 0,

            // Total
            'subtotal'          => $totalSeatAmount,
            'total'             => $totalSeatAmount,
        ]
    ]);


        return redirect()->route('booking.combo'); // Chuyển sang chọn combo sau khi chọn ghế thay vì confirm ngay
    }

    // ==========================================
    // UC-CUS-09: CHỌN COMBO
    // ==========================================
    public function showCombo()
    {
        $combos = Combo::where('status', 'ACTIVE')->get();
        return view('booking.combo', compact('combos'));
    }
public function saveCombo(Request $request)
{
    $bookingTam = session()->get('booking_tam');

    if (!$bookingTam) {
        return redirect()->route('home')
            ->with('error', 'Phiên đặt vé không tồn tại.');
    }

    $selectedCombos = [];
    $comboTotal = 0;

    foreach ($request->input('combos', []) as $comboId => $item) {
        $quantity = (int) ($item['quantity'] ?? 0);

        if ($quantity > 0) {
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
            }
        }
    }

    // 🔥 UPDATE SESSION ĐÚNG CÁCH (KHÔNG GHI ĐÈ LUNG TUNG)
    $bookingTam['combos'] = $selectedCombos;
    $bookingTam['total_combo_amount'] = $comboTotal;

    // 🔥 BẮT BUỘC: lấy lại seat total + discount từ session hiện tại
    $seatTotal = $bookingTam['total_seat_amount'] ?? 0;
    $discount  = $bookingTam['discount_amount'] ?? 0;

    $bookingTam['subtotal'] = $seatTotal + $comboTotal;
    $bookingTam['total'] = max(0, $bookingTam['subtotal'] - $discount);

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
        if (!$bookingTam) return redirect()->route('home');

        $showtime = Showtime::with(['movie', 'cinema', 'room'])->findOrFail($bookingTam['showtime_id']);
        $seats = ShowtimeSeat::with('seat')
            ->whereIn('id', $bookingTam['seats'])
            ->get();

        $totalTicketPrice = $seats->sum('price');
        $combos = $bookingTam['combos'] ?? [];
        $totalComboPrice = $bookingTam['total_combo_amount'] ?? 0;
        $discountAmount = $bookingTam['discount_amount'] ?? 0;
        $totalPrice = $totalTicketPrice + $totalComboPrice - $discountAmount;
        if ($totalPrice < 0) $totalPrice = 0;

        return view('booking.confirm', compact(
            'showtime', 'seats', 'totalTicketPrice', 'combos',
            'totalComboPrice', 'discountAmount', 'totalPrice'
        ));
    }

    public function checkout(Request $request)
    {
        $bookingTam = session('booking_tam');
        if (!$bookingTam) return redirect()->route('home');

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
                $cacheKey = 'seat_held_' . $showtimeId . '_' . $seatId;
                $heldBy = Cache::get($cacheKey);
                
                if (!$heldBy || $heldBy != Auth::id()) {
                    throw new \Exception('Ghế không còn khả dụng do hết thời gian giữ (5 phút) hoặc đã bị mua.');
                }
            }

            // Tính toán tổng tiền vé + combo + voucher
            $seats = ShowtimeSeat::whereIn('id', $seatIds)->get();

            $totalTicketAmount = $seats->sum('price');
            $totalComboAmount = $bookingTam['total_combo_amount'] ?? 0;
            $discountAmount = $bookingTam['discount_amount'] ?? 0;

            $finalAmount = $totalTicketAmount + $totalComboAmount - $discountAmount;

            if ($finalAmount < 0) {
                $finalAmount = 0;
            }

            // BR03: Mã định danh duy nhất
            $bookingCode = strtoupper('BK' . Str::random(8));

            // BR07: Trạng thái dựa vào phương thức thanh toán
            $paymentMethod = $request->input('payment_method', 'ONLINE');
            $status = ($paymentMethod == 'CASH') ? 'PENDING_CASH_PAYMENT' : 'PENDING_PAYMENT';

            // Luồng chính: Tạo booking mới
            $booking = Booking::create([
                'booking_code' => $bookingCode,
                'user_id' => Auth::id(),
                'showtime_id' => $showtimeId,
                'total_ticket_amount' => $totalTicketAmount,
                'total_combo_amount' => $totalComboAmount,
                'discount_amount' => $discountAmount,
                'final_amount' => $finalAmount,
                'status' => 'PENDING',
                'payment_status' => 'UNPAID',
                'expired_at' => now()->addMinutes(5),
            ]);

            // Lưu danh sách ghế vào bảng trung gian
            foreach ($seats as $seat) {
                $row = $seat->seat->row_label ?? '';
                $seatType = 'STANDARD';
                if ($row === 'F') $seatType = 'VIP';
                if ($row === 'J') $seatType = 'SWEETBOX';

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
                Cache::forget('seat_held_' . $showtimeId . '_' . $seat->id);
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
                if ($row === 'F') $seatType = 'vip';
                if ($row === 'J') $seatType = 'sweetbox';

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

            // Lưu email khách hàng vào metadata
            $customerEmail = $bookingTam['customer_email'] ?? (Auth::user()->email ?? '');

            $sepayOrder = SepayOrder::create([
                'order_code'   => $bookingCode,
                'booking_id'   => $booking->id,
                'package_id'   => 'booking',
                'package_name' => 'Vé xem phim',
                'amount'       => $finalAmount,
                'status'       => 'pending',
                'metadata'     => [
                    'movie_title'    => $showtime->movie->title ?? '',
                    'cinema'         => $showtime->cinema->name ?? '',
                    'room'           => $showtime->room->name ?? '',
                    'showtime'       => \Carbon\Carbon::parse($showtime->start_time)->format('H:i') . ' - ' . \Carbon\Carbon::parse($showtime->end_time)->format('H:i'),
                    'show_date'      => \Carbon\Carbon::parse($showtime->start_time)->format('d/m/Y'),
                    'format'         => '2D',
                    'seats'          => $seatDetails,
                    'seat_count'     => count($seatDetails),
                    'combos'         => $comboDetails,
                    'showtime_id'    => $showtimeId,
                    'customer_email' => $customerEmail,
                ],
            ]);

            DB::commit();
            session()->forget('booking_tam');

            return redirect()->route('booking.payment', ['orderCode' => $bookingCode]);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('booking.seat', ['showtime_id' => $showtimeId])
                             ->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }
}