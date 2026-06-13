<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\ShowtimeSeat;
use Illuminate\Database\Seeder;

class BookingSeatSeeder extends Seeder
{
    public function run(): void
    {
        BookingSeat::query()->delete();

        $bookings = Booking::all();
        $showtimeSeats = ShowtimeSeat::all();

        if ($bookings->isEmpty() || $showtimeSeats->isEmpty()) {
            $this->command->warn('Không có dữ liệu Booking hoặc ShowtimeSeat');
            return;
        }

        foreach ($showtimeSeats->take(50) as $showtimeSeat) {

            $booking = $bookings->random();

            BookingSeat::create([
                'booking_id'       => $booking->id,
                'showtime_seat_id' => $showtimeSeat->id,

                'seat_code' => $showtimeSeat->seat->seat_code ?? '',

                'seat_type' => $showtimeSeat->seat->seat_type ?? 'STANDARD',

                'price' => $showtimeSeat->price,

                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('BookingSeatSeeder completed');
    }
}