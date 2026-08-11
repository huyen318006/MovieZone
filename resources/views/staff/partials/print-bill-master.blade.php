{{-- Partial: Hóa đơn tổng hợp (Master Bill) --}}
<div class="bill-header">
    <h1>MOVIEZONE</h1>
    <div class="subtitle">Hóa Đơn Thanh Toán</div>
    <div class="booking-code">{{ $booking->booking_code }}</div>
</div>

<div class="movie-name">
    🎬 {{ $booking->showtime?->movie?->title ?? 'N/A' }}
</div>

<div class="info-grid">
    <div class="info-item">
        <span class="info-label">Khách hàng</span>
        <span class="info-value">{{ $booking->customer_name ?? 'Khách lẻ' }}</span>
    </div>
    <div class="info-item">
        <span class="info-label">Rạp</span>
        <span class="info-value">{{ $booking->showtime?->cinema?->name ?? 'N/A' }}</span>
    </div>
    <div class="info-item">
        <span class="info-label">Phòng</span>
        <span class="info-value">{{ $booking->showtime?->room?->name ?? 'N/A' }}</span>
    </div>
    <div class="info-item">
        <span class="info-label">Suất chiếu</span>
        <span class="info-value">
            {{ $booking->showtime?->start_time ? $booking->showtime->start_time->format('d/m/Y H:i') : 'N/A' }}
        </span>
    </div>
</div>

<div class="section-title">🎟️ Chi tiết thanh toán</div>
<table class="seats-table">
    <thead>
        <tr>
            <th>Mục</th>
            <th style="text-align:right;">Thành tiền</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <strong>Vé xem phim</strong>
                <div style="font-size: 11px; color: #666;">Số lượng: {{ $booking->tickets->count() }} vé</div>
            </td>
            <td class="seat-price">{{ number_format($booking->total_ticket_amount, 0, ',', '.') }}đ</td>
        </tr>
        
        @if($booking->bookingCombos->isNotEmpty())
        <tr>
            <td>
                <strong>Combo bắp nước</strong>
                <div style="font-size: 11px; color: #666;">Số lượng: {{ $booking->bookingCombos->sum('quantity') }}</div>
            </td>
            <td class="seat-price">{{ number_format($booking->total_combo_amount, 0, ',', '.') }}đ</td>
        </tr>
        @endif
        
        @if($booking->discount_amount > 0)
        <tr>
            <td>
                <strong>Khuyến mãi / Giảm giá</strong>
            </td>
            <td class="seat-price" style="color: #dc2626;">-{{ number_format($booking->discount_amount, 0, ',', '.') }}đ</td>
        </tr>
        @endif
    </tbody>
</table>

<div class="total-row">
    <span>Tổng thanh toán</span>
    <span>{{ number_format($booking->final_amount, 0, ',', '.') }}đ</span>
</div>

<div class="transaction-info">
    <div class="trans-row">
        <span>Trạng thái</span>
        <span class="val" style="color: {{ $booking->status === 'PAID' ? '#059669' : '#dc2626' }};">
            {{ $booking->status === 'PAID' ? '✓ Đã thanh toán' : $booking->status }}
        </span>
    </div>
    @if ($booking->payment)
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
        <span>Thời gian in</span>
        <span class="val">{{ now()->format('d/m/Y H:i:s') }}</span>
    </div>
</div>

<div class="bill-footer">
    Powered by MovieZone • Hóa Đơn Khách Hàng
</div>
