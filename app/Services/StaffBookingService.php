<?php

namespace App\Services;

use App\Models\Movie;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StaffBookingService
{
    public function __construct() {}

    // Lấy danh sách phim có suất chiếu sắp tới, kèm danh sách suất chiếu
    public function getMovies()
    {
        $time_now = Carbon::now();
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
            // SỬA LỖI CARBON TẠI ĐÂY: Parse chuỗi thời gian từ DB thành Object Carbon trước, sau đó mới format sang chuỗi
            $startTimeCarbon = Carbon::parse($row->start_time);
            $endTimeCarbon = Carbon::parse($row->end_time);

            //sau khi đã có thì thêm suất chiếu vào
            $movie[$movie_id]['showtimes'][] = [
                'showtime_id' => $row->id,
                'room_id' => $row->room_id,
                'room_name' => $row->room_name,
                'start_time' => $startTimeCarbon->format('H:i'),
                'end_time' => $endTimeCarbon->format('H:i'),
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

        // Đảm bảo roomSeats đã được sort như admin
        $roomSeats = $roomSeats->sortBy([
            fn ($s) => $s->row_label ?? '',
            fn ($s) => $s->seat_number ?? 0,
        ])->values();

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
                    ->whereIn('bookings.status', ['PAID', 'PENDING_PAYMENT', 'PENDING_CASH_PAYMENT'])
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



}
