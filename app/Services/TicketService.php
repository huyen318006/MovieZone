<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Ticket;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * TicketService — Sinh mã vé sau khi thanh toán thành công.
 *
 * Responsibilities:
 *  - Sinh Booking Code duy nhất (nếu chưa có)
 *  - Sinh Ticket Code duy nhất cho mỗi booking_seat (cryptographically random)
 *  - Sinh QR content (delegate cho QRCodeService)
 *  - Tạo record Ticket trong DB
 *
 * Ticket Code format: "TK" + 12 ký tự random [A-Z2-9]
 *   → Loại trừ: 0 (nhầm O), 1 (nhầm I/L), I, L, O
 *   → 33^12 ≈ 1.67 × 10^18 tổ hợp (~60 bits entropy)
 *   → Không thể brute-force, không thể đoán từ mã khác
 */
class TicketService
{
    /**
     * Số lần retry tối đa khi sinh code bị trùng.
     */
    private const MAX_RETRY = 10;

    /**
     * Bảng chữ cái an toàn: A-Z trừ I, L, O + 2-9 trừ 0, 1
     * → 33 ký tự, tránh nhầm lẫn khi đọc/nhập thủ công.
     */
    private const SAFE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /**
     * Độ dài phần random của ticket code (không tính prefix "TK").
     */
    private const TICKET_CODE_LENGTH = 12;

    public function __construct(
        protected QRCodeService $qrCodeService,
    ) {}

    /**
     * Sinh tất cả tickets cho 1 booking sau khi thanh toán thành công.
     *
     * Phải gọi TRONG DB::transaction từ caller.
     *
     * @param  Booking  $booking  Booking đã thanh toán (status = PAID)
     * @return Collection<Ticket>  Danh sách tickets vừa tạo
     *
     * @throws \RuntimeException Nếu booking không có ghế hoặc sinh code thất bại
     */
    public function generateTicketsForBooking(Booking $booking): Collection
    {
        // ── BƯỚC 1: Đảm bảo Booking Code duy nhất ──
        if (empty($booking->booking_code)) {
            $booking->booking_code = $this->generateUniqueBookingCode();
            $booking->save();
        }

        // ── BƯỚC 2: Lấy danh sách ghế ──
        $bookingSeats = BookingSeat::where('booking_id', $booking->id)->get();

        if ($bookingSeats->isEmpty()) {
            throw new \RuntimeException("Booking #{$booking->id} không có ghế nào.");
        }

        // ── BƯỚC 3: Sinh Ticket cho mỗi ghế ──
        $tickets = collect();

        foreach ($bookingSeats as $seat) {
            // Kiểm tra đã có ticket cho seat này chưa (tránh duplicate)
            $existingTicket = Ticket::where('booking_seat_id', $seat->id)->first();
            if ($existingTicket) {
                $tickets->push($existingTicket);

                continue;
            }

            $ticketCode = $this->generateUniqueTicketCode();
            $qrContent = $this->qrCodeService->generateQRContent($ticketCode);

            $ticket = Ticket::create([
                'ticket_code'    => $ticketCode,
                'booking_id'     => $booking->id,
                'booking_seat_id' => $seat->id,
                'qr_code'        => $qrContent,
                'status'         => 'UNUSED',
            ]);

            $tickets->push($ticket);
        }

        Log::info('TicketService: Generated tickets', [
            'booking_id'   => $booking->id,
            'booking_code' => $booking->booking_code,
            'ticket_count' => $tickets->count(),
            'ticket_codes' => $tickets->pluck('ticket_code')->toArray(),
        ]);

        return $tickets;
    }

    /**
     * Độ dài phần random của booking code (không tính prefix "BK").
     */
    private const BOOKING_CODE_LENGTH = 10;

    /**
     * Sinh Booking Code duy nhất — CRYPTOGRAPHICALLY RANDOM.
     *
     * Format: "BK" + 10 ký tự ngẫu nhiên từ SAFE_ALPHABET [A-Z2-9]
     * VD: BKX7M2QP9RBW
     *
     * Sử dụng random_bytes() (CSPRNG) — không dùng Str::random().
     * Entropy: ~50 bits (33^10 ≈ 1.53 × 10^15 tổ hợp)
     */
    public function generateUniqueBookingCode(): string
    {
        $alphabetLength = strlen(self::SAFE_ALPHABET);
        $retryCount = 0;

        do {
            // Sinh bytes ngẫu nhiên bằng CSPRNG
            $randomBytes = random_bytes(self::BOOKING_CODE_LENGTH);

            // Map mỗi byte vào bảng chữ cái an toàn
            $randomPart = '';
            for ($i = 0; $i < self::BOOKING_CODE_LENGTH; $i++) {
                $index = ord($randomBytes[$i]) % $alphabetLength;
                $randomPart .= self::SAFE_ALPHABET[$index];
            }

            $code = 'BK' . $randomPart;

            $exists = Booking::where('booking_code', $code)->exists();
            $retryCount++;

            if ($retryCount > self::MAX_RETRY) {
                throw new \RuntimeException(
                    "Không thể sinh booking code duy nhất sau " . self::MAX_RETRY . " lần thử."
                );
            }
        } while ($exists);

        return $code;
    }

    /**
     * Sinh Ticket Code duy nhất — CRYPTOGRAPHICALLY RANDOM.
     *
     * Format: "TK" + 12 ký tự ngẫu nhiên từ SAFE_ALPHABET [A-Z2-9]
     * VD: TKX7M2QP9RBW4F, TKHA5VC8DR3WN6
     *
     * Sử dụng random_bytes() (CSPRNG) — không dùng sequential.
     * Entropy: ~60 bits (33^12 ≈ 1.67 × 10^18 tổ hợp)
     *
     * ⚠️ TẠI SAO KHÔNG DÙNG SEQUENTIAL (TK-20260705-001)?
     *   - Kẻ xấu biết TK-20260705-001 → thử -002, -003... dễ dàng
     *   - Lộ thông tin: ngày bán, số lượng vé bán trong ngày
     *   - Dễ bị enumeration attack
     */
    public function generateUniqueTicketCode(): string
    {
        $alphabetLength = strlen(self::SAFE_ALPHABET);
        $retryCount = 0;

        do {
            // Sinh bytes ngẫu nhiên bằng CSPRNG
            $randomBytes = random_bytes(self::TICKET_CODE_LENGTH);

            // Map mỗi byte vào bảng chữ cái an toàn
            $randomPart = '';
            for ($i = 0; $i < self::TICKET_CODE_LENGTH; $i++) {
                $index = ord($randomBytes[$i]) % $alphabetLength;
                $randomPart .= self::SAFE_ALPHABET[$index];
            }

            $code = 'TK' . $randomPart;

            $exists = Ticket::where('ticket_code', $code)->exists();
            $retryCount++;

            if ($retryCount > self::MAX_RETRY) {
                throw new \RuntimeException(
                    "Không thể sinh ticket code duy nhất sau " . self::MAX_RETRY . " lần thử."
                );
            }
        } while ($exists);

        return $code;
    }
}
