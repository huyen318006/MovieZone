{{-- Partial: 1 trang vé trong hoá đơn in --}}
{{-- Variables: $booking, $ticket, $seat, $pageIndex, $totalPages --}}

{{-- Header --}}
<div class="bill-header">
    <h1>MOVIEZONE</h1>
    <div class="subtitle">Vé xem phim</div>
    <div class="booking-code">{{ $ticket->ticket_code }}</div>
</div>

{{-- Movie --}}
<div class="movie-name">
    🎬 {{ $booking->showtime?->movie?->title ?? 'N/A' }}
</div>

{{-- Showtime & Cinema Info --}}
<div class="info-grid">
    <div class="info-item">
        <span class="info-label">Rạp</span>
        <span class="info-value">{{ $booking->showtime?->cinema?->name ?? 'N/A' }}</span>
    </div>
    <div class="info-item">
        <span class="info-label">Phòng</span>
        <span class="info-value">{{ $booking->showtime?->room?->name ?? 'N/A' }} {{ $booking->showtime?->room?->room_type ? '('.$booking->showtime->room->room_type.')' : '' }}</span>
    </div>
    <div class="info-item">
        <span class="info-label">Ngày chiếu</span>
        <span class="info-value">{{ $booking->showtime?->start_time ? $booking->showtime->start_time->format('d/m/Y') : 'N/A' }}</span>
    </div>
    <div class="info-item">
        <span class="info-label">Suất chiếu</span>
        <span class="info-value">{{ $booking->showtime?->start_time ? $booking->showtime->start_time->format('H:i') : 'N/A' }} - {{ $booking->showtime?->end_time ? $booking->showtime->end_time->format('H:i') : '' }}</span>
    </div>
    <div class="info-item">
        <span class="info-label">Trạng thái</span>
        <span class="info-value" style="color: {{ $booking->status === 'PAID' ? '#059669' : '#dc2626' }};">
            {{ $booking->status === 'PAID' ? '✓ Đã thanh toán' : $booking->status }}
        </span>
    </div>
</div>

{{-- Customer --}}
<div class="section-title">👤 Khách hàng</div>
<div class="info-grid">
    <div class="info-item">
        <span class="info-label">Họ tên</span>
        <span class="info-value">{{ $booking->user?->name ?? $booking->customer_name ?? 'N/A' }}</span>
    </div>
    <div class="info-item">
        <span class="info-label">SĐT</span>
        <span class="info-value">{{ $booking->user?->phone ?? $booking->customer_phone ?? 'N/A' }}</span>
    </div>
    <div class="info-item" style="grid-column: span 2;">
        <span class="info-label">Email</span>
        <span class="info-value">{{ $booking->user?->email ?? $booking->customer_email ?? 'N/A' }}</span>
    </div>
</div>

{{-- Seat detail --}}
<div class="section-title">🎟️ Chi tiết vé</div>
<table class="seats-table">
    <thead>
        <tr>
            <th>Ghế</th>
            <th>Loại</th>
            <th>Mã vé</th>
            <th style="text-align:right;">Giá</th>
        </tr>
    </thead>
    <tbody>
        @if($seat)
        <tr>
            <td><strong>{{ $seat->seat_code }}</strong></td>
            <td class="seat-type">
                @if($seat->seat_type === 'vip')
                    👑 VIP
                @elseif($seat->seat_type === 'sweetbox')
                    💕 Sweetbox
                @else
                    🎬 Thường
                @endif
            </td>
            <td style="font-family:'Courier New',monospace; font-size:11px;">{{ $ticket->ticket_code }}</td>
            <td class="seat-price">{{ number_format($seat->price, 0, ',', '.') }}đ</td>
        </tr>
        @endif
    </tbody>
</table>

{{-- Total --}}
<div class="total-row">
    <span>Giá vé</span>
    <span>{{ number_format($seat->price ?? 0, 0, ',', '.') }}đ</span>
</div>

{{-- QR Code --}}
<div class="qr-section">
    @if($ticket->qr_code)
        <img src="{{ asset('storage/' . $ticket->qr_code) }}"
             alt="QR {{ $ticket->ticket_code }}"
             onerror="this.src='https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($ticket->ticket_code) }}&color=0f172a&bgcolor=ffffff&margin=4'">
    @else
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($ticket->ticket_code) }}&color=0f172a&bgcolor=ffffff&margin=4"
             alt="QR {{ $ticket->ticket_code }}">
    @endif
    <div class="qr-code-text">{{ $ticket->ticket_code }}</div>
    <div class="qr-hint">Ghế {{ $seat?->seat_code ?? 'N/A' }} • Đưa mã QR này cho nhân viên tại rạp</div>
</div>

{{-- Transaction --}}
<div class="transaction-info">
    <div class="trans-row">
        <span>Mã Booking</span>
        <span class="val">{{ $booking->booking_code }}</span>
    </div>
    @if($booking->payment)
    <div class="trans-row">
        <span>Phương thức TT</span>
        <span class="val">{{ $booking->payment->payment_method ?? 'N/A' }}</span>
    </div>
    <div class="trans-row">
        <span>Mã giao dịch</span>
        <span class="val">{{ $booking->payment->transaction_code ?? 'N/A' }}</span>
    </div>
    @endif
    <div class="trans-row">
        <span>Thời gian đặt</span>
        <span class="val">{{ $booking->created_at->format('d/m/Y H:i:s') }}</span>
    </div>
    <div class="trans-row">
        <span>Thời gian TT</span>
        <span class="val">{{ $booking->paid_at ? \Carbon\Carbon::parse($booking->paid_at)->format('d/m/Y H:i:s') : '—' }}</span>
    </div>
</div>

{{-- Footer --}}
<div class="bill-footer">
    Powered by MovieZone • Vé {{ $pageIndex }}/{{ $totalPages }} • In lúc {{ now()->format('d/m/Y H:i:s') }}
</div>
