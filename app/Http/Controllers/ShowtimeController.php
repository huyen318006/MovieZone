<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Showtime;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ShowtimeController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['movie', 'cinema', 'date']);

        $movies = Movie::query()
            ->visible()
            ->whereHas('showtimes', function ($query) {
                $query->availableForBooking();
            })
            ->orderBy('title')
            ->get();

        $cinemas = Cinema::query()
            ->where('status', 'ACTIVE')
            ->whereHas('showtimes', function ($query) use ($filters) {
                $query->availableForBooking()
                    ->when($filters['movie'] ?? null, function ($query, $movieId) {
                        $query->where('movie_id', $movieId);
                    });
            })
            ->orderBy('name')
            ->get();

        $selectedMovie = Movie::query()
            ->visible()
            ->find($filters['movie'] ?? null);

        $selectedCinema = Cinema::query()
            ->where('status', 'ACTIVE')
            ->find($filters['cinema'] ?? null);

        $availableDates = Showtime::query()
            ->availableForBooking()
            ->when($selectedMovie, function ($query) use ($selectedMovie) {
                $query->where('movie_id', $selectedMovie->id);
            })
            ->when($selectedCinema, function ($query) use ($selectedCinema) {
                $query->where('cinema_id', $selectedCinema->id);
            })
            ->selectRaw('DATE(start_time) as show_date')
            ->distinct()
            ->orderBy('show_date')
            ->pluck('show_date');

        $selectedDate = $filters['date'] ?? $availableDates->first();

        $showtimes = Showtime::query()
            ->availableForBooking()
            ->with(['movie.genres', 'cinema', 'room', 'showtimeSeats'])
            ->withCount([
                'showtimeSeats as available_seats_count' => function ($query) {
                    $query->available();
                },
            ])
            ->withMin([
                'showtimeSeats as min_ticket_price' => function ($query) {
                    $query->available();
                },
            ], 'price')
            ->when($selectedMovie, function ($query) use ($selectedMovie) {
                $query->where('movie_id', $selectedMovie->id);
            })
            ->when($selectedCinema, function ($query) use ($selectedCinema) {
                $query->where('cinema_id', $selectedCinema->id);
            })
            ->when($selectedDate, function ($query) use ($selectedDate) {
                $query->whereDate('start_time', $selectedDate);
            })
            ->orderBy('start_time')
            ->get();

        return view('showtime.index', compact(
            'movies',
            'cinemas',
            'availableDates',
            'showtimes',
            'filters',
            'selectedMovie',
            'selectedCinema',
            'selectedDate'
        ));
    }

    public function select(Showtime $showtime)
    {
        $availableSeatsCount = $showtime->showtimeSeats()
            ->available()
            ->count();
        $startTime = Carbon::parse($showtime->start_time);

        if (
            $showtime->status !== 'OPEN'
            || $startTime->lte(now())
            || $availableSeatsCount === 0
        ) {
            return redirect()
                ->route('showtimes', [
                    'movie' => $showtime->movie_id,
                    'cinema' => $showtime->cinema_id,
                    'date' => $startTime->format('Y-m-d'),
                ])
                ->with('error', 'Suất chiếu không còn khả dụng');
        }

        return redirect()->route('booking.seat', ['showtime_id' => $showtime->id]);
    }
}
?>