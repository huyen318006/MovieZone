<?php

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

// Thanh toán
Route::get('/checkout', function () {
    return view('booking.checkout');
})->name('booking.checkout');

// Đặt vé thành công
Route::get('/booking-success', function () {
    return view('booking.success');
})->name('booking.success');

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

// Đăng nhập / Đăng ký
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');