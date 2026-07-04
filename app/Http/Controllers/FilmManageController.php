<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateFilmRequest;
use App\Models\Booking;
use App\Models\Coin;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\MovieGenre;
use App\Models\Showtime;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use App\Mail\FilmCancelledNotification;
use App\Models\Room;
use Illuminate\Support\Facades\Log;

class FilmManageController extends Controller
{
    //

    public function listmovie(Request $request)
    {

        //ở  đây DEV-tien_hung  làm 1 đoạn cập nhật trạng thái của film thay vì cập nhật bằng tay. ae tham khảo đoạn này nếu cần !!!
        //trước khi list và filter thì cần xem phim nào đã  hạn chiếu or hết hạn chiếu thì cập nhật lại trạng thái
        //  AUTO UPDATE TRẠNG THÁI TRƯỚC KHI LOAD
        // 1. Sắp chiếu → đang chiếu
        Movie::where('status', 'COMING_SOON')
            ->whereDate('release_date', '<=', now())
            ->update([
                'status' => 'NOW_SHOWING'
            ]);

        // 2. Đang chiếu → đã kết thúc
        // Điều kiện: ngày kết thúc đã qua hoặc (nếu không có ngày kết thúc) ngày hiện tại đã qua ngày khởi chiếu
        Movie::where('status', 'NOW_SHOWING')
            ->whereNotNull('end_date') //  tránh null
            ->whereDate('end_date', '<', now())
            ->update([
                'status' => 'ENDED'
            ]);
        /* -------------------------------- End cập nhật trạng thái ---------------*/
        // 3. Ẩn phim đã kết thúc quá lâu (ví dụ: đã kết thúc hơn 10 ngày)
        Movie::where('status', 'ENDED')
            ->whereDate('end_date', '<', now()->subDays(10))
            ->update([
                'status' => 'HIDDEN'
            ]);
        // 4. Kiểm tra ở trạng thái HIDDEN, nếu phim được set lại ngày khởi chiếu hợp lệ (>= today + 3)
        // thì tự động chuyển thành COMING_SOON (Sắp chiếu)
        Movie::where('status', 'HIDDEN')
            ->whereDate('release_date', '>=', now()->addDays(3))
            ->update([
                'status' => 'COMING_SOON',
            ]);


        /* -------------------------------- End cập nhật trạng thái ---------------*/



        //lấy toàn bộ movie
        // query gốc
        $query = DB::table('movies')
            ->leftJoin('movie_genres', 'movie_genres.movie_id', '=', 'movies.id')
            ->leftJoin('genres', 'movie_genres.genre_id', '=', 'genres.id')
            ->select(
                'movies.*',
                DB::raw('GROUP_CONCAT(genres.name SEPARATOR ", ") as genres_name')
            )
            ->groupBy('movies.id');

        //lấy toàn bộ thể loại để đổ vào filter
        $allGenres = Genre::all();

        // FILTER THEO STATUS
        if ($request->status) {
            $query->where('movies.status', $request->status);
        }

        //FILTER THEO GENRE
        if ($request->genre) {

            $query->where('genres.id', $request->genre);
        }

        // paginate + giữ query khi chuyển trang
        $movieGenres = $query
            ->paginate(10)
            ->appends($request->all());

        return view('admin.film_management.film', compact('movieGenres', 'allGenres'));
    }


