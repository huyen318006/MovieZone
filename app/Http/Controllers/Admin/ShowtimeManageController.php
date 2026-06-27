<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cinema;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShowtimeManageController extends Controller
{
    private const MIN_GAP_MINUTES = 15;

    private function hasBookingActivity(Showtime $showtime): bool
    {
        $hasBooking = $showtime->bookings()->exists();

        $hasReservedSeat = $showtime->showtimeSeats()
            ->whereIn('status', ['HELD', 'SOLD'])
            ->exists();

        return $hasBooking || $hasReservedSeat;
    }


    private function currentCinema(): Cinema
    {
        return Cinema::query()
            ->where('status', 'ACTIVE')
            ->orderBy('id')
            ->firstOrFail();
    }

    private function visibleMovies()
    {
        return Movie::query()
            ->where('status', '!=', 'HIDDEN')
            ->orderBy('title');
    }

    private function activeRoomsForCurrentCinema()
    {
        return Room::query()
            ->where('cinema_id', $this->currentCinema()->id)
            ->where('status', 'ACTIVE')
            ->orderBy('name');
    }

    private function hasScheduleConflict(Room $room, Carbon $startTime, Carbon $endTime, ?int $ignoreShowtimeId = null): bool
    {
        return Showtime::query()
            ->where('room_id', $room->id)
            ->where('status', '!=', 'CANCELLED')
            ->when($ignoreShowtimeId, function ($query) use ($ignoreShowtimeId) {
                $query->where('id', '!=', $ignoreShowtimeId);
            })
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime->copy()->addMinutes(self::MIN_GAP_MINUTES))
                    ->where('end_time', '>', $startTime->copy()->subMinutes(self::MIN_GAP_MINUTES));
            })
            ->exists();
    }

    private function isWithinMovieReleaseWindow(Movie $movie, Carbon $startTime): bool
    {
        $showDate = $startTime->toDateString();

        if ($movie->release_date && $showDate < Carbon::parse($movie->release_date)->toDateString()) {
            return false;
        }

        if ($movie->end_date && $showDate > Carbon::parse($movie->end_date)->toDateString()) {
            return false;
        }

        return true;
    }

    private function loadShowtime(int $id): Showtime
    {
        $cinemaId = $this->currentCinema()->id;

        return Showtime::query()
            ->with(['movie', 'room', 'cinema', 'bookings','showtimeSeats'])
            ->where('cinema_id', $cinemaId)
            ->findOrFail($id);
    }

    public function listShowtime(){
        /*- Chức năng hiển thị danh sách suất chiếu trong hệ thống
          - Cho phép Admin tìm kiếm và lọc suất chiếu
          * Điều kiện:
            - Admin đã đăng nhập và có quyền quản lý suất chiếu
          Bộ lọc: phim,phong chiếu,ngày chiếu,giờ chiếu,trạng thái
          Kết quả trả về: danh sách suất chiếu kèm thông tin phim và phòng.
         */
        // Lấy dữ liệu filter từ request
        $filters = request()->only(['movie', 'room', 'date', 'status']);
        // Danh sách phim để hiển thị combo bộ lọc
        $cinema = $this->currentCinema();
        $movies = $this->visibleMovies()->get();
        //Danh sách phòng chiếu hiển thị theo bộ lọc
        $rooms = $this->activeRoomsForCurrentCinema()->get();
        //truy vấn danh sách suất chiếu 
        $showtimes = Showtime::query()
            //  Eager Loading tránh N+1 Query
            ->with(['movie', 'room'])
            ->where('cinema_id', $cinema->id)
            // Lọc theo phim
            ->when($filters['movie'] ?? null, function ($query, $movieId) {
                $query->where('movie_id', $movieId);
            })
            // Lọc theo phòng chiếu
            ->when($filters['room'] ?? null, function ($query, $roomId) {
                $query->where('room_id', $roomId);
            })
            // Lọc theo ngày chiếu
            ->when($filters['date'] ?? null, function ($query, $date) {
                $query->whereDate('start_time', $date);
            })
            // Lọc theo trạng thái
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            //suất chiếu mới nhất hiên hị đầu
            ->orderBy('start_time', 'desc')
            // phân trang 10 suất chiếu trên 1 trang
            ->paginate(10);
        //Trả dữ liêu về giao diện quản lí suất chiếu
        return view('admin.showtime.management', compact('showtimes', 'movies', 'rooms', 'filters'));
    }
    

    public function formAdd(){
        /* HIển thị form tạo suất chiếu
         * Chức năng: 
         - Hiển thị màn hình tạo suất chiếu mới.
         - Cho phép Admin lựa chọn phim và phòng chiếu.
         * Điều kiện: 
         - Phim không bị ẩn
         - phòng chiếu đang hoạt động
         * Kết quả trả về: form thêm suất chiếu với danh sách phim và phòng chiếu
         */
        // Danh sách phim dang hoạt đọng
        $movies = $this->visibleMovies()->get();
        // Danh ssach phong dang hoạt động
        $rooms = $this->activeRoomsForCurrentCinema()->get();
        return view('admin.showtime.add', compact('movies','rooms'));
    }

    public function store(Request $request){
        /*TẠo suất chiếu
            Luồng nghiệp vụ
            1: Validate đầu vào
            2: kiểm tra phim tồn tại ko
            3: Kiểm tra phòng hoạt động
            4: Tính giờ kết thúc từ thời lượng phim
            5: Kiểm tra trùng lịch suất chiếu
            6:  Tạo suất chiếu
            7: sinh danh sách ghế cho suất chiếu
            8: Thông báo thành công
         */
        $request->validate([
            'movie_id' => ['required', 'exists:movies,id'],
            'room_id' => ['required', 'exists:rooms,id'],
            'start_time' => ['required', 'date'],
            'format' => ['required', 'in:2D,3D,IMAX'],
            'language_type' => ['required', 'string', 'max:255'],
        ]);

        $cinema = $this->currentCinema();

        // Lấy phim
        $movie = Movie::query()->where('status', '!=', 'HIDDEN')->findOrFail($request->movie_id);

        // Lấy phòng trong đúng rạp duy nhất của hệ thống
        $room = Room::query()
            ->where('cinema_id', $cinema->id)
            ->where('status', 'ACTIVE')
            ->findOrFail($request->room_id);

        // start_time từ input datetime-local sẽ có dạng 'YYYY-MM-DDTHH:mm'
        // Carbon::parse có thể hiểu sai timezone/định dạng trên một số máy.
        // Ép sang cùng format rõ ràng và so sánh với now() theo thời gian hệ thống.
        try {
            // datetime-local thường có dạng: YYYY-MM-DDTHH:mm
            $startTime = Carbon::createFromFormat(
                'Y-m-d\TH:i',
                (string) $request->start_time,
                config('app.timezone')
            );
        } catch (\Throwable $e) {
            $startTime = Carbon::parse($request->start_time);
        }
        $now = now();

        // Cho phép start_time bằng thời điểm hiện tại (tùy business), tránh báo lỗi sai do chênh vài ms/giây.
        if ($startTime->lt($now)) {
            return back()
                ->withInput()
                ->with('error', 'Thời gian bắt đầu phải lớn hơn hoặc bằng thời điểm hiện tại.');
        }

        if ((int) $movie->duration_minutes <= 0) {
            return back()
                ->withInput()
                ->with('error', 'Phim chưa có thời lượng hợp lệ.');
        }

        if (! $this->isWithinMovieReleaseWindow($movie, $startTime)) {
            return back()
                ->withInput()
                ->with('error', 'Ngày chiếu không nằm trong thời gian phát hành của phim.');
        }

        $endTime = $startTime->copy()->addMinutes((int) $movie->duration_minutes);

        if ($endTime->lessThanOrEqualTo($startTime)) {
            return back()
                ->withInput()
                ->with('error', 'Giờ kết thúc phải lớn hơn giờ bắt đầu.');
        }

        if ($this->hasScheduleConflict($room, $startTime, $endTime)) {
            return back()
                ->withInput()
                ->with('error', 'Phòng chiếu đã có suất chiếu trong khung giờ này.');
        }

        $seats = Seat::query()
            ->where('room_id', $room->id)
            ->where('status', 'ACTIVE')
            ->get();

        $invalidSeat = $seats->first(function ($seat) {
            return (float) $seat->price <= 0;
        });

        if ($invalidSeat) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Phòng chiếu có ghế chưa được cấu hình giá vé hợp lệ.'
                );
        }

        if ($seats->isEmpty()) {
            return back()
                ->withInput()
                ->with('error', 'Phòng chiếu chưa được cấu hình sơ đồ ghế.');
        }

        DB::beginTransaction();
        try {
            $showtime = Showtime::create([
                'movie_id' => $movie->id,
                'cinema_id' => $cinema->id,
                'room_id' => $room->id,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'format' => $request->format,
                'language_type' => $request->language_type,
                'status' => 'OPEN',
            ]);

            // Gom mảng dữ liệu ghế
            $showtimeSeatsData = [];
            foreach ($seats as $seat) {
                $showtimeSeatsData[] = [
                    'showtime_id' => $showtime->id,
                    'seat_id' => $seat->id,
                    'price' => $seat->price,
                    'status' => 'AVAILABLE',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Chạy 1 câu lệnh duy nhất thay vì lặp từng câu lệnh
            ShowtimeSeat::insert($showtimeSeatsData);

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'CREATE_SHOWTIME',
                'entity_name' => 'showtime',
                'entity_id' => (string) $showtime->id,
                'old_value' => null,
                'new_value' => json_encode([
                    'id' => $showtime->id,
                    'movie_id' => $showtime->movie_id,
                    'room_id' => $showtime->room_id,
                    'start_time' => $showtime->start_time,
                    'end_time' => $showtime->end_time,
                    'format' => $showtime->format,
                    'language_type' => $showtime->language_type,
                    'status' => $showtime->status,
                ]),
                'created_at' => now(),
            ]);

            DB::commit();

            return redirect()
                ->route('admin.showtime')
                ->with('success', 'Tạo suất chiếu thành công.');
        } catch (\Throwable $e) {
            DB::rollBack();
            dd(
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );
        //     return back()
        //         ->withInput()
        //         ->with('error', 'Có lỗi xảy ra khi tạo suất chiếu.');
        }
    }

    public function viewUpdate($id){
        /* Hiển thị form cập nhật suất chiếu
            Chức năng:
            - Hiển thị thông tin suất chiếu hiện tại.
            - Cho phép Admin chỉnh sửa suất chiếu.
            Điều kiện:
            - Suất chiếu phải tồn tại.
            - Phim không bị ẩn.
            - Phòng chiếu đang hoạt động.
            Kết quả:
            - Trả về màn hình cập nhật suất chiếu.
         */

        // Lấy suất chiếu
        $showtime = $this->loadShowtime($id);
        // Nếu suất chiểu đã bị hủy thì không thể sửa đc.
        if ($showtime->status === 'CANCELLED') {
            return back()->with('error', 'Suất chiếu đã bị hủy, không thể chỉnh sửa.');
        }
        // BR10: nếu đã bắt đầu thì không cho chỉnh sửa
        if ($showtime->start_time->lessThanOrEqualTo(now())) {
            return back()->with('error', 'Suất chiếu đã bắt đầu, không thể chỉnh sửa.');
        }
        if ($this->hasBookingActivity($showtime)) {

            return back()->with(
                'error',
                'Suất chiếu đã phát sinh hoạt động đặt vé nên không thể chỉnh sửa.'
            );
        }
        // Danh sách phim
        $movies = $this->visibleMovies()->get();
        // Danh sách phòng
        $rooms = $this->activeRoomsForCurrentCinema()->get();

            return view('admin.showtime.update',compact('showtime','movies','rooms')
        );
    }

    public function update(Request $request, $id)
    {
        /*
        Cập nhật suất chiếu
        Chức năng:
        - Cập nhật thông tin suất chiếu.
        - Kiểm tra dữ liệu hợp lệ.
        - Kiểm tra trùng lịch phòng chiếu.
        Điều kiện:
        - Suất chiếu phải tồn tại.
        - Suất chiếu chưa bắt đầu.
        Kết quả:
        - Cập nhật suất chiếu thành công.

        */

        // Lấy suất chiếu
        // Validate dữ liệu
        $request->validate([
            'movie_id' => ['required', 'exists:movies,id'],
            'room_id' => ['required', 'exists:rooms,id'],
            'start_time' => ['required', 'date'],
            'format' => ['required', 'in:2D,3D,IMAX'],
            'language_type' => ['required', 'string', 'max:255'],
        ]);

        $cinema = $this->currentCinema();
        $showtime = $this->loadShowtime($id);
        // Nếu suất chiểu đã bị hủy thì không thể sửa đc.
        if ($showtime->status === 'CANCELLED') {
            return back()->with('error', 'Suất chiếu đã bị hủy, không thể chỉnh sửa.');
        }
        if ($showtime->start_time->lessThanOrEqualTo(now())) {
            return back()->with('error', 'Suất chiếu đã bắt đầu, không thể chỉnh sửa.');
        }

        // Lấy phim
        $movie = Movie::findOrFail(
            $request->movie_id
        );
        // Kiểm tra phim bị ẩn
        if ($movie->status === 'HIDDEN') {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Phim hiện không khả dụng.'
                );
        }
        // Lấy phòng
        $room = Room::query()
            ->where('cinema_id', $cinema->id)
            ->where('status', 'ACTIVE')
            ->findOrFail($request->room_id);

        if ((int) $movie->duration_minutes <= 0) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Phim chưa có thời lượng hợp lệ.'
                );
        }

        // Best practice theo BR: không cho phép đổi phòng sau khi tạo
        if ($showtime->room_id != $room->id) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Không được thay đổi phòng chiếu sau khi tạo suất chiếu.'
                );
        }

        $startTime = Carbon::parse($request->start_time);


        if ($startTime->lessThanOrEqualTo(now())) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Thời gian bắt đầu phải lớn hơn thời điểm hiện tại.'
                );
        }

        if (! $this->isWithinMovieReleaseWindow($movie, $startTime)) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Ngày chiếu không nằm trong thời gian phát hành của phim.'
                );
        }

        $endTime = $startTime->copy()->addMinutes((int) $movie->duration_minutes);

        if ($this->hasBookingActivity($showtime)) {

            return back()
                ->withInput()
                ->with('error', 'Suất chiếu đã phát sinh hoạt động đặt vé nên không thể chỉnh sửa.');
        }
        $conflict = $this->hasScheduleConflict($room, $startTime, $endTime, $showtime->id);

        if ($conflict) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Phòng chiếu đã có suất chiếu trong khung giờ này.'
                );
        }

        DB::beginTransaction();

        try {
            $before = $showtime->only(['id', 'movie_id', 'room_id', 'start_time', 'end_time', 'format', 'status']);

            $showtime->update([
                'movie_id' => $movie->id,
                'cinema_id' => $cinema->id,
                'room_id' => $room->id,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'format' => $request->format,
                'language_type' => $request->language_type,
            ]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'UPDATE_SHOWTIME',
                'entity_name' => 'showtime',
                'entity_id' => (string) $showtime->id,
                'old_value' => json_encode($before),
                'new_value' => json_encode($showtime->fresh(['movie', 'room', 'cinema'])->toArray()),
                'created_at' => now(),
            ]);

            DB::commit();

            return redirect()
                ->route('admin.showtime')
                ->with(
                    'success',
                    'Cập nhật suất chiếu thành công.'
                );
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra khi cập nhật suất chiếu.');
        }
    }

    public function detail($id)
    {
        $showtime = $this->loadShowtime($id);

        return view('admin.showtime.detail', compact('showtime'));
    }

    public function confirmCancel($id)
    {
        // Hiển thị màn hình xác nhận hủy suất chiếu
        $showtime = $this->loadShowtime($id);

        $bookingCount = Booking::where(
            'showtime_id',
            $showtime->id
        )->count();

        return view('admin.showtime.cancel',compact('showtime','bookingCount')
        );
    }
    public function cancel(Request $request, $id)
    {
        /*
        UC-A2: Hủy suất chiếu
        BR05: bắt buộc lý do
        BR07: phải log
        BR10: không xóa cứng nếu có booking
        BR11: không cancel nếu started/finished
        */
        $request->validate([
            'reason' => ['required', 'string', 'max:255']
        ]);

        DB::beginTransaction();
        
        try {
            // Lock để tránh 2 admin cancel cùng lúc
            $showtime = Showtime::query()
                ->with(['bookings', 'movie', 'room', 'cinema'])
                ->where('cinema_id', $this->currentCinema()->id)
                ->lockForUpdate()
                ->findOrFail($id);

            // BR11: không cho hủy nếu đã bắt đầu hoặc đã kết thúc
            if ($showtime->start_time->lessThanOrEqualTo(now()) || $showtime->end_time->lessThanOrEqualTo(now())) {
                return back()->with('error', 'Suất chiếu đã bắt đầu, không thể hủy.');
            }

            // CHỈ LẤY CÁC THUỘC TÍNH CỦA SHOWTIME ĐỂ TRÁNH LỖI JSON ENCODE ĐỆ QUY
            $before = $showtime->only(['id', 'movie_id', 'cinema_id', 'room_id', 'start_time', 'end_time', 'status', 'cancel_reason', 'cancelled_at']);

            // BR10: cảnh báo nếu đã có booking
            if ($this->hasBookingActivity($showtime)) {
                DB::rollBack();

                return back()->with(
                    'error',
                    'Không thể hủy suất chiếu vì đã phát sinh hoạt động đặt vé.'
                );
            }

            // Update trạng thái
            $showtime->update([
                'status' => 'CANCELLED',
                'cancel_reason' => $request->reason,
                'cancelled_at' => now(),
            ]);


            // Lấy lại dữ liệu mới sau khi update (chỉ lấy các trường cốt lõi)
            $after = [
                'id' => $showtime->id,
                'movie_id' => $showtime->movie_id,
                'cinema_id' => $showtime->cinema_id,
                'room_id' => $showtime->room_id,
                'start_time' => $showtime->start_time,
                'end_time' => $showtime->end_time,
                'status' => 'CANCELLED',
                'cancel_reason' => $request->reason,
                'cancelled_at' => now(),
            ];

            // BR07 + BR14: log đầy đủ
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'CANCEL_SHOWTIME',
                'entity_name' => 'showtime',
                'entity_id' => $showtime->id,
                'old_value' => json_encode($before),
                'new_value' => json_encode($after),
                'created_at' => now(),
            ]);

            DB::commit();

            return redirect()
                ->route('admin.showtime')
                ->with(
                    'success',
                    'Hủy suất chiếu thành công.'
                );


        } catch (\Exception $e) {
            DB::rollBack();
            
            // Ghi log lỗi thật ra 
            logger()->error('Lỗi hủy suất chiếu ID ' . $id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            // Trả về kèm câu thông báo lỗi chi tiết của hệ thống
            return back()->with('error', 'Có lỗi xảy ra khi hủy suất chiếu. Chi tiết: ' . $e->getMessage());
        }
    }

    public function checkConflict(Request $request)
    {
        $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'movie_id' => ['required', 'exists:movies,id'],
            'start_time' => ['required', 'date'],
            'showtime_id' => ['nullable', 'integer', 'exists:showtimes,id'],
        ]);

        $cinema = $this->currentCinema();

        $movie = Movie::query()->where('status', '!=', 'HIDDEN')->findOrFail($request->movie_id);
        $room = Room::query()
            ->where('cinema_id', $cinema->id)
            ->where('status', 'ACTIVE')
            ->findOrFail($request->room_id);

        $startTime = Carbon::parse($request->start_time);
        $endTime = $startTime->copy()->addMinutes((int) $movie->duration_minutes);
        $conflict = $this->hasScheduleConflict($room, $startTime, $endTime, $request->showtime_id);

        return response()->json([
            'conflict' => $conflict,
            'message' => $conflict ? 'Phòng chiếu đã có suất chiếu trong khung giờ này.' : 'OK',
        ]);
    }
}
