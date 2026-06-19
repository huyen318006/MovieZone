@extends('layout.app')

@section('content')

<section class="confirm-page">
    <div class="confirm-container">

        <div class="confirm-header">
            <h2>XÁC NHẬN ĐẶT VÉ</h2>
            <p>Kiểm tra lại thông tin trước khi thanh toán</p>
        </div>

        <div class="confirm-content">

            {{-- LEFT --}}
            <div class="confirm-movie">

                <img src="{{ $showtime->movie->poster_url ? asset($showtime->movie->poster_url) : asset('assets/hero/avatar.jpg') }}"
                     alt="{{ $showtime->movie->title }}"
                     class="movie-poster">

                <div class="movie-info">

                    <h3>{{ $showtime->movie->title }}</h3>

                    

                    <div class="info-item">
                        <i class="fa-solid fa-door-open"></i>
                        {{ $showtime->room->name }}
                    </div>

                    <div class="info-item">
                        <i class="fa-solid fa-clock"></i>
                        {{ \Carbon\Carbon::parse($showtime->start_time)->format('H:i - d/m/Y') }}
                    </div>

                </div>

            </div>

            {{-- RIGHT --}}
            <div class="confirm-ticket">

                <div class="ticket-section">
                    <h4>Ghế đã chọn</h4>

                    <div class="seat-list">
                        @foreach($seats as $seat)
                            @php
                                $seatCode = $seat->seat->seat_code ?? $seat->seat_code;
                                $seatType = $seat->seat->type ?? '';
                                $isSweetbox = (strtolower($seatType) == 'sweetbox') || str_contains(strtolower($seatCode), 'sw');
                            @endphp
                            
                            <span class="seat-badge {{ $isSweetbox ? 'seat-sweetbox' : '' }}">
                                @if($isSweetbox)
                                    <i class="fa-solid fa-heart" style="font-size: 12px; margin-right: 4px;"></i>
                                @endif
                                {{ $seatCode }}
                            </span>
                        @endforeach
                    </div>
                </div>

                {{-- Chi tiết giá vé --}}
                <div class="ticket-section">
                    <h4>Chi tiết giá vé</h4>
                    <div class="price-breakdown">
                        @foreach($seats as $seat)
                            <div class="price-row">
                                <span>🎬 {{ $seat->seat->seat_code ?? '' }}</span>
                                <span>{{ number_format($seat->price, 0, ',', '.') }}đ</span>
                            </div>
                        @endforeach
                        <div class="price-subtotal-row">
                            <span>Tổng vé</span>
                            <span>{{ number_format($totalTicketPrice, 0, ',', '.') }}đ</span>
                        </div>
                    </div>
                </div>

                {{-- Combo đã chọn --}}
                @if(!empty($combos))
                <div class="ticket-section">
                    <h4>Combo đã chọn</h4>
                    <div class="price-breakdown">
                        @foreach($combos as $combo)
                            <div class="price-row">
                                <span>🍿 {{ $combo['name'] }} x{{ $combo['quantity'] }}</span>
                                <span>{{ number_format($combo['total_price'], 0, ',', '.') }}đ</span>
                            </div>
                        @endforeach
                        <div class="price-subtotal-row">
                            <span>Tổng combo</span>
                            <span>{{ number_format($totalComboPrice, 0, ',', '.') }}đ</span>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Giảm giá (nếu có) --}}
                @if($discountAmount > 0)
                <div class="ticket-section">
                    <div class="price-breakdown">
                        <div class="price-row discount-row">
                            <span>🎫 Giảm giá</span>
                            <span>-{{ number_format($discountAmount, 0, ',', '.') }}đ</span>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Tổng thanh toán --}}
                <div class="ticket-section">
                    <h4>Tổng thanh toán</h4>
                    <div class="total-price">
                        {{ number_format($totalPrice, 0, ',', '.') }}VNĐ
                    </div>
                </div>

            </div>

        </div>

        <form action="{{ route('booking.checkout') }}" method="POST">
            @csrf

            <div class="payment-method-box">

                <div class="payment-options-grid">
                    <label class="payment-option">
                        <input type="radio"
                               name="payment_method"
                               value="ONLINE"
                               checked>
                        <div class="option-content">
                            <span class="icon">💳</span>
                            <span class="text">Thanh toán Online</span>
                        </div>
                    </label>

                </div>

            </div>

            <div class="confirm-actions">

                <a href="{{ route('booking.combo') }}"
                   class="btn-back">
                    <i class="fa-solid fa-arrow-left"></i>
                    Quay lại chọn combo
                </a>

                <button type="submit" class="btn-confirm">
                    Xác nhận & Thanh toán
                    <i class="fa-solid fa-check"></i>
                </button>

            </div>

        </form>

    </div>
</section>

