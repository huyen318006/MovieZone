<?php

use App\Models\Movie;


class StaffBookingService
{
    
    public function __construct()
    {
        
    }

    
    //hàm lấy ra film
    public function getMovies()
    {
        return Movie::all();
    }

    // // hàm lấy ra lịch chiếu
    // public function getShowtimes($movieId)
    // {
    //     return Showtime::where('movie_id', $movieId)->get();
    // }

    // // hàm lấy ra ghế
    // public function getSeats($showtimeId)
    // {
    //     return Seat::where('showtime_id', $showtimeId)->get();
    // }

    // // hàm tạo booking
    // public function createBooking($bookingData)
    // {
    //     return Booking::create($bookingData);
    // }
}

?>