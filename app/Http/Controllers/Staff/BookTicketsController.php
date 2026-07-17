<?php

namespace App\Http\Controllers\staff;

use App\Http\Controllers\Controller;
use App\Models\Combo;
use Illuminate\Http\Request;
use App\Models\Movie;
use App\Models\Product;
use App\Services\StaffBookingService as ServicesStaffBookingService;
use App\Services\StaffBookingService\staffBooking;
use StaffBookingService;


class BookTicketsController extends Controller
{
    public function __construct(private ServicesStaffBookingService $staffBookingService) {}
    // hàm lấy ra film của hệ thống
    public function index()
    {
        $movies = $this->staffBookingService->getMovies();
        return view('staff.sell-tickets', compact('movies'));
    }

    // hàm lấy ra ghế của suất chiếu đó + thông tin phim, phòng
    public function sell_seat($id)
    {
        $data = $this->staffBookingService->sell_seat($id);

        $showtime = $data['showtime'];  // có sẵn ->movie (tên phim, poster...) và ->room (tên phòng)
        $seatMap  = $data['seatMap'];   // sơ đồ ghế theo hàng

        return view('staff.sell-tickets-seats', compact('showtime', 'seatMap'));
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
                'seats'       => $request->input('seats'),
            ]
        ]);
        // dd(session('booking'));

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
}
