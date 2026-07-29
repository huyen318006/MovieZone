<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingCombo;
use App\Models\BookingProduct;
use App\Models\Combo;
use App\Models\Movie;
use App\Models\Product;
use App\Models\Seat;
use App\Models\SepayOrder;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StaffBookingService
{
    public function __construct() {}

    // Lấy danh sách phim có suất chiếu sắp tới, kèm danh sách suất chiếu
    public function getMovies($keyword)
    {

        $time_now = Carbon::now();

        //kiểm tra keyword
        if(!empty($keyword)){
            //truy vấn
            $showtime = DB::table('showtimes')->leftJoin('movies', 'showtimes.movie_id', '=', 'movies.id')
                ->leftJoin('rooms', 'showtimes.room_id', '=', 'rooms.id')
                ->select(
                    'showtimes.id',
                    'movies.id as movie_id',           // ← sửa ở đây
                    'movies.title as movie_title',
                    'movies.poster_url as movie_poster',
                    'movies.duration_minutes as movie_duration',


                    'showtimes.room_id',
                    'showtimes.start_time',
                    'showtimes.end_time',
                    'showtimes.status',

                    //
                    'rooms.name as room_name',


                )
                ->where('showtimes.start_time', '>=', $time_now)   // chỉ lấy suất chiếu chưa chiếu
                ->where('showtimes.status', 'OPEN')
                ->where('movies.title', 'like', '%' . $keyword . '%') // lọc theo keyword
                ->orderBy('movies.title')
                ->orderBy('showtimes.start_time')
                ->get();
        }else{
        //truy vấn
        $showtime = DB::table('showtimes')->leftJoin('movies', 'showtimes.movie_id', '=', 'movies.id')
        ->leftJoin('rooms', 'showtimes.room_id', '=', 'rooms.id')
            ->select(
                'showtimes.id',
                'movies.id as movie_id',           // ← sửa ở đây
                'movies.title as movie_title',
                'movies.poster_url as movie_poster',
                'movies.duration_minutes as movie_duration',


                'showtimes.room_id',
                'showtimes.start_time',
                'showtimes.end_time',
                'showtimes.status',

                //
                'rooms.name as room_name',


            )
            ->where('showtimes.start_time', '>=', $time_now)   // chỉ lấy suất chiếu chưa chiếu
            ->where('showtimes.status', 'OPEN')
            ->orderBy('movies.title')
            ->orderBy('showtimes.start_time')
            ->get();
        }


        //gộp movie ==
        $movie = [];
        foreach ($showtime as $row) {
            //lấy id  của film hiện tại
            $movie_id = $row->movie_id;
            // kiểm tra xem id film đó có tồn tại trong mảng rỗng tổng hợp kia không
            if (!isset($movie[$movie_id])) {
                $movie[$movie_id] = [
                    'movie_id' => $row->movie_id,
                    'title' => $row->movie_title,
                    'duration' => $row->movie_duration,


                    'poster' => $row->movie_poster,

                    'showtimes' => [], //tạo khung suất chiếu rỗng
                ];
            };

            $startTimeCarbon = Carbon::parse($row->start_time);
            $endTimeCarbon = Carbon::parse($row->end_time);

            //sau khi đã có thì thêm suất chiếu vào
            $movie[$movie_id]['showtimes'][] = [
                'showtime_id' => $row->id,
                'room_id' => $row->room_id,
                'room_name' => $row->room_name,
                'start_time' => $startTimeCarbon->format('H:i'),
                'end_time' => $endTimeCarbon->format('H:i'),
                'show_date' => $startTimeCarbon->format('d/m/Y'),
                'show_date_short' => $startTimeCarbon->format('D, d/m'),
                'status' => $row->status,

            ];
        }
        return $movie;
    }

    // hàm lấy ra ghế của phòng đó (dựa theo BookingController::showSeats)
    public function sell_seat($id)
    {
        // Bước 1: Lấy thông tin suất chiếu kèm theo phim + phòng
        $showtime = Showtime::with(['movie', 'room'])->findOrFail($id);

        // Bước 2: Đồng bộ ghế từ bảng seats → showtime_seats
        // (đảm bảo mọi ghế của phòng đều có record trong showtime_seats)
        $roomSeats = Seat::where('room_id', $showtime->room_id)
            ->whereNull('deleted_at')
            ->get();

        foreach ($roomSeats as $seat) {
            ShowtimeSeat::firstOrCreate(
                [
                    'showtime_id' => $showtime->id,
                    'seat_id'     => $seat->id,
                ],
                [
                    'price'  => $seat->price ?? 90000,
                    'status' => 'AVAILABLE',
                ]
            );
        }

        // Bước 3: Load lại showtime_seats sau khi sync
        $showtime->load('showtimeSeats.seat');

        // Map showtime_seats theo seat_id để lookup nhanh
        $showtimeSeatsBySeatId = $showtime->showtimeSeats->keyBy('seat_id');

        // Bước 4: Build seatMap theo đúng kiểu admin (group theo row_label + orderBy seat_number)
        // Admin: $seats = Seat::where(...)->orderBy('row_label')->orderBy('seat_number')->get();
        //       $seatsGrouped = $seats->groupBy('row_label');

        $seatMap = [];

        //hiểu  đơn giản đoạn này chỉ là  dùng sortBy để sắp xếp theo ưu tiên bằng
        // cái $s là từng phần tử trong mảng $roomSeats, và sắp xếp theo row_label trước, sau đó mới đến seat_number.
        //khi chạy sortBy nó giống kiểu foreach ($roomSeat as $value) thì  cái $s chính là cái value đó
        /* còn cái fn là 1 hàm ẩn danh  kiểu:
                  function ($s) {
                        return $s->row_label ?? '';
                      }
        */ // thì cái => ra chính là cái return đó
        $roomSeats = $roomSeats->sortBy([
            //tiếp theo là tìm hiểu vì sao ưu tiên nhưng lại truyền 2 cái kiểm tra ưu tiên thì nó xếp kiểu gì?
             /* ở đây theo thứ tự ví dụ: cái đầu ta có Hàng ghế(label) 'A,B,C,D'
             lúc này cùng 1 hàng  như hàng A thì sẽ đến cái thứ 2 là xếp lần lượt  theo number như A1,A2,A3 */
            fn ($s) => $s->row_label ?? '',
            fn ($s) => $s->seat_number ?? 0,
        ])->values(); /* tiếp theo trỏ value() là để xếp lại key của  tập hợp(colection) của $roomSeat;
                        vì khi nó lấy tất cả ghế thì nó lấy lần lượt bảng key cx lần lượt 0,1,2...
                         giả sử lộn xộn mà mình bên trên xếp lại  theo ưu tiên sortBy thì nó đc sắp xếp lại nhưng key vẫn không thay đổi
                         ví dụ: trước đó key[1] ghế A2 và key[2] ghế A1 nhưng khi sortBy xếp lại thành Key[2] A1 rồi mới đến [key1] A2
                                => ta có thể thấy là nó key vẫn đi theo mà ko lần lượt thì value() chính là xếp lại lần lượt key theo đúng tiêu chuẩn lần lượt 0,1,2,....
                                */

        foreach ($roomSeats as $seat) {
            $row = $seat->row_label;
            $seatNum = $seat->seat_number;
            if (!$row || $seatNum === null) {
                continue;
            }

            $showtimeSeat = $showtimeSeatsBySeatId->get($seat->id);
            if (!$showtimeSeat) {
                continue;
            }

            // Xác định trạng thái hiển thị (SOLD/BLOCKED/BROKEN/AVAILABLE)
            $baseStatus = $seat->status ?? 'ACTIVE';

            if (in_array($baseStatus, ['BLOCKED', 'BROKEN'])) {
                $displayStatus = $baseStatus;
            } else {
                $isSold = DB::table('booking_seats')
                    ->join('bookings', 'bookings.id', '=', 'booking_seats.booking_id')
                    ->where('booking_seats.showtime_seat_id', $showtimeSeat->id)
                    ->where('bookings.showtime_id', $id)
                    ->whereIn('bookings.status', ['PAID', 'PENDING'])
                    ->exists();

                $displayStatus = $isSold ? 'SOLD' : ($showtimeSeat->status ?? 'AVAILABLE');
            }

            $seatType = $seat->seat_type ?? 'STANDARD';
            $mappedType = 'standard';
            if ($seatType === 'VIP') {
                $mappedType = 'vip';
            } elseif ($seatType === 'COUPLE') {
                // view staff dùng type 'sweetbox' để bắt cặp COUPLE
                $mappedType = 'sweetbox';
            } elseif ($seatType === 'DEMO') {
                $mappedType = 'demo';
            }

            if (!isset($seatMap[$row])) {
                $seatMap[$row] = [];
            }

            // label để view staff hiển thị: admin render theo seat_number, nên label = seat_number-1
            $labelIndex = (int)$seatNum - 1;

            $seatMap[$row][] = [
                'id'     => $showtimeSeat->id,
                'code'   => $seat->seat_code ?? ($row . $seatNum),
                'price'  => (int) ($showtimeSeat->price ?? $seat->price ?? 90000),
                'status' => $displayStatus,
                'type'   => $mappedType,
                'label'  => $labelIndex,
                // Đồng bộ aisle theo mỗi 10 ghế/hàng như rule admin
                'is_aisle' => ((($labelIndex + 1) % 10) === 0),
            ];
        }
        // (đã thay bằng build seatMap theo kiểu admin ở trên)

        // Trả về showtime (có movie + room) và seatMap
        return [
            'showtime' => $showtime,
            'seatMap'  => $seatMap,
        ];
    }

    /**
     * Tạo Booking từ Staff (Đặt vé hộ).
     *
     * Tái sử dụng logic từ BookingController::checkout() nhưng:
     * - Không dùng Cache hold seat (Staff không cần timer 5 phút)
     * - Validate ghế trực tiếp từ DB
     * - Tính toán tổng tiền ở backend (không tin frontend)
     *
     * @return array ['success' => bool, 'booking_code' => string, 'order_code' => string, 'message' => string]
     */
    public function createBookingFromStaff(array $data): array
    {
        $showtimeId    = $data['showtime_id'];
        $seatIds       = $data['seats'];           // showtime_seat IDs
        $combos        = $data['combos'] ?? [];     // [{id, name, quantity, total_price}, ...]
        $products      = $data['products'] ?? [];   // [{id, name, quantity, total_price}, ...]
        $customerName  = $data['customer_name'];
        $customerPhone = $data['customer_phone'];
        $customerEmail = $data['customer_email'];
        $paymentMethod = $data['payment_method'] ?? 'ONLINE';
        $staffUserId   = $data['staff_user_id'];

        DB::beginTransaction();
        try {
            // ── VALIDATE: Suất chiếu còn mở ──
            $showtime = Showtime::with(['movie', 'room'])->findOrFail($showtimeId);
            if (now()->greaterThan($showtime->start_time)) {
                throw new \Exception('Suất chiếu đã bắt đầu, không thể đặt vé.');
            }

            // ── VALIDATE: Tất cả ghế chưa bán ──
            $seats = ShowtimeSeat::with('seat')->whereIn('id', $seatIds)->get();
            if ($seats->count() !== count($seatIds)) {
                throw new \Exception('Một số ghế không tồn tại.');
            }

            foreach ($seats as $seat) {
                $baseSeatStatus = $seat->seat->status ?? 'ACTIVE';
                if (in_array($baseSeatStatus, ['BLOCKED', 'BROKEN'])) {
                    throw new \Exception("Ghế {$seat->seat->seat_code} đang bị khóa hoặc hỏng.");
                }

                $isSold = DB::table('booking_seats')
                    ->join('bookings', 'bookings.id', '=', 'booking_seats.booking_id')
                    ->where('booking_seats.showtime_seat_id', $seat->id)
                    ->where('bookings.showtime_id', $showtimeId)
                    ->whereIn('bookings.status', ['PAID', 'PENDING'])
                    ->exists();

                if ($isSold) {
                    throw new \Exception("Ghế {$seat->seat->seat_code} đã được đặt.");
                }
            }

            // ── TÍNH TOÁN TỔNG TIỀN TỪ BACKEND ──
            $totalTicketAmount = $seats->sum('price');

            // Tính combo từ DB (không tin frontend price)
            $totalComboAmount = 0;
            $validatedCombos = [];
            foreach ($combos as $comboItem) {
                $combo = Combo::where('status', 'ACTIVE')->find($comboItem['id'] ?? 0);
                if ($combo && ($comboItem['quantity'] ?? 0) > 0) {
                    $qty = (int) $comboItem['quantity'];
                    $subtotal = $combo->price * $qty;
                    $totalComboAmount += $subtotal;
                    $validatedCombos[] = [
                        'combo_id'    => $combo->id,
                        'name'        => $combo->name,
                        'quantity'    => $qty,
                        'unit_price'  => $combo->price,
                        'total_price' => $subtotal,
                    ];
                }
            }

            // Tính sản phẩm lẻ từ DB
            $totalProductAmount = 0;
            $validatedProducts = [];
            foreach ($products as $productItem) {
                $product = Product::find($productItem['id'] ?? 0);
                if ($product && ($productItem['quantity'] ?? 0) > 0) {
                    $qty = (int) $productItem['quantity'];
                    $subtotal = $product->price * $qty;
                    $totalProductAmount += $subtotal;
                    $validatedProducts[] = [
                        'product_id'  => $product->id,
                        'name'        => $product->name,
                        'quantity'    => $qty,
                        'unit_price'  => $product->price,
                        'total_price' => $subtotal,
                    ];
                }
            }

            $finalAmount = $totalTicketAmount + $totalComboAmount + $totalProductAmount;
            if ($finalAmount < 0) {
                $finalAmount = 0;
            }

            // ── TẠO BOOKING ──
            $ticketService = app(TicketService::class);
            $bookingCode = $ticketService->generateUniqueBookingCode();

            $status = 'PENDING';

            $booking = Booking::create([
                'booking_code'       => $bookingCode,
                'user_id'            => $staffUserId,
                'showtime_id'        => $showtimeId,
                'customer_name'      => $customerName,
                'customer_email'     => $customerEmail,
                'customer_phone'     => $customerPhone,
                'total_ticket_amount' => $totalTicketAmount,
                'total_combo_amount'  => $totalComboAmount,
                'discount_amount'    => 0,
                'final_amount'       => $finalAmount,
                'status'             => $status,
                'payment_status'     => 'UNPAID',
                'expired_at'         => now()->addMinutes(15),
            ]);

            // ── TẠO BOOKING SEATS ──
            $seatDetails = [];
            foreach ($seats as $seat) {
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

                // Build seat details cho SepayOrder metadata
                $mappedType = 'standard';
                $seatKind = $seat->seat->seat_type ?? 'STANDARD';
                if ($row === 'J' || $seatKind === 'COUPLE') {
                    $mappedType = 'sweetbox';
                } elseif ($seatKind === 'VIP') {
                    $mappedType = 'vip';
                } elseif ($seatKind === 'DEMO') {
                    $mappedType = 'demo';
                }

                $seatDetails[] = [
                    'code'  => $seat->seat->seat_code ?? 'N/A',
                    'type'  => $mappedType,
                    'price' => (int) $seat->price,
                ];
            }

            // ── TẠO BOOKING COMBOS ──
            foreach ($validatedCombos as $comboItem) {
                BookingCombo::create([
                    'booking_id' => $booking->id,
                    'combo_id'   => $comboItem['combo_id'],
                    'quantity'   => $comboItem['quantity'],
                    'unit_price' => $comboItem['unit_price'],
                    'total_price' => $comboItem['total_price'],
                ]);
            }

            // ── TẠO BOOKING PRODUCTS ──
            foreach ($validatedProducts as $productItem) {
                BookingProduct::create([
                    'booking_id'  => $booking->id,
                    'product_id'  => $productItem['product_id'],
                    'quantity'    => $productItem['quantity'],
                    'unit_price'  => $productItem['unit_price'],
                    'total_price' => $productItem['total_price'],
                ]);
            }

            // ── TẠO SEPAY ORDER (cho thanh toán QR) ──
            $comboDetailsForMeta = [];
            foreach ($validatedCombos as $c) {
                $comboDetailsForMeta[] = [
                    'name'        => $c['name'],
                    'quantity'    => $c['quantity'],
                    'unit_price'  => $c['unit_price'],
                    'total_price' => $c['total_price'],
                ];
            }

            $sepayOrder = SepayOrder::create([
                'order_code'   => $bookingCode,
                'booking_id'   => $booking->id,
                'package_id'   => 'booking',
                'package_name' => 'Vé xem phim (Staff)',
                'amount'       => $finalAmount,
                'status'       => 'pending',
                'metadata'     => [
                    'movie_title'    => $showtime->movie->title ?? '',
                    'room'           => $showtime->room->name ?? '',
                    'showtime'       => Carbon::parse($showtime->start_time)->format('H:i') . ' - ' . Carbon::parse($showtime->end_time)->format('H:i'),
                    'show_date'      => Carbon::parse($showtime->start_time)->format('d/m/Y'),
                    'format'         => '2D',
                    'seats'          => $seatDetails,
                    'seat_count'     => count($seatDetails),
                    'combos'         => $comboDetailsForMeta,
                    'showtime_id'    => $showtimeId,
                    'customer_email' => $customerEmail,
                    'customer_name'  => $customerName,
                    'customer_phone' => $customerPhone,
                    'booked_by'      => 'staff',
                    'staff_user_id'  => $staffUserId,
                ],
            ]);

            DB::commit();

            Log::info('Staff booking created', [
                'booking_code' => $bookingCode,
                'staff_user_id' => $staffUserId,
                'showtime_id'  => $showtimeId,
                'seat_count'   => count($seatIds),
                'amount'       => $finalAmount,
            ]);

            return [
                'success'      => true,
                'booking_code' => $bookingCode,
                'order_code'   => $bookingCode,
                'message'      => 'Tạo đơn đặt vé thành công.',
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Staff booking failed', [
                'error'        => $e->getMessage(),
                'staff_user_id' => $staffUserId,
                'showtime_id'  => $showtimeId,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}

