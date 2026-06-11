<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Showtime;
use App\Models\Seat;
use App\Models\Booking;
use Illuminate\Support\Str;
use App\Models\ShowtimeSeat;


class BookingController extends Controller
{
    // ==========================================
    // UC-CUS-08: CHỌN GHẾ
    // ==========================================
   public function showSeats($showtime_id)
{
    $showtime = Showtime::with([
        'movie',
        'cinema',
        'room',
        'showtimeSeats.seat'
    ])->findOrFail($showtime_id);

    if (now()->greaterThan($showtime->start_time)) {
        return redirect()->back()
            ->with('error', 'Suất chiếu này đã bắt đầu.');
    }

    $seats = $showtime->showtimeSeats;

    foreach ($seats as $seat) {

        $seat->seat_name =
            $seat->seat->row_label .
            $seat->seat->seat_number;

        $isSold = DB::table('booking_seats')
            ->join(
                'bookings',
                'bookings.id',
                '=',
                'booking_seats.booking_id'
            )
            ->where(
                'booking_seats.showtime_seat_id',
                $seat->id
            )
            ->where(
                'bookings.showtime_id',
                $showtime_id
            )
            ->whereIn('bookings.status', [
                'PAID',
                'PENDING_PAYMENT',
                'PENDING_CASH_PAYMENT'
            ])
            ->exists();

        if ($isSold) {
            $seat->display_status = 'SOLD';
        } else {

            $heldBy = Cache::get(
                'seat_held_' .
                $showtime_id .
                '_' .
                $seat->id
            );

            if ($heldBy) {

                $seat->display_status =
                    $heldBy == Auth::id()
                    ? 'HELD_BY_ME'
                    : 'HELD';

            } else {

                $seat->display_status =
                    $seat->status;

            }
        }
    }

    return view(
        'booking.seat',
        compact('showtime', 'seats')
    );
}
    // AJAX API xử lý giữ ghế Realtime (UC-08 bước 5)
    public function holdSeat(Request $request)
    {
    $showtimeId = $request->showtime_id;
    $seatId = $request->seat_id; // Đây là ID của showtime_seat
    $action = $request->action;
    $cacheKey = 'seat_held_' . $showtimeId . '_' . $seatId;

    if ($action === 'hold') {
        // Sửa lại truy vấn này:
        $isSold = DB::table('booking_seats')
            ->join('bookings', 'bookings.id', '=', 'booking_seats.booking_id')
            ->where('booking_seats.showtime_seat_id', $seatId) // Chỉ dùng cột này
            ->where('bookings.showtime_id', $showtimeId)
            ->whereIn('bookings.status', ['PAID', 'PENDING_CASH_PAYMENT', 'PENDING_PAYMENT'])
            ->exists();

        if ($isSold) {
            return response()->json(['success' => false, 'error_type' => 'SOLD', 'message' => 'E1: Ghế đã được bán.']);
        }

        // Kiểm tra Cache
        $heldBy = Cache::get($cacheKey);
        if ($heldBy && $heldBy != Auth::id()) {
            return response()->json(['success' => false, 'error_type' => 'HELD', 'message' => 'E2: Ghế đang được người khác giữ.']);
        }

        Cache::put($cacheKey, Auth::id(), now()->addMinutes(5));
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

        session(['booking_tam' => [
            'showtime_id' => $request->showtime_id,
            'seats' => $request->seats
        ]]);

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

        $totalPrice = $seats->sum('price');

        return view('booking.confirm', compact('showtime', 'seats', 'totalPrice'));
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

            // E1, E4: Kiểm tra lại toàn bộ ghế trước khi ghi DB (Hết 5 phút giữ)
            foreach ($seatIds as $seatId) {
                $cacheKey = 'seat_held_' . $showtimeId . '_' . $seatId;
                $heldBy = Cache::get($cacheKey);
                
                if (!$heldBy || $heldBy != Auth::id()) {
                    throw new \Exception('Ghế không còn khả dụng do hết thời gian giữ (5 phút) hoặc đã bị mua.');
                }
            }

            $seats = ShowtimeSeat::whereIn('id', $seatIds)->get();

            $totalAmount = $seats->sum('price');

            // BR03: Mã định danh duy nhất
            $bookingCode = strtoupper('BK' . Str::random(8));

            // BR07: Trạng thái dựa vào phương thức chọn
            $paymentMethod = $request->input('payment_method', 'ONLINE');
            $status = ($paymentMethod == 'CASH') ? 'PENDING_CASH_PAYMENT' : 'PENDING_PAYMENT';

            // Luồng chính: Tạo booking mới
            $booking = Booking::create([
                'user_id' => Auth::id(),
                'showtime_id' => $showtimeId,
                'booking_code' => $bookingCode,
                'total_amount' => $totalAmount,
                'status' => $status, 
            ]);

            // Lưu danh sách ghế vào bảng trung gian
            foreach ($seats as $seat) {
                DB::table('booking_seats')->insert([
                    'booking_id' => $booking->id,
                    'showtime_seat_id' => $seat->id,
                    'price' => $seat->price ?? 80000
                ]);
                
                // Giải phóng ghế khỏi Cache sau khi lưu DB thành công
                Cache::forget('seat_held_' . $showtimeId . '_' . $seat->id);
            }

           DB::commit();
            session()->forget('booking_tam');

            // SỬA LỖI: Route này nằm trong prefix 'sepay', không phải 'booking'
            return redirect()->route('sepay.payment', ['orderCode' => $booking->booking_code]);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('booking.seat', ['showtime_id' => $showtimeId])
                             ->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }
}