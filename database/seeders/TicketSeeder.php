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

        $dateStr = now()->format('Ymd');

        for ($i = 1; $i <= 50; $i++) {
            // Format chuẩn: TK-YYYYMMDD-NNN
            $ticketCode = 'TK-' . $dateStr . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);

            Ticket::create([
                'booking_id'      => rand(1, 20),
                'booking_seat_id' => rand(1, 50),
                'ticket_code'     => $ticketCode,
                'qr_code'         => 'QR-' . $dateStr . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'status'          => $i % 5 === 0 ? 'USED' : 'UNUSED',
                'checked_in_at'   => $i % 5 === 0 ? now() : null,
                'checked_in_by'   => $i % 5 === 0 ? 3 : null, // Staff user_id = 3
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }
}

