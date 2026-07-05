<?php

namespace App\Services;

use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * TicketPDFService — Sinh file PDF vé xem phim kèm QR Code.
 *
 * Responsibilities:
 *  - Load đầy đủ booking + tickets + showtime + movie + cinema + room
 *  - Sinh QR Code image (SVG) cho mỗi ticket
 *  - Render blade template → PDF (dùng DomPDF)
 *  - Lưu file PDF vào storage
 */
class TicketPDFService
{
    /**
     * Sinh file PDF chứa tất cả vé của 1 booking.
     *
     * Mỗi vé = 1 trang PDF, gộp chung trong 1 file.
     *
     * @param  Booking  $booking  Booking đã có tickets
     * @return string|null  Đường dẫn tuyệt đối tới file PDF, hoặc null nếu lỗi
     */
    public function generateBookingTicketsPDF(Booking $booking): ?string
    {
        try {
            // ── Load đầy đủ relationships ──
            $booking->load([
                'tickets.bookingSeat',
                'showtime.movie',
                'showtime.cinema',
                'showtime.room',
                'user',
            ]);

            $tickets = $booking->tickets;

            if ($tickets->isEmpty()) {
                Log::warning('TicketPDFService: No tickets found for booking', [
                    'booking_id' => $booking->id,
                ]);

                return null;
            }

            $showtime = $booking->showtime;

            // ── Sinh QR Code image (SVG) cho mỗi ticket ──
            $qrImages = [];
            foreach ($tickets as $ticket) {
                $qrContent = $ticket->qr_code;

                if (empty($qrContent)) {
                    // Fallback: sinh lại QR content nếu chưa có
                    $qrCodeService = app(QRCodeService::class);
                    $qrContent = $qrCodeService->generateQRContent($ticket->ticket_code);
                }

                // Sinh SVG QR Code (dùng SimpleSoftwareIO)
                $qrImages[$ticket->id] = QrCode::format('svg')
                    ->size(180)
                    ->margin(1)
                    ->color(15, 23, 42)        // Dark navy (#0f172a)
                    ->backgroundColor(255, 255, 255) // White
                    ->errorCorrection('H')     // High error correction (30%)
                    ->generate($qrContent);
            }

            // ── Render PDF ──
            $pdf = Pdf::loadView('pdf.ticket-pdf', [
                'booking'  => $booking,
                'tickets'  => $tickets,
                'showtime' => $showtime,
                'qrImages' => $qrImages,
            ]);

            $pdf->setPaper('A4', 'portrait');

            // ── Lưu file ──
            $directory = 'tickets';
            if (!Storage::exists($directory)) {
                Storage::makeDirectory($directory);
            }

            $filename = "tickets_{$booking->booking_code}_" . now()->format('YmdHis') . '.pdf';
            $relativePath = "{$directory}/{$filename}";

            Storage::put($relativePath, $pdf->output());

            $absolutePath = Storage::path($relativePath);

            Log::info('TicketPDFService: PDF generated', [
                'booking_id'   => $booking->id,
                'booking_code' => $booking->booking_code,
                'ticket_count' => $tickets->count(),
                'file'         => $absolutePath,
            ]);

            return $absolutePath;

        } catch (\Exception $e) {
            Log::error('TicketPDFService: Failed to generate PDF', [
                'booking_id' => $booking->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            return null;
        }
    }
}
