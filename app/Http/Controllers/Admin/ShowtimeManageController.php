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

    private static function normalizeRoomType(?string $roomType): string
    {
        return mb_strtolower(trim((string) ($roomType ?? '')));
    }

    private static function roomTypeMatchesAllowedFormats(?string $roomType, array $allowedFormats): bool
    {
        if (empty($allowedFormats)) {
            return true;
        }

        $normalizedRoomType = self::normalizeRoomType($roomType);

        foreach ($allowedFormats as $allowedFormat) {
            if (self::normalizeRoomType($allowedFormat) === $normalizedRoomType) {
                return true;
            }
        }

        return false;
    }

    private function getAllowedRoomTypesForMovie(Movie $movie): array
    {
        return DB::table('movie_room_types')
            ->where('movie_id', $movie->id)
            ->pluck('type_name_room')
            ->filter(fn ($value) => ! blank($value))
            ->map(fn ($value) => trim((string) $value))
            ->values()
            ->all();
    }

    private function normalizeRequestedShowtimes(Request $request): array
    {
        $payload = $request->input('selected_showtimes');

        if (is_string($payload) && filled($payload)) {
            $decoded = json_decode($payload, true);
            if (is_array($decoded)) {
                $items = array_filter(array_map(function ($item) {
                    if (! is_array($item)) {
                        return null;
                    }

                    if (empty($item['room_id']) || empty($item['start_time'])) {
                        return null;
                    }

                    return [
                        'room_id' => (int) $item['room_id'],
                        'start_time' => (string) $item['start_time'],
                    ];
                }, $decoded));

                return array_values($items);
            }
        }

        if ($request->filled('room_id') && $request->filled('start_time')) {
            return [[
                'room_id' => (int) $request->room_id,
                'start_time' => (string) $request->start_time,
            ]];
        }

        return [];
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

    /**
     * Lấy suất chiếu gây xung đột lịch chiếu của phòng (dùng để giải thích cho Admin)
     * Các bước xử lý:
     * Bước 1: Tìm suất chiếu cùng phòng, không bị hủy, bỏ qua suất chiếu đang sửa (nếu có).
     * Bước 2: Kiểm tra khoảng thời gian giao thoa (có tính thêm 15 phút buffer dọn phòng).
     * Bước 3: Load thông tin Phim đi kèm để lấy tiêu đề hiển thị.
     */
    private function getConflictingShowtime(Room $room, Carbon $startTime, Carbon $endTime, ?int $ignoreShowtimeId = null): ?Showtime
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
            ->with('movie')
            ->first();
    }

    private function isWithinMovieReleaseWindow(Movie $movie, Carbon $startTime): bool
    {
        // Compare using inclusive day bounds (startOfDay..endOfDay) to avoid
        // off-by-one due to timezone conversions. Use application timezone
        // when parsing movie release/end dates.
        $tz = config('app.timezone') ?: date_default_timezone_get();

        if ($movie->release_date) {
            $releaseStart = Carbon::parse($movie->release_date, $tz)->startOfDay();
        } else {
            $releaseStart = null;
        }

        if ($movie->end_date) {
            $releaseEnd = Carbon::parse($movie->end_date, $tz)->endOfDay();
        } else {
            $releaseEnd = null;
        }

        $checkTime = $startTime->copy()->setTimezone($tz);

        if ($releaseStart && $checkTime->lt($releaseStart)) {
            return false;
        }

        if ($releaseEnd && $checkTime->gt($releaseEnd)) {
            return false;
        }

        return true;
    }

    private function getMovieReleaseWindowLabel(Movie $movie): string
    {
        $startDate = $movie->release_date ? Carbon::parse($movie->release_date)->format('d/m/Y') : 'không giới hạn';
        $endDate = $movie->end_date ? Carbon::parse($movie->end_date)->format('d/m/Y') : 'không giới hạn';

        return $startDate === $endDate
            ? 'Ngày chiếu hợp lệ: ' . $startDate
            : 'Ngày chiếu hợp lệ từ ' . $startDate . ' đến ' . $endDate;
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
            ->with(['movie', 'room','bookings','showtimeSeats'])
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
            // Suất chiếu vừa tạo nên hiện đầu tiên
            ->orderByDesc('created_at')
            ->orderByDesc('start_time')
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
        /*
        Luồng tạo suất chiếu (không chạm tới chức năng khác):
        1. Validate dữ liệu đầu vào: phim, danh sách suất đã chọn.
        2. Lấy thông tin phim và danh sách phòng được phép chiếu theo định dạng phim.
        3. Với mỗi suất được chọn, kiểm tra:
           - phòng hoạt động và phù hợp định dạng phim,
           - ngày chiếu nằm trong khoảng phát hành phim,
           - thời lượng phim hợp lệ,
           - không có xung đột lịch trong phòng,
           - phòng đã cấu hình ghế và giá vé hợp lệ.
        4. Tạo bản ghi showtime, tạo showtime_seats cho từng ghế, ghi audit log.
        5. Trả về trang quản lý suất chiếu với thông báo kết quả.

        Chú ý: luồng này chỉ tác động đến bảng showtimes, showtime_seats, audit_logs và giao diện admin showtime.
        */
        $request->validate([
            'movie_id' => ['required', 'exists:movies,id'],
            'selected_showtimes' => ['nullable', 'string'],
        ]);

        $cinema = $this->currentCinema();

        // Lấy phim
        $movie = Movie::query()->where('status', '!=', 'HIDDEN')->findOrFail($request->movie_id);
        $allowedRoomTypes = $this->getAllowedRoomTypesForMovie($movie);
        $requestedShowtimes = $this->normalizeRequestedShowtimes($request);

        if ($requestedShowtimes === []) {
            return back()
                ->withInput()
                ->with('error', 'Vui lòng chọn ít nhất một suất chiếu.');
        }

        DB::beginTransaction();
        try {
            foreach ($requestedShowtimes as $showtimeRequest) {
                $room = Room::query()
                    ->where('cinema_id', $cinema->id)
                    ->where('status', 'ACTIVE')
                    ->findOrFail($showtimeRequest['room_id']);

                if (! self::roomTypeMatchesAllowedFormats($room->room_type, $allowedRoomTypes)) {
                    DB::rollBack();

                    return back()
                        ->withInput()
                        ->with('error', 'Phòng ' . $room->name . ' không phù hợp với định dạng được phép chiếu của phim ' . $movie->title . '.');
                }

                try {
                    $startTime = Carbon::createFromFormat(
                        'Y-m-d\TH:i',
                        (string) $showtimeRequest['start_time'],
                        config('app.timezone')
                    );
                } catch (\Throwable $e) {
                    $startTime = Carbon::parse($showtimeRequest['start_time']);
                }

                $now = now();

                if ($startTime->lt($now)) {
                    DB::rollBack();

                    return back()
                        ->withInput()
                        ->with('error', 'Thời gian bắt đầu phải lớn hơn hoặc bằng thời điểm hiện tại.');
                }

                if ((int) $movie->duration_minutes <= 0) {
                    DB::rollBack();

                    return back()
                        ->withInput()
                        ->with('error', 'Phim chưa có thời lượng hợp lệ.');
                }

                if (! $this->isWithinMovieReleaseWindow($movie, $startTime)) {
                    DB::rollBack();

                    return back()
                        ->withInput()
                        ->with('error', 'Ngày chiếu không nằm trong thời gian phát hành của phim.');
                }

                $endTime = $startTime->copy()->addMinutes((int) $movie->duration_minutes);

                if ($endTime->lessThanOrEqualTo($startTime)) {
                    DB::rollBack();

                    return back()
                        ->withInput()
                        ->with('error', 'Giờ kết thúc phải lớn hơn giờ bắt đầu.');
                }

                if ($this->hasScheduleConflict($room, $startTime, $endTime)) {
                    DB::rollBack();

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
                    DB::rollBack();

                    return back()
                        ->withInput()
                        ->with('error', 'Phòng chiếu có ghế chưa được cấu hình giá vé hợp lệ.');
                }

                if ($seats->isEmpty()) {
                    DB::rollBack();

                    return back()
                        ->withInput()
                        ->with('error', 'Phòng chiếu chưa được cấu hình sơ đồ ghế.');
                }

                $showtime = Showtime::create([
                    'movie_id' => $movie->id,
                    'cinema_id' => $cinema->id,
                    'room_id' => $room->id,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    // 'format' => $room->room_type, lấy định dạng theo bộ phim
                    'status' => 'OPEN',
                ]);

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
                        'status' => $showtime->status,
                    ]),
                    'created_at' => now(),
                ]);
            }

            DB::commit();

            return redirect()
                ->route('admin.showtime')
                ->with('success', 'Đã tạo ' . count($requestedShowtimes) . ' suất chiếu thành công.');
        } catch (\Throwable $e) {
            DB::rollBack();

            logger()->error('Lỗi tạo suất chiếu: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra khi tạo suất chiếu.');
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
        // Bước 1: Validate dữ liệu đầu vào
        $request->validate([
            'movie_id' => ['required', 'exists:movies,id'],
            'room_id' => ['required', 'exists:rooms,id'],
            'start_time' => ['required', 'date'],
        ]);

        $cinema = $this->currentCinema();

        // Bước 2: Bắt đầu DB Transaction để đảm bảo tính toàn vẹn dữ liệu
        DB::beginTransaction();

        try {
            // Bước 3: Truy vấn suất chiếu cần cập nhật và sử dụng lockForUpdate() để tránh race condition
            $showtime = Showtime::query()
                ->where('cinema_id', $cinema->id)
                ->lockForUpdate()
                ->findOrFail($id);

            // Bước 4: Kiểm tra các điều kiện nghiệp vụ của suất chiếu
            // - Không chỉnh sửa nếu suất chiếu đã bị hủy
            if ($showtime->status === 'CANCELLED') {
                DB::rollBack();
                return back()->with('error', 'Suất chiếu đã bị hủy, không thể chỉnh sửa.');
            }
            // - Không chỉnh sửa nếu suất chiếu đã bắt đầu
            if ($showtime->start_time->lessThanOrEqualTo(now())) {
                DB::rollBack();
                return back()->with('error', 'Suất chiếu đã bắt đầu, không thể chỉnh sửa.');
            }
            // - Không chỉnh sửa nếu đã phát sinh hoạt động đặt vé hoặc giữ ghế
            if ($this->hasBookingActivity($showtime)) {
                DB::rollBack();
                return back()
                    ->withInput()
                    ->with('error', 'Suất chiếu đã phát sinh hoạt động đặt vé nên không thể chỉnh sửa.');
            }

            // Bước 5: Lấy thông tin phim và kiểm tra tính hợp lệ
            $movie = Movie::findOrFail($request->movie_id);
            if ($movie->status === 'HIDDEN') {
                DB::rollBack();
                return back()
                    ->withInput()
                    ->with('error', 'Phim hiện không khả dụng.');
            }
            if ((int) $movie->duration_minutes <= 0) {
                DB::rollBack();
                return back()
                    ->withInput()
                    ->with('error', 'Phim chưa có thời lượng hợp lệ.');
            }

            // Bước 6: Lấy thông tin phòng chiếu mới
            $room = Room::query()
                ->where('cinema_id', $cinema->id)
                ->where('status', 'ACTIVE')
                ->findOrFail($request->room_id);

            // Bước 7: Xử lý thời gian chiếu
            $startTime = Carbon::parse($request->start_time);
            if ($startTime->lessThanOrEqualTo(now())) {
                DB::rollBack();
                return back()
                    ->withInput()
                    ->with('error', 'Thời gian bắt đầu phải lớn hơn thời điểm hiện tại.');
            }

            if (! $this->isWithinMovieReleaseWindow($movie, $startTime)) {
                DB::rollBack();
                return back()
                    ->withInput()
                    ->with('error', 'Ngày chiếu không nằm trong thời gian phát hành của phim.');
            }

            $endTime = $startTime->copy()->addMinutes((int) $movie->duration_minutes);

            // Bước 8: Kiểm tra trùng lịch suất chiếu trong phòng (trừ chính suất chiếu đang sửa)
            $conflict = $this->hasScheduleConflict($room, $startTime, $endTime, $showtime->id);
            if ($conflict) {
                DB::rollBack();
                return back()
                    ->withInput()
                    ->with('error', 'Phòng chiếu đã có suất chiếu trong khung giờ này.');
            }

            // Lưu trữ dữ liệu trước khi thay đổi để ghi log
            $before = $showtime->only(['id', 'movie_id', 'room_id', 'start_time', 'end_time', 'status']);
            $oldRoomName = $showtime->room->name ?? 'N/A';
            $isRoomSwapped = ($showtime->room_id != $room->id);
            $seatCountUpdated = 0;

            // Bước 9: Xử lý Đổi phòng (Nếu có thay đổi phòng và chưa có ai đặt vé)
            if ($isRoomSwapped) {
                // 9.1: Xóa các ghế của phòng cũ trong suất chiếu
                ShowtimeSeat::where('showtime_id', $showtime->id)->delete();

                // 9.2: Lấy danh sách ghế đang hoạt động của phòng mới
                $seats = Seat::query()
                    ->where('room_id', $room->id)
                    ->where('status', 'ACTIVE')
                    ->get();

                if ($seats->isEmpty()) {
                    DB::rollBack();
                    return back()
                        ->withInput()
                        ->with('error', 'Phòng chiếu mới chưa được cấu hình sơ đồ ghế.');
                }

                // 9.3: Kiểm tra cấu hình giá ghế của phòng mới
                $invalidSeat = $seats->first(function ($seat) {
                    return (float) $seat->price <= 0;
                });
                if ($invalidSeat) {
                    DB::rollBack();
                    return back()
                        ->withInput()
                        ->with('error', 'Phòng chiếu mới có ghế chưa được cấu hình giá vé hợp lệ.');
                }

                // 9.4: Tạo mới danh sách ghế suất chiếu cho phòng mới
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
                ShowtimeSeat::insert($showtimeSeatsData);
                $seatCountUpdated = count($showtimeSeatsData);
            }

            // Bước 10: Cập nhật thông tin suất chiếu
            $showtime->update([
                'movie_id' => $movie->id,
                'cinema_id' => $cinema->id,
                'room_id' => $room->id,
                'start_time' => $startTime,
                'end_time' => $endTime,
                // 'format' => $room->room_type,
            ]);

            // Bước 11: Ghi log thao tác Admin vào AuditLog
            $logAction = $isRoomSwapped ? 'SWAP_ROOM_AND_UPDATE_SHOWTIME' : 'UPDATE_SHOWTIME';
            $logNewValue = array_merge(
                $showtime->fresh(['movie', 'room', 'cinema'])->toArray(),
                [
                    'meta' => [
                        'room_swapped' => $isRoomSwapped,
                        'old_room_name' => $oldRoomName,
                        'new_room_name' => $room->name,
                        'seat_count_updated' => $seatCountUpdated,
                    ]
                ]
            );

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => $logAction,
                'entity_name' => 'showtime',
                'entity_id' => (string) $showtime->id,
                'old_value' => json_encode($before),
                'new_value' => json_encode($logNewValue),
                'created_at' => now(),
            ]);

            // Bước 12: Commit Transaction và redirect
            DB::commit();

            $msg = $isRoomSwapped 
                ? "Cập nhật suất chiếu và đổi sang {$room->name} thành công." 
                : "Cập nhật suất chiếu thành công.";

            return redirect()
                ->route('admin.showtime')
                ->with('success', $msg);

        } catch (\Throwable $e) {
            DB::rollBack();

            logger()->error('Lỗi cập nhật suất chiếu ID ' . $id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra khi cập nhật suất chiếu. Chi tiết: ' . $e->getMessage());
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

    /* ============================================================
     * API: Lấy thông tin phim + số suất chiếu trong ngày
     * - Được gọi bằng AJAX từ wizard tạo suất chiếu (Bước 1)
     * - Trả về: thông tin phim, thể loại, số suất đã xếp trong ngày
     * ============================================================ */
    public function apiGetMovieInfo(Request $request)
    {
        $request->validate([
            'movie_id' => ['required', 'exists:movies,id'],
            'date'     => ['nullable', 'date'],
        ]);

        // Lấy phim (không lấy phim ẩn)
        $movie = Movie::query()
            ->where('status', '!=', 'HIDDEN')
            ->with('genres')
            ->findOrFail($request->movie_id);

        $cinema = $this->currentCinema();

        // Đếm số suất chiếu của phim trong ngày được chọn (nếu có)
        $showtimeCountToday = 0;
        $totalShowtimesInDay = 0;
        if ($request->date) {
            $date = Carbon::parse($request->date);
            // Số suất chiếu của phim này trong ngày
            $showtimeCountToday = Showtime::query()
                ->where('cinema_id', $cinema->id)
                ->where('movie_id', $movie->id)
                ->where('status', '!=', 'CANCELLED')
                ->whereDate('start_time', $date)
                ->count();
            // Tổng số suất chiếu tất cả phim trong ngày
            $totalShowtimesInDay = Showtime::query()
                ->where('cinema_id', $cinema->id)
                ->where('status', '!=', 'CANCELLED')
                ->whereDate('start_time', $date)
                ->count();
        }

        return response()->json([
            'movie' => [
                'id'               => $movie->id,
                'title'            => $movie->title,
                'original_title'   => $movie->original_title,
                'poster_url'       => $movie->poster_url,
                'duration_minutes' => (int) $movie->duration_minutes,
                'age_rating'       => $movie->age_rating,
                'release_date'     => $movie->release_date,
                'end_date'         => $movie->end_date,
                'status'           => $movie->status,
                'language'         => $movie->language,
                'director'         => $movie->director,
                'genres'           => $movie->genres->pluck('name')->toArray(),
                'date_window_label' => $this->getMovieReleaseWindowLabel($movie),
            ],
            'showtime_count_today'   => $showtimeCountToday,
            'total_showtimes_in_day' => $totalShowtimesInDay,
        ]);
    }

    /* ============================================================
     * API: Lấy danh sách phòng + lịch chiếu trong ngày
     * - Được gọi bằng AJAX từ wizard tạo suất chiếu (Bước 3)
     * - Trả về: danh sách phòng active kèm trạng thái & lịch chiếu
     * ============================================================ */
    public function apiGetRoomSchedule(Request $request)
    {
        $request->validate([
            'date' => ['required', 'date'],
        ]);

        $cinema = $this->currentCinema();
        $date = Carbon::parse($request->date);
        $now = now();

        // Lấy tất cả phòng active của rạp
        $rooms = Room::query()
            ->where('cinema_id', $cinema->id)
            ->where('status', 'ACTIVE')
            ->orderBy('name')
            ->get();

        // Lấy tất cả suất chiếu trong ngày (không bị hủy) cho tất cả phòng
        $showtimesInDay = Showtime::query()
            ->where('cinema_id', $cinema->id)
            ->where('status', '!=', 'CANCELLED')
            ->whereDate('start_time', $date)
            ->with('movie:id,title,duration_minutes,poster_url')
            ->orderBy('start_time')
            ->get();

        // Gom suất chiếu theo room_id
        $showtimesByRoom = $showtimesInDay->groupBy('room_id');

        $result = [];
        foreach ($rooms as $room) {
            $roomShowtimes = $showtimesByRoom->get($room->id, collect());

            // Xác định trạng thái phòng tại thời điểm hiện tại
            $currentlyShowing = $roomShowtimes->first(function ($st) use ($now) {
                return $st->start_time->lte($now) && $st->end_time->gte($now);
            });
            $aboutToStart = $roomShowtimes->first(function ($st) use ($now) {
                return $st->start_time->gt($now) && $st->start_time->lte($now->copy()->addMinutes(30));
            });

            if ($currentlyShowing) {
                $roomStatus = 'SHOWING';
                $roomStatusLabel = 'Đang chiếu';
            } elseif ($aboutToStart) {
                $roomStatus = 'UPCOMING';
                $roomStatusLabel = 'Sắp chiếu';
            } else {
                $roomStatus = 'FREE';
                $roomStatusLabel = 'Trống';
            }

            $result[] = [
                'id'           => $room->id,
                'name'         => $room->name,
                'room_type'    => $room->room_type,
                'total_seats'  => (int) $room->total_seats,
                'status'       => $roomStatus,
                'status_label' => $roomStatusLabel,
                'showtime_count' => $roomShowtimes->count(),
                'showtimes'    => $roomShowtimes->map(function ($st) {
                    return [
                        'id'         => $st->id,
                        'movie_title'=> $st->movie->title ?? 'N/A',
                        'start_time' => $st->start_time->format('H:i'),
                        'end_time'   => $st->end_time->format('H:i'),
                        'start_hour' => (float) $st->start_time->format('G') + $st->start_time->format('i') / 60,
                        'end_hour'   => (float) $st->end_time->format('G') + $st->end_time->format('i') / 60,
                        'status'     => $st->status,
                    ];
                })->values()->toArray(),
            ];
        }

        return response()->json([
            'rooms' => $result,
            'total_showtimes' => $showtimesInDay->count(),
        ]);
    }

    /* ============================================================
     * API: Lấy chi tiết timeline của 1 phòng trong ngày
     * - Được gọi bằng AJAX từ wizard tạo suất chiếu (Bước 3 - khi click phòng)
     * - Trả về: danh sách suất chiếu chi tiết + khoảng trống
     * ============================================================ */
    public function apiGetRoomTimeline(Request $request)
    {
        $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'date'    => ['required', 'date'],
            'showtime_id' => ['nullable', 'integer', 'exists:showtimes,id'],
        ]);

        $cinema = $this->currentCinema();
        $date = Carbon::parse($request->date);

        // Kiểm tra phòng thuộc đúng rạp
        $room = Room::query()
            ->where('cinema_id', $cinema->id)
            ->where('status', 'ACTIVE')
            ->findOrFail($request->room_id);

        // Lấy tất cả suất chiếu của phòng trong ngày (không bị hủy)
        $showtimes = Showtime::query()
            ->where('room_id', $room->id)
            ->where('status', '!=', 'CANCELLED')
            ->whereDate('start_time', $date)
            ->with('movie:id,title,duration_minutes,poster_url')
            ->orderBy('start_time')
            ->get();

        // Tính các khoảng trống (gap) giữa các suất chiếu
        // Timeline từ 6:00 đến 24:00
        $gaps = [];
        $timelineStart = $date->copy()->setTime(6, 0);
        $timelineEnd = $date->copy()->setTime(23, 59);

        $previousEnd = $timelineStart->copy();
        foreach ($showtimes as $st) {
            // Cộng thêm MIN_GAP_MINUTES (15 phút) vệ sinh vào end_time
            $slotStart = $st->start_time->copy()->subMinutes(self::MIN_GAP_MINUTES);
            $slotEnd = $st->end_time->copy()->addMinutes(self::MIN_GAP_MINUTES);

            if ($slotStart->gt($previousEnd)) {
                $gapMinutes = $previousEnd->diffInMinutes($slotStart);
                $gaps[] = [
                    'start'   => $previousEnd->format('H:i'),
                    'end'     => $slotStart->format('H:i'),
                    'minutes' => $gapMinutes,
                ];
            }
            $previousEnd = $slotEnd->copy();
        }
        // Khoảng trống cuối cùng (từ suất cuối đến 24:00)
        if ($previousEnd->lt($timelineEnd)) {
            $gaps[] = [
                'start'   => $previousEnd->format('H:i'),
                'end'     => $timelineEnd->format('H:i'),
                'minutes' => $previousEnd->diffInMinutes($timelineEnd),
            ];
        }

        // Đếm số ghế đã cấu hình cho phòng
        $seatCount = Seat::where('room_id', $room->id)->where('status', 'ACTIVE')->count();

        // Trả về thêm danh sách showtimes để FE có thể kiểm tra slot conflict theo từng suất
        return response()->json([
            'room' => [
                'id'          => $room->id,
                'name'        => $room->name,
                'room_type'   => $room->room_type,
                'total_seats' => (int) $room->total_seats,
                'seat_count'  => $seatCount,
            ],
            'showtimes' => $showtimes->map(function ($st) {
                return [
                    'id'              => $st->id,
                    'movie_title'     => $st->movie->title ?? 'N/A',
                    'movie_poster'    => $st->movie->poster_url ?? null,
                    'start_time'      => $st->start_time->format('H:i'),
                    'end_time'        => $st->end_time->format('H:i'),
                    'status'          => $st->status,
                ];
            })->values()->toArray(),
            'gaps' => $gaps,
            'date' => $date->format('Y-m-d'),
        ]);

    }
    /* ============================================================
     * API: Kiểm tra danh sách phòng chiếu khả dụng & lý do trùng
     * - Được gọi bằng AJAX từ màn hình cập nhật suất chiếu khi thay đổi
     *   thời gian bắt đầu hoặc phim.
     * - Trả về: danh sách tất cả phòng kèm trạng thái trống/trùng chi tiết
     * ============================================================ */
    public function apiCheckRoomsAvailability(Request $request)
    {
        // Bước 1: Validate thông tin truyền lên
        $request->validate([
            'movie_id' => ['required', 'exists:movies,id'],
            'start_time' => ['required', 'date'],
            'showtime_id' => ['nullable', 'integer', 'exists:showtimes,id'],
        ]);

        $cinema = $this->currentCinema();
        
        // Bước 2: Lấy thông tin phim
        $movie = Movie::query()->where('status', '!=', 'HIDDEN')->findOrFail($request->movie_id);
        
        // Bước 3: Parse thời gian bắt đầu dựa trên timezone
        try {
            $startTime = Carbon::createFromFormat(
                'Y-m-d\TH:i',
                (string) $request->start_time,
                config('app.timezone')
            );
        } catch (\Throwable $e) {
            $startTime = Carbon::parse($request->start_time);
        }

        // Bước 4: Tính thời gian kết thúc dựa vào thời lượng phim
        $endTime = $startTime->copy()->addMinutes((int) $movie->duration_minutes);

        // Bước 5: Lấy tất cả phòng đang hoạt động thuộc rạp hiện tại
        $allowedRoomTypes = $this->getAllowedRoomTypesForMovie($movie);
        $rooms = Room::query()
            ->where('cinema_id', $cinema->id)
            ->where('status', 'ACTIVE')
            ->orderBy('name')
            ->get();

        $result = [];
        
        // Bước 6: Duyệt qua từng phòng để kiểm tra trùng lịch chi tiết
        foreach ($rooms as $room) {
            if (! empty($allowedRoomTypes) && ! self::roomTypeMatchesAllowedFormats($room->room_type, $allowedRoomTypes)) {
                continue;
            }

            $conflict = $this->getConflictingShowtime($room, $startTime, $endTime, $request->showtime_id);
            
            $isAvailable = $conflict ? false : true;
            $conflictReason = '';
            
            if ($conflict) {
                $conflictReason = 'Trùng lịch với phim "' . ($conflict->movie->title ?? 'N/A') . '" (' . $conflict->start_time->format('H:i') . ' - ' . $conflict->end_time->format('H:i') . ')';
            }

            $result[] = [
                'id' => $room->id,
                'name' => $room->name,
                'room_type' => $room->room_type,
                'total_seats' => (int) $room->total_seats,
                'is_available' => $isAvailable,
                'conflict_reason' => $conflictReason,
            ];
        }

        // Bước 7: Trả kết quả JSON về cho client
        // Trường hợp update suất chiếu: cho phép chọn lại đúng chính suất đang sửa
        // (room + start_time bị trùng chính showtime_id) => getConflictingShowtime đã bỏ qua showtime_id.
        return response()->json([
            'rooms' => $result,
        ]);
    }

    // NOTE:
    // apiCheckRoomsAvailability dùng start_time/end_time để lock phòng khi trùng lịch.
    // Khi admin đang edit một showtime thì cần loại xung đột với chính showtime đó.
    // Logic bỏ qua này nằm trong getConflictingShowtime() bằng $ignoreShowtimeId.
    // Vì client luôn gửi showtime_id đang sửa, getConflictingShowtime sẽ không trả conflict
    // nếu conflict thuộc chính showtime_id đó.

    /* ============================================================


     * API: Quét tất cả các phòng & tính toán khoảng trống khả dụng
     * - Đầu vào: movie_id, date
     * - Xử lý: 
     *   1. Xác định thời gian quét trong ngày (nếu là hôm nay, bắt đầu từ now() + 15 phút, ngược lại từ 06:00).
     *   2. Lấy các suất chiếu đã có của từng phòng trong ngày.
     *   3. Tìm các khoảng trống (gaps) giữa các suất chiếu.
     *   4. Cắt các khoảng trống thành các slot nhỏ tương ứng với thời lượng phim động + 15 phút buffer.
     * - Đầu ra: JSON danh sách phòng kèm các slot gợi ý khả dụng.
     * ============================================================ */
    public function apiGetAvailableSlots(Request $request)
    {
        // Bước 1: Validate đầu vào
        $request->validate([
            'movie_id' => ['required', 'exists:movies,id'],
            'date' => ['required', 'date'],
        ]);

        $cinema = $this->currentCinema();
        $movie = Movie::findOrFail($request->movie_id);
        $duration = (int) $movie->duration_minutes;

        $date = Carbon::parse($request->date);
        $now = now();

        // Bước 2: Xác định mốc thời gian bắt đầu quét lịch trống trong ngày
        if ($date->isToday()) {
            // Hôm nay: Bắt đầu từ hiện tại + 15 phút để chuẩn bị
            $timelineStart = $now->copy()->addMinutes(15);
            // Làm tròn phút lên khoảng 5 phút tiếp theo nhìn cho đẹp mắt
            $minutes = $timelineStart->minute;
            $remainder = $minutes % 5;
            if ($remainder > 0) {
                $timelineStart->addMinutes(5 - $remainder);
            }
            $timelineStart->second(0);
        } else {
            // Ngày khác: Bắt đầu từ 06:00 sáng
            $timelineStart = $date->copy()->setTime(6, 0, 0);
        }

        $timelineEnd = $date->copy()->setTime(23, 59, 0);

        // Bước 3: Lấy danh sách tất cả phòng đang hoạt động của rạp
        $allowedRoomTypes = $this->getAllowedRoomTypesForMovie($movie);
        $rooms = Room::query()
            ->where('cinema_id', $cinema->id)
            ->where('status', 'ACTIVE')
            ->orderBy('name')
            ->get();

        $result = [];

        // Bước 4: Duyệt qua từng phòng để phân tích khoảng trống
        foreach ($rooms as $room) {
            if (! empty($allowedRoomTypes) && ! self::roomTypeMatchesAllowedFormats($room->room_type, $allowedRoomTypes)) {
                continue;
            }

            // Lấy tất cả suất chiếu của phòng trong ngày (trừ các suất chiếu đã bị hủy)
            $showtimes = Showtime::query()
                ->where('room_id', $room->id)
                ->where('status', '!=', 'CANCELLED')
                ->whereDate('start_time', $date)
                ->orderBy('start_time')
                ->get();

            // Bước 5: Tìm các khoảng trống (gaps) thực tế trong ngày
            $gaps = [];
            $previousEnd = $timelineStart->copy();

            foreach ($showtimes as $st) {
                // Thời gian bắt đầu thực tế cần trống (có trừ đi 15 phút dọn dẹp vệ sinh trước suất chiếu)
                $slotStart = $st->start_time->copy()->subMinutes(self::MIN_GAP_MINUTES);
                
                // Thời gian kết thúc thực tế của suất chiếu (cộng thêm 15 phút dọn dẹp vệ sinh sau suất chiếu)
                $slotEnd = $st->end_time->copy()->addMinutes(self::MIN_GAP_MINUTES);

                if ($slotStart->gt($previousEnd)) {
                    $gaps[] = [
                        'start' => $previousEnd->copy(),
                        'end' => $slotStart->copy(),
                    ];
                }
                
                // Cập nhật previousEnd thành mốc kết thúc của suất chiếu hiện tại
                if ($slotEnd->gt($previousEnd)) {
                    $previousEnd = $slotEnd->copy();
                }
            }

            // Khoảng trống cuối cùng từ suất chiếu cuối đến cuối ngày (23:59)
            if ($previousEnd->lt($timelineEnd)) {
                $gaps[] = [
                    'start' => $previousEnd->copy(),
                    'end' => $timelineEnd->copy(),
                ];
            }

            // Bước 6: Phân chia các khoảng trống lớn thành các slot nhỏ khớp với thời lượng phim động + 15 phút vệ sinh
            $suggestedSlots = [];
            foreach ($gaps as $gap) {
                $gapStart = $gap['start'];
                $gapEnd = $gap['end'];
                
                $currentStart = $gapStart->copy();
                
                while (true) {
                    // Thời điểm kết thúc của bộ phim
                    $currentEnd = $currentStart->copy()->addMinutes($duration);
                    
                    // Nếu thời điểm kết thúc phim vượt quá giới hạn của khoảng trống này, dừng lại không chia tiếp nữa
                    if ($currentEnd->gt($gapEnd)) {
                        break;
                    }
                    
                    $suggestedSlots[] = [
                        'start_time' => $currentStart->format('Y-m-d H:i'),
                        'end_time' => $currentEnd->format('Y-m-d H:i'),
                        'start_label' => $currentStart->format('H:i'),
                        'end_label' => $currentEnd->format('H:i'),
                    ];
                    
                    // Suất chiếu tiếp theo bắt đầu sau khi phim kết thúc + 15 phút buffer dọn phòng
                    $currentStart = $currentEnd->copy()->addMinutes(self::MIN_GAP_MINUTES);
                }
            }

            $result[] = [
                'id' => $room->id,
                'name' => $room->name,
                'room_type' => $room->room_type,
                'total_seats' => $room->total_seats,
                'slots' => $suggestedSlots,
            ];
        }

        // Bước 7: Trả kết quả JSON về cho giao diện
        return response()->json([
            'movie' => [
                'id' => $movie->id,
                'title' => $movie->title,
                'duration' => $duration,
                'language' => $movie->language,
                'subtitle' => $movie->subtitle,
            ],
            'rooms' => $result,
        ]);
    }
}
