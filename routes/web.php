<?php

use App\Http\Controllers\Admin\AccountManageController;
use App\Http\Controllers\Admin\ArticleManageController;
use App\Http\Controllers\Admin\CustomerMembershipController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\BannerManageController;
use App\Http\Controllers\Admin\BookingManageController;
use App\Http\Controllers\Admin\RoomManageController;
use App\Http\Controllers\Admin\ProductManageController;
use App\Http\Controllers\Admin\ComboManageController;
use App\Http\Controllers\Admin\VoucherManageController;
use App\Http\Controllers\Admin\PromotionManageController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Admin\SeatManageController;
use App\Http\Controllers\Admin\ShowtimeManageController;
use App\Http\Controllers\CoinController;
use App\Http\Controllers\FilmManageController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SepayController;
use App\Http\Controllers\ShowtimeController;
use App\Http\Controllers\staff\BookTicketsController;
use App\Http\Controllers\Staff\sellproduct\SellproductController;
use App\Http\Controllers\Staff\StaffDashboardController;
use App\Http\Controllers\Staff\StaffArticleController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\ChatbotController;
use App\Models\Banner;
use App\Models\Movie;
use Illuminate\Support\Facades\Route;

// Trang chủ
// Route::get('/', function () {
//     $showingMovies = [
//         ['title' => 'Avatar 2', 'rating' => '8.9', 'poster' => 'avatar.jpg'],
//         ['title' => 'Dune Part Two', 'rating' => '8.7', 'poster' => 'dune.jpg'],
//         ['title' => 'John Wick 4', 'rating' => '8.4', 'poster' => 'johnwick.jpg'],
//         ['title' => 'Oppenheimer', 'rating' => '8.8', 'poster' => 'oppenheimer.jpg'],
//     ];

//     return view('home', compact('showingMovies'));
// })->name('home');
Route::get('/', function () {
    $now = now();

    // Lấy danh sách banner có trạng thái ACTIVE, vị trí HOME_TOP và còn thời hạn hiển thị
    $banners = Banner::where('status', 'ACTIVE')
        ->where(function ($q) {
            $q->whereNull('position')->orWhere('position', 'HOME_TOP');
        })
        ->where(function ($q) use ($now) {
            $q->whereNull('start_date')->orWhere('start_date', '<=', $now);
        })
        ->where(function ($q) use ($now) {
            $q->whereNull('end_date')->orWhere('end_date', '>=', $now);
        })
        ->orderBy('created_at', 'desc')
        ->limit(3)
        ->get();

    $showingMovies = Movie::where('status', 'NOW_SHOWING')->get();
    $upcomingMovies = Movie::where('status', 'COMING_SOON')->get();

    return view('home', compact('banners', 'showingMovies', 'upcomingMovies'));
})->name('home');
/* ------------ Đăng nhập / Đăng ký / forgot*------------------ */
Route::controller(AuthController::class)->group(function () {
    // Đăng nhập thông thường
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');
    Route::post('/login', 'login')->name('login.post');
    // Đăng ký
    Route::get('/register', function () {
        return view('auth.register');
    })->name('register');
    Route::post('/register', 'register')->name('register.post');
    // Quên mật khẩu
    Route::get('/forgot-password', function () {
        return view('auth.forgot');
    })->name('password.request');
    Route::post('/forgot-password', 'forgotPassword')->name('password.email');
    Route::get('reset-password/{token}', [AuthController::class, 'reset_password'])
        ->name('password.reset');
    Route::post('reset-password/{token}', [AuthController::class, 'update_password'])
        ->name('password.update');
});
/* ---------------------END LOGIN THƯỜNG---------- */

/* ----------------LOGIN GOOGLE------------------ */
// test đăng nhập bằng google
Route::controller(GoogleController::class)->group(function () {
    Route::get('auth/google', 'redirectToGoogle')->name('auth.google');
    Route::get('auth/google/callback', 'handleGoogleCallback');
});
Route::match(['GET', 'POST'], '/logout', [AuthController::class, 'logout'])
    ->name('logout');
/* ---------------------END---------- */

/* --------------------- Coin------------------ */
Route::middleware('tab.auth')->group(function () {
    Route::get('/coin/{id}', [CoinController::class, 'coinIndex'])->name('coin.index');
    Route::post('/coin/{id}/checkin', [CoinController::class, 'checkin'])->name('coin.checkin');

});

