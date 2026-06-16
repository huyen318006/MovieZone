<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\FilmManageController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SepayController;
use App\Http\Controllers\ShowtimeController;
use App\Http\Controllers\TicketController;
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
    $showingMovies = Movie::where('status', 'NOW_SHOWING')->get();
    $upcomingMovies = Movie::where('status', 'COMING_SOON')->get();

    return view('home', compact('showingMovies', 'upcomingMovies'));
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
Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');
/* ---------------------END---------- */

// Trang chi tiết phim
Route::get('/movie-detail', function () {
    return redirect()->route('movies');
})->name('movie.detail.legacy');

Route::get('/movies/{slug}', [MovieController::class, 'show'])->name('movie.detail');
// đánh giá phim
Route::middleware('auth')->group(function () {
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
Route::get('/showtimes/{showtime}/select', [ShowtimeController::class, 'select'])->name('showtimes.select');

// Rạp chiếu
Route::get('/cinemas', function () {
    return view('cinema.index');
})->name('cinemas');

// Chọn ghế

// Booking payment flow (dùng SepayController cho QR + polling)
Route::prefix('booking')->name('booking.')->group(function () {
    Route::get('/demo-bill', [SepayController::class, 'demoBill'])->name('demo-bill');
    // Route checkout cũ đã chuyển sang BookingController::checkout
    // Route::post('/checkout', [SepayController::class, 'bookingCheckout'])->name('checkout');
    Route::get('/payment/{orderCode}', [SepayController::class, 'bookingPayment'])->name('payment');
    Route::get('/check/{orderCode}', [SepayController::class, 'checkStatus'])->name('check');
    Route::get('/bill/{orderCode}', [SepayController::class, 'bookingBill'])->name('bill');
});

// route Sepay (gói nạp tiền)
Route::prefix('sepay')->name('sepay.')->group(function () {
    Route::get('/', [SepayController::class, 'index'])->name('index');
    Route::post('/checkout/{packageId}', [SepayController::class, 'checkout'])->name('checkout');
    Route::get('/payment/{orderCode}', [SepayController::class, 'payment'])->name('payment');
    Route::get('/check/{orderCode}', [SepayController::class, 'checkStatus'])->name('check');
    Route::get('/bill/{orderCode}', [SepayController::class, 'bill'])->name('bill');
});

// Khuyến mãi
Route::get('/promotions', function () {
    return view('promotion.index');
})->name('promotions');

// Tin tức
Route::get('/news', function () {
    return view('news.index');
})->name('news');

Route::get('/news-detail', function () {
    return view('news.detail');
})->name('news.detail');

// Hồ sơ người dùng
Route::middleware('auth')->group(function () { // chưa đăng nhập thì không xem được hồ sơ
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    // dòng mới: Trang hiển thị FORM chỉnh sửa hồ sơ
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    // cập nhật hồ sơ
    Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
    // đổi mkhau
    Route::put('/profile/password', [ProfileController::class, 'changePassword'])->name('profile.password.change');
});
// Vé đã mua
Route::middleware('auth')->group(function () {
    Route::get('/my-tickets', [TicketController::class, 'index'])->name('tickets');
});



/* --------------------- KHU VỰC CỦA ADMIN ------------------ */
Route::get('/admin', function () {
    return view('admin.dashboard');
})->name('admin.dashboard');

// Quản lý phim - FILM MANAGEMENT
Route::controller(FilmManageController::class)->group(function () {
    Route::get('/admin/film/management', 'listmovie')->name('admin.film');
    Route::get('/admin/film/store','formadd')->name('admin.film.add');
    Route::post('/admin/filmstore','store')->name('admin.store.film');
    //sửa thông tin film
    Route::get('/admin/view/update/film/{id}','viewupdate')->name('admin.view.update.film');
    Route::post('/admin/updatefilm/{id}','update')->name('update.film');

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


// Route::get('/admin/my-tickets', function () {
//     return view('ticket.index');
// })->name('admin.tickets');
// Route::get('/my-tickets', function () {
//     return view('ticket.index');
// })->name('tickets');

/* --------------------- UC-08 & UC-11: ĐẶT VÉ ------------------ */
Route::middleware(['auth'])->group(function () {
    // UC-08: Hiển thị màn hình chọn ghế (Sửa tên cho đồng bộ)
    Route::get('/booking/showtime/{showtime_id}/seat', [BookingController::class, 'showSeats'])->name('booking.seat');

    // UC-08: AJAX xử lý giữ/hủy ghế theo thời gian thực (5 phút)
    Route::post('/booking/hold-seat', [BookingController::class, 'holdSeat'])->name('booking.holdSeat');

    // UC-08 -> UC-11: Submit danh sách ghế đã chọn vào Session
    Route::post('/booking/seats/submit', [BookingController::class, 'submitSeats'])->name('booking.seats.submit');

    // UC-09:COMBO
    Route::get('/booking/combo', [BookingController::class, 'showCombo'])->name('booking.combo');
    Route::post('/booking/combo', [BookingController::class, 'saveCombo'])->name('booking.combo.save');

    // UC-11: Hiển thị màn hình xác nhận đặt vé
    Route::get('/booking/confirm', [BookingController::class, 'showConfirm'])->name('booking.confirm');

    // UC-11: Nút "Xác nhận đặt vé" -> Tạo DB -> Chuyển thanh toán
    Route::post('/booking/checkout', [BookingController::class, 'checkout'])->name('booking.checkout');
});
