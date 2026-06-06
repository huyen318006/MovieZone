<?php

namespace Database\Seeders;

use App\Models\Ticket;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Ticket::query()->delete();

        for ($i = 1; $i <= 50; $i++) {
            Ticket::create([
                'booking_id' => rand(1, 20),
                'booking_seat_id' => rand(1, 50),
                'ticket_code' => 'TK' . strtoupper(uniqid()),
                'qr_code' => 'QR' . strtoupper(uniqid()),
                'status' => 'UNUSED',
                'checked_in_at' => null,
                'checked_in_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
