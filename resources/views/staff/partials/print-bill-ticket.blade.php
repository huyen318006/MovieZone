{{-- Partial: 1 trang vé trong hoá đơn in --}}
{{-- Variables: $booking, $ticket, $seat, $pageIndex, $totalPages --}}

{{-- Header --}}
<div class="bill-header">
    <h1>MOVIEZONE</h1>
    <div class="subtitle">Vé xem phim</div>
    <div class="booking-code">{{ $ticket->ticket_code }}</div>
</div>

{{-- Movie --}}
<div class="movie-name" style="text-align: center; margin: 15px 0; font-size: 18px;">
    {{ $booking->showtime?->movie?->title ?? 'N/A' }}
</div>

{{-- Showtime & Cinema Info --}}
<div class="info-grid">
    <div class="info-item">
        <span class="info-label">Ngày chiếu</span>
        <span
            class="info-value">{{ $booking->showtime?->start_time ? $booking->showtime->start_time->format('d/m/Y') : 'N/A' }}</span>
    </div>
    <div class="info-item">
        <span class="info-label">Giờ chiếu</span>
        <span class="info-value">
            {{ $booking->showtime?->start_time ? $booking->showtime->start_time->format('H:i') : 'N/A' }}
            - {{ $booking->showtime?->end_time ? $booking->showtime->end_time->format('H:i') : '' }}
        </span>
    </div>
    <div class="info-item">
        <span class="info-label">Phòng chiếu</span>
        <span class="info-value">{{ $booking->showtime?->room?->name ?? 'N/A' }}</span>
    </div>
    <div class="info-item">
        <span class="info-label">Loại ghế</span>
        <span class="info-value">
            @if ($seat?->seat_type === 'vip')
                VIP
            @elseif($seat?->seat_type === 'sweetbox')
                Sweetbox
            @else
                Thường
            @endif
        </span>
    </div>
</div>

{{-- Tiền & Ghế --}}
<div
    style="text-align: center; margin: 25px 0; padding: 15px 0; border-top: 1px dashed #ccc; border-bottom: 1px dashed #ccc;">
    <div style="font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 1px;">Ghế ngồi</div>
    <div style="font-size: 38px; font-weight: 900; margin: 5px 0;">{{ $seat?->seat_code ?? 'N/A' }}</div>
</div>

<div class="total-row" style="border-top: none; padding-top: 0;">
    <span>Giá vé</span>
    <span>{{ number_format($seat?->price ?? 0, 0, ',', '.') }}đ</span>
</div>

{{-- Mã vé --}}
<div style="text-align: center; padding: 10px 0; margin-top: 20px; border-top: 2px solid #333;">
    <div style="font-size: 11px; color: #666; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Mã vé
    </div>
    <div style="font-family: 'Courier New', monospace; font-size: 16px; font-weight: 700; letter-spacing: 2px;">
        {{ $ticket->ticket_code }} </div>
</div>

{{-- Footer --}}
<div class="bill-footer">
    Powered by MovieZone • Vé {{ $pageIndex }}/{{ $totalPages }} • In lúc {{ now()->format('d/m/Y H:i:s') }}
</div>
