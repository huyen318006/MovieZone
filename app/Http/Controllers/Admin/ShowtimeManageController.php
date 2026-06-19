<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\SystemLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShowtimeManageController extends Controller
{
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
        $movies = Movie::orderBy('title')->get();
        //Danh sách phòng chiếu hiển thị theo bộ lọc
        $rooms = Room::orderBy('name')->get();
        //truy vấn danh sách suất chiếu 
        $showtimes = Showtime::query()
            //  Eager Loading tránh N+1 Query
            ->with(['movie', 'room'])
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
            ->orderBy('start_time')
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
        $movies = Movie::where('status', '!=', 'HIDDEN')
            ->orderBy('title')
            ->get();
        // Danh ssach phong dang hoạt động
        $rooms = Room::where('status', 'ACTIVE')
            ->orderBy('title')
            ->get();
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
        'format' => ['required'],
        'language_type' => ['required'],
    ]);

    // Lấy phim
    $movie = Movie::findOrFail($request->movie_id);

    // Kiểm tra phim bị ẩn
    if ($movie->status === 'HIDDEN') {
        return back()
            ->withInput()
            ->with('error', 'Phim hiện không khả dụng.');
    }

    // Lấy phòng
    $room = Room::findOrFail($request->room_id);

    // Kiểm tra phòng hoạt động
    if ($room->status !== 'ACTIVE') {
        return back()
            ->withInput()
            ->with('error', 'Phòng chiếu không hoạt động.');
    }

    // Thời gian bắt đầu
    // Carbon là thư viện xử lí ngày giờ của laravel
    $startTime = Carbon::parse($request->start_time);

    // Không cho tạo suất chiếu trong quá khứ
    if ($startTime->lt(now())) {
        return back()
            ->withInput()
            ->with('error', 'Thời gian bắt đầu phải lớn hơn thời điểm hiện tại.');
    }

    // Tính thời gian kết thúc
    $endTime = $startTime->copy()->addMinutes($movie->duration_minutes);

    /*
     BR01: Kiểm tra trùng lịch phòng chiếu
     Điều kiện trùng:
     start mới < end cũ
     end mới > start cũ
    */

    $conflict = Showtime::where('room_id', $room->id)
        ->where('status', '!=', 'CANCELLED')
        ->where(function ($query) use ($startTime, $endTime) {
            $query->where(
                'start_time',
                '<',
                $endTime
            )->where(
                'end_time',
                '>',
                $startTime
            );
        })
        ->exists();
    if ($conflict) {
        return back()
            ->withInput()
            ->with(
                'error',
                'Phòng chiếu đã có suất chiếu trong khoảng thời gian này.'
            );
    }
    DB::beginTransaction(); // Dùng để giúp dữ liệu không bị chạy lệch, Tất cả SQL chạy thành công ->lưu hết, nếu 1 câu lệnh lỗi thì rollback lại.
    try {
        // Tạo suất chiếu
        $showtime = Showtime::create([
            'movie_id' => $movie->id,
            'cinema_id' => $room->cinema_id,
            'room_id' => $room->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'format' => $request->format,
            'language_type' => $request->language_type,
            'status' => 'OPEN',
        ]);

        /*
        BR03: Tạo ghế cho suất chiếu
        */
        $seats = Seat::where('room_id', $room->id)->get();
        foreach ($seats as $seat) {
            ShowtimeSeat::create([
                'showtime_id' => $showtime->id,
                'seat_id' => $seat->id,
                'price' => $seat->price,
                'status' => 'AVAILABLE',
            ]);
        }
        DB::commit();
        return redirect()
            ->route('admin.showtime')
            ->with(
                'success',
                'Tạo suất chiếu thành công.'
            );
    } catch (\Exception $e) {
        DB::rollBack();
        return back()
            ->withInput()
            ->with(
                'error',
                'Có lỗi xảy ra khi tạo suất chiếu.'
            );
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
        $showtime = Showtime::with(['movie','room'])->findOrFail($id);
        // Danh sách phim
        $movies = Movie::where('status','!=','HIDDEN')
            ->orderBy('title')
            ->get();
        // Danh sách phòng
        $rooms = Room::where('status','ACTIVE')
            ->orderBy('name')
            ->get();

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
        $showtime = Showtime::findOrFail($id);
        // BR06:Suất chiếu đã bắt đầu không được chỉnh sửa
        if ($showtime->start_time <= now()) {

            return redirect()
                ->route('admin.showtime')
                ->with(
                    'error',
                    'Suất chiếu đã bắt đầu và không thể chỉnh sửa.'
                );
        }
        // Validate dữ liệu
        $request->validate([
            'movie_id' => ['required', 'exists:movies,id'],
            'room_id' => ['required', 'exists:rooms,id'],
            'start_time' => ['required', 'date'],
            'format' => ['required'],
            'language_type' => ['required'],
        ]);
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
        $room = Room::findOrFail(
            $request->room_id
        );
        // Kiểm tra phòng hoạt động
        if ($room->status !== 'ACTIVE') {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Phòng chiếu không hoạt động.'
                );
        }
        // Thời gian bắt đầu mới
        $startTime = Carbon::parse(
            $request->start_time
        );
        // Tính giờ kết thúc
        $endTime = $startTime
            ->copy()
            ->addMinutes(
                $movie->duration_minutes
            );

        //E1: Kiểm tra trùng lịch
        $conflict = Showtime::where('room_id', $room->id)
            ->where('id','!=', $showtime->id)
            ->where('status','!=', 'CANCELLED')
            ->where(function ($query) use ($startTime,$endTime)
            {
                $query->where('start_time','<',$endTime)
                    ->where('end_time','>',$startTime);
            })
            ->exists();

        if ($conflict) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Phòng chiếu đã có suất chiếu trong khung giờ này.'
                );
        }

        // Cập nhật dữ liệu
        $showtime->update([
            'movie_id' => $movie->id,
            'cinema_id' => $room->cinema_id,
            'room_id' => $room->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'format' => $request->format,
            'language_type' => $request->language_type,
        ]);

        return redirect()
            ->route('admin.showtime')
            ->with(
                'success',
                'Cập nhật suất chiếu thành công.'
            );
    }

    public function detail($id){}

    public function confirmCancel($id)
    {
        // Hiển thị màn hình xác nhận hủy suất chiếu
        $showtime = Showtime::with([
            'movie',
            'room'
        ])->findOrFail($id);

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
            // lock để tránh 2 admin cancel cùng lúc
            $showtime = Showtime::with(['bookings'])
                ->lockForUpdate()
                ->findOrFail($id);
            $before = $showtime->toArray();
            // BR11: không cho hủy nếu đã kết thúc
            if ($showtime->status === 'FINISHED') {
                return back()->with('error', 'Suất chiếu đã kết thúc, không thể hủy.');
            }
            // BR11: không cho hủy nếu đã bắt đầu
            if ($showtime->start_time <= now()) {
                return back()->with('error', 'Suất chiếu đã bắt đầu, không thể hủy.');
            }
            // BR10: cảnh báo nếu đã có booking
            $hasBooking = $showtime->bookings->count() > 0;
            // update trạng thái
            $showtime->update([
                'status' => 'CANCELLED',
                'cancel_reason' => $request->reason,
                'cancelled_at' => now(),
            ]);
            $showtime->refresh();
            $after = $showtime->toArray();
            // BR07 + BR14: log đầy đủ
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'CANCEL_SHOWTIME',
                'entity_name' => 'showtime',
                'entity_id' => $showtime->id,
                'old_value' => json_encode($before),
                'new_value' => json_encode($after),
            ]);
            DB::commit();
            return redirect()
                ->route('admin.showtime')
                ->with(
                    'success',
                    $hasBooking
                        ? 'Hủy suất chiếu thành công (đã có booking trước đó).'
                        : 'Hủy suất chiếu thành công.'
                );
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->with('error', 'Có lỗi xảy ra khi hủy suất chiếu.');
        }
    }

    public function checkConflict(Request $request){}
}