    //form thêm film
    public function formadd(Request $request)
    {
        //đổ thể loại cho view
        $genres = Genre::all();
        return view('admin.film_management.addfilm', compact('genres'));
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Thông tin cơ bản
            'title' => 'required|string|max:255',
            'original_title' => 'nullable|string|max:255',
            'description' => 'nullable|string',

            // Phát hành
            'duration_minutes' => 'required|integer|min:1|max:500',
            'release_date' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    $minDate = \Carbon\Carbon::today()->addDays(3);

                    if (\Carbon\Carbon::parse($value)->lt($minDate)) {
                        $fail('Ngày khởi chiếu phải từ ' . $minDate->format('d/m/Y') . ' trở đi (ít nhất sau 3 ngày kể từ hôm nay).');
                    }
                }
            ],
            'end_date' => [
                'nullable',
                'date',
                'after:release_date'
            ],
            'status' => 'required|in:COMING_SOON,NOW_SHOWING,ENDED,HIDDEN',

            // Nội dung
            'country' => 'required|string|max:100',
            'language' => 'required|string|max:100',
            'subtitle' => 'nullable|string|max:100',
            'director' => 'nullable|string|max:255',
            'age_rating' => 'required|in:P,K,T13,T16,T18',
            'cast' => 'nullable|string',

            // Thể loại (checkbox array)
            'genres' => 'required|array',
            'genres.*' => 'exists:genres,id',

            // Media
            'poster' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'trailer_url' => 'nullable|url',
        ], [
            // Custom message (tuỳ chọn)
            'title.required' => 'Tên phim không được để trống',
            'end_date.after' => 'Ngày kết thúc phải lớn hơn ngày khởi chiếu',
            'duration_minutes.required' => 'Vui lòng nhập thời lượng phim',
            'release_date.required' => 'Ngày khởi chiếu là bắt buộc',
            'status.in' => 'Trạng thái không hợp lệ',
            'age_rating.in' => 'Độ tuổi không hợp lệ',
        ]);
        $poster = null;
        $banner = null;

        //kiểm tra xem nếu có hệ thống có cập nhật poster
        if ($request->hasFile('poster')) {
            //kiểm tra hasFile() xem form đẩy lên có ô input file tên poster không
            $poster = $request->file('poster')?->store('poster_film', 'public');
        }

        //kiểm tra xem nếu hệ thống có đẩy banner lên ko
        if ($request->hasFile('banner')) {
            $banner = $request->file('banner')?->store('banner_film', 'public');
        }
        // dd($poster);


        $movie = Movie::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title) . '-' . time(),
            'original_title' => $request->original_title,
            'description' => $request->description,
            'duration_minutes' => $request->duration_minutes,
            'release_date' => $request->release_date,
            'end_date' => $request->end_date,
            'status' => $request->status,
            'country' => $request->country,
            'language' => $request->language,
            'subtitle' => $request->subtitle,
            'director' => $request->director,
            'age_rating' => $request->age_rating,
            'cast' => $request->cast,
            'poster_url' => $poster,
            'banner_url' => $banner,
            'trailer_url' => $request->trailer_url ?? null,
        ]);
        // dd($movie);
        $movie->genres()->sync($request->input('genres', []));
        DB::table('audit_logs')->insert([
            'user_id'     => auth()->id(),
            'action'      => 'create_movie',
            'entity_name' => 'movies',
            'entity_id'   => (string) $movie->id,

            'old_value'   => json_encode(null),

            'new_value'   => json_encode($movie->toArray()),

            'created_at'  => now(),
        ]);

        return redirect()->route('admin.film')->with('success', 'Phim đã được thêm thành công!');
    }


    // phần update
    public function viewupdate($id)
    {
        $movie = Movie::findOrFail($id);

        // Lấy thêm genres_name (danh sách tên thể loại dạng chuỗi)
        $movie_id = DB::table('movies')
            ->leftJoin('movie_genres', 'movie_genres.movie_id', '=', 'movies.id')
            ->leftJoin('genres', 'movie_genres.genre_id', '=', 'genres.id')
            ->select(
                'movies.*',
                DB::raw('GROUP_CONCAT(genres.name SEPARATOR ", ") as genres_name')
            )->where('movies.id', $id)->groupBy('movies.id')
            ->first();

        // ID thể loại hiện tại — dùng để tick sẵn checkbox
        $currentGenreIds = DB::table('movie_genres')
            ->where('movie_id', $id)
            ->pluck('genre_id')
            ->toArray();

        $genres = Genre::all();

        return view('admin.film_management.updatefilm', compact('movie_id', 'genres', 'currentGenreIds'));
    }

    public function apiCheckSlots(Request $request)
    {
        // Đọc tham số ngầm từ AJAX gửi sang
        $releaseDate = $request->input('release_date');
        $endDate     = $request->input('end_date');
        $duration    = (int) $request->input('duration_minutes');
        $cleaningTime = 15; // Quy ước thời gian dọn dẹp và chuẩn bị phòng chiếu giữa các suất (phút)

        // Tổng quỹ thời gian liên tục tối thiểu cần phải chiếm dụng cho 1 suất chiếu
        $requiredTime = $duration + $cleaningTime;

        $rooms = Room::all(); // Lấy thông tin các phòng trong hệ thống rạp
        $totalAvailableSlots = 0; // Biến tích lũy tổng số slot trống khả thi tìm được

        // Dùng Carbon thiết lập mốc thời gian bắt đầu và kết thúc chu kỳ chiếu
        $startPeriod = Carbon::parse($releaseDate)->startOfDay();
        $endPeriod   = Carbon::parse($endDate)->endOfDay();

        // VÒNG LẶP 1: Chạy qua từng ngày một trong chu kỳ phân phối phim
        for ($date = $startPeriod->copy(); $date->lte($endPeriod); $date->addDay()) {
            $dateStr = $date->toDateString();

            // VÒNG LẶP 2: Quét lần lượt từng phòng chiếu trong ngày đó
            foreach ($rooms as $room) {
                // Định nghĩa khung giờ hoạt động cố định của phòng ngày hôm đó (Mặc định 08:00 đến 23:00 nếu DB trống)
                $openTime  = Carbon::parse($dateStr . ' ' . ($room->open_time ?? '08:00:00'));
                $closeTime = Carbon::parse($dateStr . ' ' . ($room->close_time ?? '23:00:00'));


                // Truy vấn toàn bộ các lịch chiếu ĐÃ LÊN LỊCH của phòng này trong ngày hôm nay (Sắp xếp tăng dần theo giờ chiếu)
                // Lấy các suất chiếu KHÔNG chỉ theo start_time, mà theo phần giao với ngày hiện tại.
                // Vì một suất có thể bắt đầu trước 00:00 nhưng kết thúc trong ngày.
                $showtimes = Showtime::where('room_id', $room->id)
                    ->where('status', '!=', 'CANCELLED')
                    ->where('start_time', '<=', $date->copy()->endOfDay())
                    ->where('end_time', '>=', $date->copy()->startOfDay())
                    ->orderBy('start_time', 'asc')
                    ->get();



                // Đặt điểm bắt đầu rà soát khoảng hở đầu tiên chính là giờ mở cửa phòng rạp
                $lastEndTime = $openTime;

                // VÒNG LẶP 3: Duyệt tuần tự qua từng lịch chiếu cố định để bóc tách khoảng hở ở giữa
                foreach ($showtimes as $showtime) {
                    $showtimeStart = Carbon::parse($showtime->start_time);
                    $showtimeEnd   = Carbon::parse($showtime->end_time);


                    // Đo đạc khoảng cách thời gian (phút) từ suất trước (hoặc từ lúc mở cửa) đến mốc bắt đầu của suất này
                    // Dùng max để đảm bảo không bị âm do diffInMinutes tùy tham số
                    $freeMinutes = max(0, $lastEndTime->diffInMinutes($showtimeStart));

                    // Nếu khoảng trống lớn hơn hoặc bằng tổng quỹ thời gian phim cần chiếm dụng
                    if ($freeMinutes >= $requiredTime) {
                        // Chia lấy phần nguyên để xem khoảng hở này nhét vừa khít tối đa bao nhiêu suất chiếu
                        $totalAvailableSlots += floor($freeMinutes / $requiredTime);
                    }

                    // Tịnh tiến mốc thời gian cuối sang điểm kết thúc của suất hiện tại kèm thời gian dọn rạp
                    $lastEndTime = $showtimeEnd->copy()->addMinutes($cleaningTime);
                }

                // KIỂM TRA BỔ SUNG: Rà soát khoảng trống cuối cùng từ sau suất chiếu muộn nhất đến khi rạp đóng cửa đóng đèn
                $finalFreeMinutes = $lastEndTime->diffInMinutes($closeTime, false);
                if ($finalFreeMinutes >= $requiredTime) {
                    $totalAvailableSlots += floor($finalFreeMinutes / $requiredTime);
                }
            }
        }

        // Đóng gói tổng số lượng slot trống tìm thấy và phản hồi về định dạng JSON cho AJAX tiếp nhận
        return response()->json([
            'total_slots' => (int) $totalAvailableSlots
        ]);
    }

    /** Hướng giải quyết cập nhật phim theo nghiệp vụ rạp chiếu
     * ae có thể tham khảo =)
     * Cập nhật thông tin phim theo đúng nghiệp vụ rạp chiếu.
     *
     * QUY TẮc KHÓA TRƯỜNG THEO STATUS:
     *   COMING_SOON  → có thể sửa cả release_date và end_date
     *   NOW_SHOWING  → khóa release_date (phim đang chiếu, không thể đổi ngày khởi chiếu)
     *   ENDED        → khóa cả release_date và end_date
     *
     * Logic khóa được xử lý trong UpdateFilmRequest::prepareForValidation()
     */

    public function update(UpdateFilmRequest $request, $id)
    {
        $movie  = Movie::findOrFail($id);
        $status = $request->input('status');

        // ── Xử lý Poster ────────────────────────────────────────────────────
        if ($request->hasFile('poster')) {
            if (!empty($movie->poster_url)) {
                Storage::disk('public')->delete($movie->poster_url);
            }
            $posterPath = $request->file('poster')->store('poster_film', 'public');
        } else {
            $posterPath = $movie->poster_url;
        }

        // ── Xử lý Banner ────────────────────────────────────────────────────
        if ($request->hasFile('banner')) {
            if (!empty($movie->banner_url)) {
                Storage::disk('public')->delete($movie->banner_url);
            }
            $bannerPath = $request->file('banner')->store('banner_film', 'public');
        } else {
            $bannerPath = $movie->banner_url;
        }

        // ── Xây dựng payload cập nhật ─────────────────────────────────────
        $payload = [
            'title'            => $request->title,
            'slug'             => Str::slug($request->title) . '-' . time(),
            'original_title'   => $request->original_title,
            'description'      => $request->description,
            'duration_minutes' => $request->duration_minutes,
            'status'           => $status,
            'country'          => $request->country,
            'language'         => $request->language,
            'subtitle'         => $request->subtitle,
            'director'         => $request->director,
            'age_rating'       => $request->age_rating,
            'cast'             => $request->cast,
            'poster_url'       => $posterPath,
            'banner_url'       => $bannerPath,
            'trailer_url'      => $request->trailer_url,
        ];

        // ── Áp dụng quy tắc khóa ngày theo status ────────────────────────
        // prepareForValidation() đã override giá trị trong $request,
        // nên chỉ cần gán lại ở đây — giá trị luôn an toàn.
        $payload['release_date'] = $request->release_date; // giữ nguyên DB nếu NOW_SHOWING/ENDED
        $payload['end_date']     = $request->end_date;     // giữ nguyên DB nếu ENDED

        // ── Thực hiện cập nhật ────────────────────────────────────────────
        $movie->update($payload);

        // ── Cập nhật thể loại ─────────────────────────────────────────────
        $movie->genres()->sync($request->genres ?? []);

        $oldMovie = $movie->getOriginal();

        DB::table('audit_logs')->insert([
            'user_id'     => auth()->id(),
            'action'      => 'update_movie',
            'entity_name' => 'movies',
            'entity_id'   => (string) $movie->id,

            'old_value'   => json_encode($oldMovie),

            'new_value'   => json_encode($payload),

            'created_at'  => now(),
        ]);

        return redirect()->route('admin.film')
            ->with('success', 'Cập nhật phim thành công!');
    }

    /**
     * Hiển thị trang xác nhận ngừng chiếu phim (MVC thuần)
     */
    public function confirmStop(Request $request, $id)
    {
        // Lấy thông tin phim theo ID
        $movie = Movie::findOrFail($id);

        // Lấy danh sách suất chiếu trong tương lai của phim này (chưa bị hủy)
        $showtimes = Showtime::where('movie_id', $id)
            ->where('start_time', '>', now())
            ->where('status', '!=', 'CANCELLED');

        // Đếm số lượng suất chiếu tương lai
        $showtimeCount = $showtimes->count();

        // Đếm số lượng đơn đặt vé tương lai (PAID hoặc PENDING) của các suất chiếu này
        $bookingCount = Booking::whereIn('showtime_id', $showtimes->pluck('id'))
            ->whereIn('status', ['PAID', 'PENDING'])
            ->count();

        // Trả về view Blade xác nhận kèm dữ liệu thống kê ảnh hưởng
        return view('admin.film.confirm_stop', compact('movie', 'showtimeCount', 'bookingCount'));
    }

    // Thay đổi trạng thái của phim   
    // Thay đổi trạng thái của phim
    public function toggleStatus(Request $request, $id)
    {
        $movie = Movie::findOrFail($id);
        $action = $request->toggle_action;

        // Xử lý luồng ngừng chiếu phim (ENDED)
        if ($action === 'stop') {

            // Tạo một mảng để chứa danh sách các đơn hàng cần gửi mail sau khi cập nhật xong DB
            $bookingsToEmail = [];

            DB::transaction(function () use ($movie, $id, &$bookingsToEmail) {

                // 1. Cập nhật trạng thái phim thành ENDED và gán ngày kết thúc chiếu là hôm nay
                $movie->update([
                    'status' => 'ENDED',
                    'end_date' => now()->toDateString()
                ]);

                // 2. Tìm tất cả các suất chiếu tương lai của bộ phim này
                $showtimes = Showtime::where('movie_id', $id)
                    ->where('start_time', '>', now())
                    ->where('status', '!=', 'CANCELLED')
                    ->get();

                $showtimeIds = $showtimes->pluck('id');

                // 3. Chuyển trạng thái của các suất chiếu tương lai đó thành CANCELLED (Đã hủy)
                Showtime::whereIn('id', $showtimeIds)
                    ->update(['status' => 'CANCELLED']);

                // 4. Lấy các đơn đặt vé (PAID hoặc PENDING) liên quan đến các suất chiếu bị hủy
                $bookings = Booking::whereIn('showtime_id', $showtimeIds)
                    ->whereIn('status', ['PAID', 'PENDING'])
                    ->with(['user', 'showtime.movie'])   // ← Thêm dòng này
                    ->get();



                // 5. Hủy các đơn hàng và chuyển trạng thái thanh toán của đơn PAID sang REFUNDED (hoàn tiền)
                foreach ($bookings as $b) {
                    $b->status = 'CANCELLED';

                    if ($b->payment_status === 'PAID') {
                        $b->payment_status = 'REFUNDED';

                        $refundAmount = (int) round((float) ($b->final_amount ?? 0));
                        if ($refundAmount > 0 && $b->user_id) {
                            $coin = Coin::firstOrCreate(
                                ['user_id' => $b->user_id],
                                ['balance' => 0]
                            );

                            $coin->balance = (int) $coin->balance + $refundAmount;
                            $coin->save();
                        }
                    }

                    $b->save();

                    // KHÔNG gửi mail trực tiếp ở đây nữa. Gom đơn hàng vào mảng tạm
                    if ($b->user && $b->user->email) {
                        $userId = $b->user_id;

                        if (!isset($bookingsToEmail[$userId])) {
                            $bookingsToEmail[$userId] = [
                                'user' => $b->user,
                                'bookings' => []
                            ];
                        }
                        $bookingsToEmail[$userId]['bookings'][] = $b;


                    }
                }
            });

            // --- ĐƯA LUỒNG GỬI MAIL RA NGOÀI TRANSACTION VÀ VÒNG LẶP CẬP NHẬT DB ---
            // Hệ thống sẽ gửi tuần tự, nếu có độ trễ nhỏ giữa các mail cũng không làm ảnh hưởng đến dữ liệu DB
            foreach ($bookingsToEmail as $data) {
                try {
                    Mail::to($data['user']->email)->send(new FilmCancelledNotification($data['bookings']));

                    // Thêm một khoảng trễ cực nhỏ (0.5 giây) để tránh việc ép Mail Server của Google nhận request quá dồn dập
                    usleep(500000);
                } catch (\Exception $e) {
                    Log::error("Lỗi gửi mail cho đơn hàng số " . $data['bookings']->id . ": " . $e->getMessage());
                }
            }

            // Sau khi xử lý xong, redirect về trang danh sách phim kèm thông báo thành công
            return redirect()->route('admin.film')->with('success', 'Đã chuyển phim sang trạng thái Ngừng chiếu, các suất chiếu và vé liên quan đã được xử lý hủy.');
        }
    }



    public function restore($id)
    {

        //lấy id movie
        $movie = Movie::findOrFail($id);
        if ($movie) {
            return view('admin.film.restore', compact('movie'));
        }
    }

    public function confirmrecovery($id, Request $request)
    {
        // kiểm tra có xác nhận hay ko
        $action = $request->toggle_action;
        if ($action == 'resume') {
            $movie = Movie::findOrFail($id);
            //nếu id tồn tại
            if ($movie) {
                //bắt đầu cập nhật trạng thái
                Movie::where('id', $id)->update(
                    [
                        //cập nhật trạng thái
                        'status' => 'COMING_SOON',
                        'release_date' => now()->addDays(3)->toDateString(),
                        'end_date' => null,
                    ],
                );
            }
            return redirect()->route('admin.film')->with('success', 'Phim đã khôi phục thành công.');
        }
        return redirect()->route('admin.film');
    }
}
