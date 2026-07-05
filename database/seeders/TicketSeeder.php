<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Services\QRCodeService;
use App\Services\TicketService;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        Ticket::query()->delete();

        $qrService = app(QRCodeService::class);
        $ticketService = app(TicketService::class);

        for ($i = 1; $i <= 50; $i++) {
            // Format mới: CSPRNG + safe alphabet [A-Z2-9]
            $ticketCode = $ticketService->generateUniqueTicketCode();

            // QR format: MZ|ticket_code|checksum
            $qrContent = $qrService->generateQRContent($ticketCode);

            Ticket::create([
                'booking_id'      => rand(1, 20),
                'booking_seat_id' => rand(1, 50),
                'ticket_code'     => $ticketCode,
                'qr_code'         => $qrContent,
                'status'          => $i % 5 === 0 ? 'USED' : 'UNUSED',
                'checked_in_at'   => $i % 5 === 0 ? now() : null,
                'checked_in_by'   => $i % 5 === 0 ? 3 : null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }
}
