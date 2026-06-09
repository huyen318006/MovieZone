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
            'tickets'
        ])
        ->where('user_id', Auth::id())
        ->latest()
        ->get();

        return view(
            'ticket.index',
            compact('bookings')
        );
    }
}