<style>
/* Reset màu nền tổng thể về Dark Theme */
.confirm-page {
    padding: 60px 20px;
    background: #090e17;
    min-height: 100vh;
    font-family: 'Inter', sans-serif;
    color: #e2e8f0;
}

.confirm-container {
    max-width: 1100px;
    margin: 50px auto;
    background: #111827;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    border: 1px solid #1f2937;
}

.confirm-header {
    background: linear-gradient(to right, #1f2937, #111827);
    border-bottom: 1px solid #374151;
    text-align: center;
    padding: 30px;
}

.confirm-header h2 {
    margin: 0;
    font-size: 28px;
    font-weight: 700;
    color: #ffffff;
    letter-spacing: 1px;
}

.confirm-header p {
    margin-top: 8px;
    color: #9ca3af;
    font-size: 15px;
}

.confirm-content {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr;
    gap: 30px;
    padding: 30px;
}

.confirm-movie {
    display: flex;
    gap: 25px;
}

.movie-poster {
    width: 200px;
    height: 300px;
    object-fit: cover;
    border-radius: 12px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
    border: 1px solid #374151;
}

.movie-info { flex: 1; }

.movie-info h3 {
    margin-top: 0;
    margin-bottom: 20px;
    font-size: 26px;
    color: #f8fafc;
}

.info-item {
    background: #1f2937;
    color: #e2e8f0;
    padding: 14px 18px;
    border-radius: 10px;
    margin-bottom: 12px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1px solid #374151;
}

.info-item i { color: #3b82f6; font-size: 18px; }

.confirm-ticket {
    background: #1f2937;
    border-radius: 12px;
    padding: 25px;
    border: 1px solid #374151;
}

.ticket-section { margin-bottom: 25px; }

.ticket-section h4 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #9ca3af;
    font-size: 15px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.seat-list { display: flex; flex-wrap: wrap; gap: 10px; }

.seat-badge {
    background: rgba(59, 130, 246, 0.1);
    color: #60a5fa;
    border: 1px solid #3b82f6;
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 15px;
    display: inline-flex;
    align-items: center;
}

.seat-sweetbox {
    background: rgba(236, 72, 153, 0.1);
    color: #ec4899;
    border: 1px solid #ec4899;
}

.price-breakdown {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.price-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: #cbd5e1;
    font-size: 14px;
    padding: 6px 0;
}

.price-subtotal-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: #60a5fa;
    font-weight: 600;
    font-size: 15px;
    padding: 10px 0 0;
    margin-top: 8px;
    border-top: 1px solid #374151;
}

.discount-row span:last-child {
    color: #34d399;
    font-weight: 600;
}

.total-price {
    color: #ef4444;
    font-size: 36px;
    font-weight: 700;
    letter-spacing: -0.5px;
}

.payment-method-box {
    margin: 0 30px 30px;
    padding: 25px;
    background: #1f2937;
    border: 1px solid #374151;
    border-radius: 12px;
}

.payment-method-box h4 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #f8fafc;
    font-size: 18px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.payment-method-box h4 i { color: #3b82f6; }

.payment-options-grid {
    gap: 15px;
}

.payment-option { display: block; cursor: pointer; }

.payment-option input[type="radio"] { display: none; }

.option-content {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 16px;
    background: #111827;
    border: 2px solid #374151;
    border-radius: 10px;
    transition: all 0.3s ease;
    text-align: center
}

.payment-option input[type="radio"]:checked + .option-content {
    border-color: #3b82f6;
    background: rgba(59, 130, 246, 0.05);
}

.option-content .icon { font-size: 20px; }

.option-content .text { color: #cbd5e1; font-weight: 500; }

.payment-option input[type="radio"]:checked + .option-content .text {
    color: #ffffff;
    font-weight: 600;
}

.confirm-actions {
    display: flex;
    justify-content: space-between;
    padding: 0 30px 30px;
}

.btn-back {
    background: #374151;
    color: #f8fafc;
    padding: 14px 28px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: 0.3s;
}

.btn-back:hover { background: #4b5563; }

.btn-confirm {
    background: #ef4444;
    color: white;
    border: none;
    padding: 14px 32px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: 0.3s;
    box-shadow: 0 4px 14px 0 rgba(239, 68, 68, 0.39);
}

.btn-confirm:hover {
    background: #dc2626;
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.23);
}

@media(max-width: 768px) {
    .confirm-content { grid-template-columns: 1fr; }
    .confirm-movie { flex-direction: column; align-items: center; text-align: center; }
    .movie-poster { width: 100%; max-width: 250px; }
    .payment-options-grid { grid-template-columns: 1fr; }
    .confirm-actions { flex-direction: column-reverse; gap: 15px; }
    .btn-back, .btn-confirm { width: 100%; justify-content: center; }
}
</style>

@endsection