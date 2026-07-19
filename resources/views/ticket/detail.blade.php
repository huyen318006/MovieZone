@extends('layout.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<div class="ticket-detail-container">
    {{-- Thanh điều hướng quay lại --}}
    <div class="back-navigation">
        <a href="{{ route('my-tickets.index') }}" class="back-btn">
            <i class="bi bi-arrow-left"></i> Quay lại danh sách
        </a>
    </div>

    <div class="detail-card-wrapper">
        <div class="detail-card-header">
            <h3><i class="bi bi-ticket-detailed"></i> Chi tiết giao dịch: <span class="highlight">{{ $booking->booking_code }}</span></h3>
        </div>

        <div class="detail-card-body">
            
            {{-- 1. Thông tin phim & suất chiếu --}}
            <div class="detail-section">
                <div class="detail-section-title"><i class="bi bi-film"></i> Thông tin suất chiếu</div>
                
                {{-- Khu vực hiển thị thông tin phim trực quan --}}
                @if(isset($booking->showtime->movie))
                    <div class="movie-info-header">
                        @if($booking->showtime->movie->poster_url)
                            <img class="movie-poster" src="{{ asset('storage/' . $booking->showtime->movie->poster_url) }}" alt="{{ $booking->showtime->movie->title }}">
                        @endif
                        <div class="movie-text-details">
                            <h4 class="movie-title">{{ $booking->showtime->movie->title }}</h4>
                            <div class="movie-meta-tags">
                                @if($booking->showtime->movie->language)
                                    <span class="meta-badge lang-badge"><i class="bi bi-translate"></i> {{ $booking->showtime->movie->language }}</span>
                                @endif
                                @if($booking->showtime->movie->duration_minutes)
                                    <span class="meta-badge duration-badge"><i class="bi bi-clock"></i> {{ $booking->showtime->movie->duration_minutes }} phút</span>
                                @endif
                                @if($booking->showtime->movie->age_rating)
                                    <span class="meta-badge age-badge"><i class="bi bi-exclamation-triangle"></i> {{ $booking->showtime->movie->age_rating }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="label">Rạp</div>
                        <div class="value">{{ $booking->showtime->cinema->name ?? 'N/A' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Phòng chiếu</div>
                        <div class="value">{{ $booking->showtime->room->name ?? 'N/A' }} ({{ $booking->showtime->room->room_type ?? '2D' }})</div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Suất chiếu</div>
                        <div class="value">
                            {{ $booking->showtime ? \Carbon\Carbon::parse($booking->showtime->start_time)->format('H:i - d/m/Y') : 'N/A' }}
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Ngôn ngữ hiển thị</div>
                        <div class="value">{{ $booking->showtime->movie->language ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>

            {{-- 2. Ghế đã đặt --}}
            @if($booking->bookingSeats && $booking->bookingSeats->isNotEmpty())
                <div class="detail-divider"></div>
                <div class="detail-section">
                    <div class="detail-section-title"><i class="bi bi-grid-3x3-gap"></i> Ghế đã đặt ({{ $booking->bookingSeats->count() }})</div>
                    <div class="seat-list">
                        @foreach($booking->bookingSeats as $seat)
                            @php 
                                $typeLabel = $seat->seat_type === 'VIP' ? '👑 VIP' : ($seat->seat_type === 'COUPLE' ? '💕 Couple' : '🎬 Thường');
                            @endphp
                            <div class="seat-badge">
                                <span class="seat-code">{{ $seat->seat_code }}</span>
                                <span class="seat-price">{{ $typeLabel }} — {{ number_format($seat->price) }} VNĐ</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- 3. Combo đã đặt --}}
            @if($booking->bookingCombos && $booking->bookingCombos->isNotEmpty())
                <div class="detail-divider"></div>
                <div class="detail-section">
                    <div class="detail-section-title"><i class="bi bi-basket3"></i> Combo đã đặt</div>
                    @foreach($booking->bookingCombos as $combo)
                        <div class="combo-item">
                            <span class="combo-name">{{ $combo->combo->name ?? 'Combo' }}</span>
                            <span class="combo-qty">x{{ $combo->quantity }} — {{ number_format($combo->total_price) }} VNĐ</span>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- 4. Chi tiết thanh toán --}}
            <div class="detail-divider"></div>
            <div class="detail-section">
                <div class="detail-section-title"><i class="bi bi-credit-card"></i> Chi tiết thanh toán</div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="label">Tiền vé</div>
                        <div class="value">{{ number_format($booking->total_ticket_amount ?? ($booking->final_amount - $booking->total_combo_amount + $booking->discount_amount)) }} VNĐ</div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Combo</div>
                        <div class="value">{{ number_format($booking->total_combo_amount) }} VNĐ</div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Giảm giá</div>
                        <div class="value" style="color:#10b981;">-{{ number_format($booking->discount_amount) }} VNĐ</div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Tổng cộng</div>
                        <div class="value price-highlight">{{ number_format($booking->final_amount) }} VNĐ</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
body {
    background-color: #0b0f19 !important;
}

.ticket-detail-container {
    max-width: 800px;
    margin: 50px auto;
    padding: 0 20px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

/* Nút quay lại */
.back-navigation {
    margin-bottom: 20px;
}

.back-btn {
    text-decoration: none !important;
    color: #94a3b8 !important;
    font-size: 14px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: color 0.2s;
}

.back-btn:hover {
    color: #ffffff !important;
}

/* Card chi tiết */
.detail-card-wrapper {
    background: #111827;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    overflow: hidden;
}

.detail-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.detail-card-header h3 {
    color: #ffffff;
    font-size: 18px;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.detail-card-header h3 .highlight {
    color: #3b82f6;
}

.detail-card-body {
    padding: 24px;
    color: #e2e8f0;
}

/* Giao diện Header Phim */
.movie-info-header {
    display: flex;
    gap: 20px;
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 20px;
    align-items: center;
}

.movie-poster {
    width: 80px;
    height: 115px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.movie-text-details {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.movie-title {
    font-size: 20px;
    font-weight: 700;
    margin: 0;
    color: #ffffff;
}

.movie-meta-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.meta-badge {
    font-size: 11px;
    padding: 4px 10px;
    border-radius: 20px;
    font-weight: 500;
}

.lang-badge { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
.duration-badge { background: rgba(107, 114, 128, 0.15); color: #9ca3af; }
.age-badge { background: rgba(239, 68, 68, 0.15); color: #f87171; }

/* Các layout khu vực */
.detail-section {
    margin-bottom: 20px;
}

.detail-section-title {
    font-size: 13px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.detail-item {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    padding: 12px 14px;
}

.detail-item .label {
    font-size: 11px;
    color: #64748b;
    margin-bottom: 4px;
}

.detail-item .value {
    font-size: 14px;
    color: #f1f5f9;
    font-weight: 500;
}

.detail-item .value.price-highlight {
    color: #38bdf8;
    font-weight: 700;
}

.detail-divider {
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.08), transparent);
    margin: 20px 0;
}

/* Ghế badges */
.seat-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.seat-badge {
    background: linear-gradient(135deg, #312e81, #4c1d95);
    color: #c4b5fd;
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
}

.seat-badge .seat-code {
    color: #ffffff;
    font-size: 15px;
}

.seat-badge .seat-price {
    font-size: 11px;
    color: #a78bfa;
}

/* Combo list */
.combo-item {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    padding: 10px 14px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.combo-item .combo-name {
    color: #e2e8f0;
    font-weight: 500;
}

.combo-item .combo-qty {
    color: #94a3b8;
    font-size: 13px;
}

@media(max-width: 768px) {
    .detail-grid {
        grid-template-columns: 1fr;
    }
    .movie-info-header {
        flex-direction: column;
        text-align: center;
    }
}
</style>
@endsection