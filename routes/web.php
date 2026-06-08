<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\SepayController;
use Illuminate\Support\Facades\Route;

// Trang chủ
Route::get('/', function () {
    $showingMovies = [
        ['title' => 'Avatar 2', 'rating' => '8.9', 'poster' => 'avatar.jpg'],
        ['title' => 'Dune Part Two', 'rating' => '8.7', 'poster' => 'dune.jpg'],
        ['title' => 'John Wick 4', 'rating' => '8.4', 'poster' => 'johnwick.jpg'],
        ['title' => 'Oppenheimer', 'rating' => '8.8', 'poster' => 'oppenheimer.jpg'],
    ];
    return view('home', compact('showingMovies'));
})->name('home');
 /*------------ Đăng nhập / Đăng ký / forgot*------------------*/
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
/*---------------------END LOGIN THƯỜNG---------- */

/* ----------------LOGIN GOOGLE------------------ */
//test đăng nhập bằng google
Route::controller(GoogleController::class)->group(function () {
    Route::get('auth/google', 'redirectToGoogle')->name('auth.google');
    Route::get('auth/google/callback', 'handleGoogleCallback');
});
Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');
/*---------------------END---------- */

// Trang chi tiết phim
Route::get('/movie-detail', function () {
    return view('movie.detail');
})->name('movie.detail');

// Danh sách phim
Route::get('/movies', function () {
    return view('movie.index');
})->name('movies');

// Lịch chiếu
Route::get('/showtimes', function () {
    return view('showtime.index');
})->name('showtimes');

// Rạp chiếu
Route::get('/cinemas', function () {
    return view('cinema.index');
})->name('cinemas');

// Chọn ghế
Route::get('/booking-seat', function () {
    return view('booking.seat');
})->name('booking.seat');

// Booking payment flow
Route::prefix('booking')->name('booking.')->group(function () {
    Route::post('/checkout', [SepayController::class, 'bookingCheckout'])->name('checkout');
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
Route::get('/profile', function () {
    return view('profile.index');
})->name('profile');

// Vé đã mua
Route::get('/my-tickets', function () {
    return view('ticket.index');
})->name('tickets');