// Trang chi tiết phim
Route::get('/movie-detail', function () {
    return redirect()->route('movies');
})->name('movie.detail.legacy');

Route::get('/movies/{slug}', [MovieController::class, 'show'])->name('movie.detail');
// đánh giá phim
Route::middleware('tab.auth')->group(function () {
    // Luồng gửi đánh giá - Gọi vào storeReview của ReviewController
    Route::post('/movies/{movie}/review', [ReviewController::class, 'store'])->name('movies.review.store');

    //  Sửa/Xóa đánh giá viết ở ReviewController
    Route::put('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
});
// Danh sách phim
Route::get('/movies', [MovieController::class, 'index'])->name('movies');

// Lịch chiếu
Route::get('/showtimes', [ShowtimeController::class, 'index'])->name('showtimes');
// Chọn suất chiếu cụ thể - yêu cầu đăng nhập (cần tab_token để giữ ghế)
Route::middleware('tab.auth')->get('/showtimes/{showtime}/select', [ShowtimeController::class, 'select'])->name('showtimes.select');



// Chọn ghế

// Booking payment flow (dùng SepayController cho QR + polling) - yêu cầu đăng nhập
Route::middleware('tab.auth')->prefix('booking')->name('booking.')->group(function () {
    Route::get('/demo-bill', [SepayController::class, 'demoBill'])->name('demo-bill');
    // Route checkout cũ đã chuyển sang BookingController::checkout
    // Route::post('/checkout', [SepayController::class, 'bookingCheckout'])->name('checkout');
    Route::get('/payment/{orderCode}', [SepayController::class, 'bookingPayment'])->name('payment');
    Route::get('/check/{orderCode}', [SepayController::class, 'checkStatus'])->name('check');
    Route::get('/bill/{orderCode}', [SepayController::class, 'bookingBill'])->name('bill');
});

// route Sepay (gói nạp tiền) - yêu cầu đăng nhập
Route::middleware('tab.auth')->prefix('sepay')->name('sepay.')->group(function () {
    Route::get('/', [SepayController::class, 'index'])->name('index');
    Route::post('/checkout/{packageId}', [SepayController::class, 'checkout'])->name('checkout');
    Route::get('/payment/{orderCode}', [SepayController::class, 'payment'])->name('payment');
    Route::get('/check/{orderCode}', [SepayController::class, 'checkStatus'])->name('check');
    Route::get('/bill/{orderCode}', [SepayController::class, 'bill'])->name('bill');
});

// Khuyến mãi
Route::get('/promotions', [PromotionController::class, 'index'])->name('promotions');
Route::get('/promotions/{promotion}', [PromotionController::class, 'show'])->name('promotion.show');

// Tin tức

Route::get('/news', [NewsController::class, 'index'])->name('news');
Route::get('/news/{slug}', [NewsController::class, 'show'])->name('news.detail');

// Hồ sơ người dùng
Route::middleware('tab.auth')->group(function () { // chưa đăng nhập thì không xem được hồ sơ
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    // dòng mới: Trang hiển thị FORM chỉnh sửa hồ sơ
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    // cập nhật hồ sơ
    Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
    // đổi mkhau
    Route::put('/profile/password', [ProfileController::class, 'changePassword'])->name('profile.password.change');
});
// Vé đã mua
Route::middleware('tab.auth')->group(function () {
    Route::get('/my-tickets', [TicketController::class, 'index'])->name('my-tickets.index');
    Route::get('/my-tickets/{id}', [TicketController::class, 'detail'])->name('my-tickets.show');
});

// Membership Customer
Route::middleware('tab.auth')->group(function () {
    Route::get('/membership', [MembershipController::class, 'index'])->name('membership.index');
    Route::get('/membership/history', [MembershipController::class, 'history'])->name('membership.history');
});



/* --------------------- KHU VỰC CỦA ADMIN ------------------ */
Route::middleware(['tab.auth', 'admin'])
    ->get('/admin', [AdminDashboardController::class, 'index'])
    ->name('admin.dashboard');
// Quản lý phim - FILM MANAGEMENT
Route::middleware(['tab.auth', 'admin'])->controller(FilmManageController::class)->group(function () {
    Route::get('/admin/film/management', 'listmovie')->name('admin.film');
    Route::get('/admin/film/store','formadd')->name('admin.film.add');
    Route::post('/admin/filmstore','store')->name('admin.store.film');
    //sửa thông tin film
    Route::get('/admin/view/update/film/{id}','viewupdate')->name('admin.view.update.film');
    Route::post('/admin/updatefilm/{id}','update')->name('update.film');
    Route::post('/movies/check-slots',  'apiCheckSlots')->name('admin.movies.check-slots');

    //bắt đầu phần khó liên quan đến trạng thái thanh toán suất chiếu cụ thể

    // Route GET: Trang xác nhận ngừng chiếu phim (MVC thuần)
    Route::get('/admin/film/{id}/confirm-stop', [FilmManageController::class, 'confirmStop'])
        ->name('admin.film.confirm_stop');

    // Route POST: Thực hiện thay đổi trạng thái phim (Ngừng chiếu)
    Route::post('/admin/film/{id}/toggle-status', [FilmManageController::class, 'toggleStatus'])
        ->name('admin.film.toggle_status');

        //xác nhận khôi phục phim
        Route::get('/admin/film/{id}/restore','restore')->name('restore.film');
    //bắt đầu khôi phục
    //xác nhận khôi phục phim
    Route::post('confirm_recovery/{id}/file', 'confirmrecovery')->name('confirm.recovery');


});



// Quản lý phòng chiếu - ROOM MANAGEMENT
Route::middleware(['tab.auth', 'admin'])->prefix('admin/rooms')->name('admin.rooms.')->group(function () {
    Route::get('/', [RoomManageController::class, 'index'])->name('index');
    Route::get('/create', [RoomManageController::class, 'create'])->name('create');
    Route::post('/', [RoomManageController::class, 'store'])->name('store');
    Route::get('/{room}/edit', [RoomManageController::class, 'edit'])->name('edit');
    Route::put('/{room}', [RoomManageController::class, 'update'])->name('update');
    Route::post('/{room}/hide', [RoomManageController::class, 'hide'])->name('hide');
    Route::post('/{room}/restore', [RoomManageController::class, 'restore'])->name('restore');
    Route::get('/{room}/seats', [RoomManageController::class, 'seats'])->name('seats');
});

// QUẢN LÝ SUẤT CHIẾU - SHOWTIME MANAGEMENT
Route::middleware(['tab.auth', 'admin'])->controller(ShowtimeManageController::class)->group(function () {
    // Danh sách suất chiếu
    Route::get('/admin/showtime/management', 'listShowtime')->name('admin.showtime');
    // Form thêm suất chiếu
    Route::get('/admin/showtime/store', 'formAdd')->name('admin.showtime.add');
    // Lưu suất chiếu
    Route::post('/admin/showtime/store', 'store')->name('admin.store.showtime');
    // Form sửa suất chiếu
    Route::get('/admin/view/update/showtime/{id}', 'viewUpdate')->name('admin.view.update.showtime');
    // Cập nhật suất chiếu
    Route::post('/admin/update/showtime/{id}', 'update')->name('update.showtime');
    // Xem chi tiết suất chiếu
    Route::get('/admin/showtime/detail/{id}', 'detail')->name('detail.showtime');
    // Xác nhận hủy suất chiếu
    Route::get('/admin/showtime/{id}/confirm-cancel', 'confirmCancel')->name('admin.showtime.confirm_cancel');
    // Thực hiện hủy suất chiếu
    Route::post('/admin/showtime/{id}/cancel', 'cancel')->name('admin.showtime.cancel');
    // API kiểm tra trùng lịch
    Route::post('/showtimes/check-conflict', 'checkConflict')->name('admin.showtimes.check-conflict');
    // API lấy thông tin phim (wizard tạo suất chiếu - Bước 1)
    Route::post('/admin/showtime/api/movie-info', 'apiGetMovieInfo')->name('admin.showtime.api.movie_info');
    // API lấy danh sách phòng + lịch chiếu trong ngày (wizard - Bước 3)
    Route::post('/admin/showtime/api/room-schedule', 'apiGetRoomSchedule')->name('admin.showtime.api.room_schedule');
    // API lấy timeline chi tiết của 1 phòng (wizard - Bước 3 khi click phòng)
    Route::post('/admin/showtime/api/room-timeline', 'apiGetRoomTimeline')->name('admin.showtime.api.room_timeline');
    // API kiểm tra phòng trống khả dụng & trùng lịch chi tiết (màn hình update)
    Route::post('/admin/showtime/api/check-rooms-availability', 'apiCheckRoomsAvailability')->name('admin.showtime.api.check_rooms_availability');
    // API quét phòng & lấy danh sách khung giờ trống khả dụng (màn hình tạo mới 3 bước)
    Route::post('/admin/showtime/api/available-slots', 'apiGetAvailableSlots')->name('admin.showtime.api.available_slots');
});
// =====================================================================//

// Route::get('/admin/my-tickets', function () {
//     return view('ticket.index');
// })->name('admin.tickets');
// Route::get('/my-tickets', function () {
//     return view('ticket.index');
// })->name('tickets');

/* --------------------- UC-08 & UC-11: ĐẶT VÉ ------------------ */
Route::middleware(['tab.auth'])->group(function () {
    // UC-08: Hiển thị màn hình chọn ghế (Sửa tên cho đồng bộ)
    Route::get('/booking/showtime/{showtime_id}/seat', [BookingController::class, 'showSeats'])->name('booking.seat');

    // UC-08: AJAX xử lý giữ/hủy ghế theo thời gian thực (5 phút)
    Route::post('/booking/hold-seat', [BookingController::class, 'holdSeat'])->name('booking.holdSeat');

    // UC-08 -> UC-11: Submit danh sách ghế đã chọn vào Session
    Route::post('/booking/seats/submit', [BookingController::class, 'submitSeats'])->name('booking.seats.submit');

    // UC-09:COMBO
    Route::get('/booking/combo', [BookingController::class, 'showCombo'])->name('booking.combo');
    Route::post('/booking/combo', [BookingController::class, 'saveCombo'])->name('booking.combo.save');

    // UC-10: VOUCHER
    Route::post('/booking/voucher/apply', [VoucherController::class, 'apply'])->name('voucher.apply');
    Route::post('/booking/voucher/remove', [VoucherController::class, 'remove'])->name('voucher.remove');

    // COIN REDEMPTION: Áp dụng / Huỷ xu tại trang xác nhận
    Route::post('/booking/coin/apply', [BookingController::class, 'applyCoin'])->name('booking.coin.apply');
    Route::post('/booking/coin/remove', [BookingController::class, 'removeCoin'])->name('booking.coin.remove');

    // UC-11: Hiển thị màn hình xác nhận đặt vé
    Route::get('/booking/confirm', [BookingController::class, 'showConfirm'])->name('booking.confirm');

    // UC-11: Nút "Xác nhận đặt vé" -> Tạo DB -> Chuyển thanh toán
    Route::post('/booking/checkout', [BookingController::class, 'checkout'])->name('booking.checkout');

    // Hủy thanh toán và quay lại chọn ghế
    Route::post('/booking/cancel/{orderCode}', [BookingController::class, 'cancelBookingAndRelease'])->name('booking.cancel');
});

//UC-04 quan ly ghe
// Đặt trong group admin
Route::middleware(['tab.auth', 'admin'])->prefix('admin/seats')->name('admin.seats.')->group(function () {
    Route::get('/', [SeatManageController::class, 'index'])->name('index');
    Route::get('/create', [SeatManageController::class, 'create'])->name('create');
    Route::post('/store', [SeatManageController::class, 'store'])->name('store');

    Route::get('/{id}/edit', [SeatManageController::class, 'edit'])->name('edit');
    Route::put('/{id}', [SeatManageController::class, 'update'])->name('update');

    // Nút toggle khóa/mở khóa
    Route::post('/{id}/toggle-lock', [SeatManageController::class, 'toggleLock'])->name('toggle_lock');

    // Xóa mềm
    Route::delete('/{id}', [SeatManageController::class, 'destroy'])->name('destroy');

    // Xóa nhiều
    Route::post('/destroy-many', [SeatManageController::class, 'destroyMany'])->name('destroy_many');

    // Thêm hàng loạt
    Route::post('/store-batch', [SeatManageController::class, 'storeBatch'])->name('store_batch');

    // Khóa/Mở khóa nhiều ghế (toggle)
    Route::post('/toggle-lock-many', [SeatManageController::class, 'toggleLockMany'])->name('toggle_lock_many');

    // Đổi loại ghế hàng loạt theo hàng (VIP ↔ STANDARD ↔ COUPLE)
    Route::post('/bulk-update-type', [SeatManageController::class, 'bulkUpdateType'])->name('bulk_update_type');

});



// Quản lý tài khoản - account management
Route::middleware(['tab.auth', 'admin'])
    ->controller(AccountManageController::class)
    ->group(function () {

        // Redirect /account/management/ về trang danh sách (tránh 404)
        Route::get('/account/management', function () {
            return redirect()->route('admin.list_account');
        })->name('admin.account.management');

        Route::get('/account/management/', function () {
            return redirect()->route('admin.list_account');
        });

        Route::get('/account/management/admin', 'listaccount')
            ->name('admin.list_account');

        Route::get('/account_detail/management/{id}', 'detailaccount')
            ->name('admin.detail.account');

        Route::post('/admin/users/{id}/lock', 'lock')
            ->name('admin.users.lock');

        Route::get('/admin/users/{id}/open', 'open')
            ->name('admin.users.open');
            //phần update account management
        Route::get('/admin/users/profile/update/{id}', 'profileAccount')
            ->name('admin.profile.account.admins');
        Route::post('/admin/users/profile/update/{id}', 'update')
            ->name('admin.profile.account.admins.update');
              //đổi mật khẩu account
        Route::put('/admin/users/password/update/{id}', 'updatepassword')
            ->name('admin.profile.account.admins.updatepassword');
//phần nâng hạ quyền account management
        Route::put('/admin/users/promote', 'promote')
            ->name('admin.users.promote');

    Route::put('/admin/users/demote', 'demote')
        ->name('admin.users.demote');

    Route::put('/admin/users/demote-admin', 'demoteAdmin')
        ->name('admin.users.demote.admin');

        //thêm tài khoản
         Route::get('/account/management/create', 'createAccount')
        ->name('admin.create_account');
        // Lưu tài khoản mới
        Route::post('/account/management/create', 'storeAccount')
            ->name('admin.account.store_account');

    });

    /* --------------------- ADMIN COMBO, PRODUCTS, VOUCHERS, PROMOTIONS ------------------ */
    Route::middleware(['tab.auth', 'admin'])->group(function () {
        Route::resource('admin/products', ProductManageController::class)->names([
            'index' => 'admin.products.index',
            'create' => 'admin.products.create',
            'store' => 'admin.products.store',
            'edit' => 'admin.products.edit',
            'update' => 'admin.products.update',
            'destroy' => 'admin.products.destroy',
        ]);
        Route::resource('admin/combos', ComboManageController::class)->names([
            'index' => 'admin.combos.index',
            'create' => 'admin.combos.create',
            'store' => 'admin.combos.store',
            'edit' => 'admin.combos.edit',
            'update' => 'admin.combos.update',
            'destroy' => 'admin.combos.destroy',
        ]);
        Route::resource('admin/vouchers', VoucherManageController::class)->names([
            'index' => 'admin.vouchers.index',
            'create' => 'admin.vouchers.create',
            'store' => 'admin.vouchers.store',
            'edit' => 'admin.vouchers.edit',
            'update' => 'admin.vouchers.update',
            'destroy' => 'admin.vouchers.destroy',
        ]);
        Route::resource('admin/promotions', PromotionManageController::class)->names([
            'index' => 'admin.promotions.index',
            'create' => 'admin.promotions.create',
            'store' => 'admin.promotions.store',
            'edit' => 'admin.promotions.edit',
            'update' => 'admin.promotions.update',
            'destroy' => 'admin.promotions.destroy',
        ]);
    });

/* --------------------- STAFF DASHBOARD ------------------ */
Route::middleware(['tab.auth', 'staff.permission:staff.dashboard'])
    ->get('/staff', [StaffDashboardController::class, 'index'])
    ->name('staff.dashboard');

/* --------------------- UC-STAFF-03: TRA CỨU BOOKING/VÉ ------------------ */
use App\Http\Controllers\Staff\BookingLookupController;

Route::middleware(['tab.auth', 'staff.permission:booking.lookup'])
    ->prefix('staff')
    ->name('staff.')
    ->group(function () {
        // Trang tra cứu booking (Blade)
        Route::get('/booking-lookup', [BookingLookupController::class, 'index'])
            ->name('booking-lookup');

        // API endpoints
        Route::get('/api/bookings/search', [BookingLookupController::class, 'search'])
            ->name('api.bookings.search');

        Route::get('/api/bookings/{id}', [BookingLookupController::class, 'detail'])
            ->name('api.bookings.detail');

        Route::get('/api/bookings/{id}/audit-logs', [BookingLookupController::class, 'auditLogs'])
            ->name('api.bookings.audit-logs');

        Route::get('/api/cinemas', [BookingLookupController::class, 'cinemas'])
            ->name('api.cinemas');
    });

/* --------------------- UC-STAFF-01: CHECK-IN VÉ QR ------------------ */
use App\Http\Controllers\Staff\CheckInController;

Route::middleware(['tab.auth', 'staff.permission:ticket.checkin'])
    ->prefix('staff')
    ->name('staff.')
    ->group(function () {
        // Trang check-in (Blade)
        Route::get('/check-in', [CheckInController::class, 'index'])
            ->name('check-in');

        // API endpoints
        Route::post('/api/check-in/scan', [CheckInController::class, 'scan'])
            ->name('api.checkin.scan');

        Route::post('/api/check-in/manual', [CheckInController::class, 'manual'])
            ->name('api.checkin.manual');

        Route::post('/api/check-in/confirm', [CheckInController::class, 'confirm'])
            ->name('api.checkin.confirm');

        Route::post('/api/check-in/confirm-batch', [CheckInController::class, 'confirmBatch'])
            ->name('api.checkin.confirm-batch');

        Route::get('/api/check-in/history', [CheckInController::class, 'history'])
            ->name('api.checkin.history');

        Route::get('/api/check-in/{bookingId}/download-pdf', [CheckInController::class, 'downloadPDF'])
            ->name('api.checkin.download-pdf');

        Route::get('/print-bill/{bookingCode}', [CheckInController::class, 'printBill'])
            ->name('print-bill');
    });

/* --------------------- UC-STAFF-04: HỖ TRỢ SỰ CỐ ĐẶT VÉ ------------------ */
use App\Http\Controllers\Staff\StaffIssueSupportController;

Route::middleware(['tab.auth', 'staff.permission:booking.lookup'])
    ->prefix('staff')
    ->name('staff.')
    ->group(function () {
        Route::get('/issue-support', [StaffIssueSupportController::class, 'index'])
            ->name('issue-support');

        Route::post('/api/issue-support/diagnose', [StaffIssueSupportController::class, 'diagnose'])
            ->name('api.issue-support.diagnose');
    });
/* UC-STAFF-05: BÁN VÉ */
Route::middleware(['tab.auth'])->group(function () {
    Route::get('/staff/sell-tickets', [BookTicketsController::class, 'index'])->name('staff.sell-tickets');
    Route::get('/staff/sell-seat/{id}', [BookTicketsController::class, 'sell_seat'])->name('staff.sell-seat');
    Route::get('/staff/sell-tickets/submitseat', [BookTicketsController::class, 'submitseat'])->name('staff.sell-tickets.submitseat');
    Route::post('/staff/sell-tickets/savecombo', [BookTicketsController::class, 'savecombo'])->name('staff.sell-tickets.savecombo');
    Route::get('/staff/sell-tickets/confirm', [BookTicketsController::class, 'confirm'])->name('staff.sell-tickets.confirm');
    Route::post('/staff/sell-tickets/checkout', [BookTicketsController::class, 'checkout'])->name('staff.sell-tickets.checkout');
    Route::get('/staff/sell-tickets/payment/{orderCode}', [BookTicketsController::class, 'payment'])->name('staff.sell-tickets.payment');
});

/* --------------------- UC-STAFF-06: bán sản phẩm lẻ ------------------ */
Route::middleware(['tab.auth'])->group(function () {
    Route::get('/staff/sell-products', [SellproductController::class, 'sell_products'])->name('staff.sell-products');
    Route::post('/staff/sell-products/order', [SellproductController::class, 'orderProducts'])->name('staff.sell-products.order');
    Route::post('/staff/sell-products/checkout', [SellproductController::class, 'checkout'])->name('staff.sell-products.checkout');
    Route::get('/staff/sell-products/payment/{orderCode}', [SellproductController::class, 'payment'])->name('staff.sell-products.payment');
    Route::post('/staff/sell-products/payment/{orderCode}/cash-confirm', [SellproductController::class, 'confirmCashPayment'])->name('staff.sell-products.cash-confirm');
    Route::get('/staff/sell-products/check-status/{orderCode}', [SellproductController::class, 'checkStatus'])->name('staff.sell-products.check-status');
    Route::get('/staff/sell-products/success/{orderCode}/{paymentMethod?}', [SellproductController::class, 'success'])->name('staff.sell-products.success');
    Route::get('/staff/sell-products/invoice/{orderCode}', [SellproductController::class, 'downloadInvoice'])->name('staff.sell-products.invoice');
});

/* STAFF: XEM BÀI VIẾT */
/* STAFF: BÀI VIẾT (XEM + SỬA) */
Route::middleware(['tab.auth'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/articles', [StaffArticleController::class, 'index'])->name('articles.index');
    Route::get('/articles/{id}', [StaffArticleController::class, 'show'])->name('articles.show');
    Route::get('/articles/{id}/edit', [StaffArticleController::class, 'edit'])->name('articles.edit');
    Route::put('/articles/{id}', [StaffArticleController::class, 'update'])->name('articles.update');
});



/* --------------------- UC-ADM-06: Quản lý booking (ĐẶT VÉ) ------------------ */
Route::middleware(['tab.auth', 'admin'])->group(function () {
    // Trang quản lý booking (view)
    Route::get('/admin/bookings', [BookingManageController::class, 'index'])->name('admin.bookings.index');

    // API danh sách booking có phân trang & bộ lọc
    Route::get('/admin/api/bookings', [BookingManageController::class, 'list'])->name('admin.bookings.list');

    // Chi tiết 1 đơn đặt vé
    Route::get('/admin/bookings/{id}', [BookingManageController::class, 'show'])->name('admin.bookings.show');

    // Thực hiện hủy đơn đặt vé
    Route::post('/admin/bookings/{id}/cancel', [BookingManageController::class, 'cancel'])->name('admin.bookings.cancel');

    // Hỗ trợ check-in vé
    Route::post('/admin/api/bookings/tickets/{ticket_id}/check-in', [BookingManageController::class, 'checkInTicket'])->name('admin.bookings.ticket.checkin');
});

// Chatbot API (Menu-based - Giai đoạn 1)
Route::post('/api/chatbot', ChatbotController::class)->name('api.chatbot');

Route::middleware(['tab.auth', 'admin'])->group(function () {
    Route::resource('admin/banners', BannerManageController::class)->names([
                'index' => 'admin.banners.index',
                'create' => 'admin.banners.create',
                'store' => 'admin.banners.store',
                'edit' => 'admin.banners.edit',
                'update' => 'admin.banners.update',
                'destroy' => 'admin.banners.destroy',
            ]);
});
/* --------------------- Quản lý Bài viết (Articles) ------------------ */
Route::middleware(['tab.auth', 'admin'])->group(function () {
    Route::get('/admin/articles', [ArticleManageController::class, 'index'])->name('admin.articles.index');
    Route::get('/admin/articles/create', [ArticleManageController::class, 'create'])->name('admin.articles.create');
    Route::post('/admin/articles', [ArticleManageController::class, 'store'])->name('admin.articles.store');
    Route::get('/admin/articles/{id}/edit', [ArticleManageController::class, 'edit'])->name('admin.articles.edit');
    Route::put('/admin/articles/{id}', [ArticleManageController::class, 'update'])->name('admin.articles.update');
    Route::delete('/admin/articles/{id}', [ArticleManageController::class, 'destroy'])->name('admin.articles.destroy');
});

/* --------------------- Quản lý Membership Admin ------------------ */
Route::middleware(['tab.auth', 'admin'])->group(function () {
    Route::get('/admin/memberships', [CustomerMembershipController::class, 'index'])->name('admin.memberships.index');
    Route::post('/admin/memberships/scan-expired', [CustomerMembershipController::class, 'scanExpired'])->name('admin.memberships.scan_expired');
    Route::get('/admin/memberships/{id}', [CustomerMembershipController::class, 'show'])->name('admin.memberships.show');
    Route::post('/admin/memberships/{id}/adjust-coin', [CustomerMembershipController::class, 'adjustCoin'])->name('admin.memberships.adjust_coin');
});
