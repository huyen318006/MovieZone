<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    public function index()
    {
        $bookings = Booking::with([
            'payment',
            'tickets',
            'showtime.movie',
            'showtime.room',
            'showtime.cinema',
            'bookingSeats',
            'bookingCombos.combo',
        ])
        ->where('user_id', Auth::id())
        ->latest()
        ->get();

        return view(
            'ticket.index',
            compact('bookings')
        );
    }

    /**
     * API: Chi tiết 1 booking (cho modal lịch sử giao dịch).
     */
    public function show($id)
    {
        $booking = Booking::with([
            'payment',
            'tickets.bookingSeat',
            'showtime.movie',
            'showtime.room',
            'showtime.cinema',
            'bookingSeats',
            'bookingCombos.combo',
        ])
        ->where('user_id', Auth::id())
        ->findOrFail($id);

        return response()->json([
            'success' => true,
            'booking' => $booking,
        ]);
    }
}
