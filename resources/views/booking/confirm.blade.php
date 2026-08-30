@extends('layout.app')

@section('content')

{{-- COUNTDOWN TIMER 5 PHÚT --}}
@include('booking._countdown_timer', [
    'holdExpiresAt' => $holdExpiresAt,
    'serverTime' => $serverTime,
    'holdTotalSeconds' => $holdTotalSeconds,
    'resolvedShowtimeId' => $showtime_id ?? null
])

<section class="confirm-page">
    <div class="confirm-container">

        <div class="confirm-header">
            <h2>XÁC NHẬN ĐẶT VÉ</h2>
            <p>Kiểm tra lại thông tin trước khi thanh toán</p>
        </div>

        <div class="confirm-content">

            {{-- LEFT --}}
            <div class="confirm-movie">

                <img src="{{ $showtime->movie->poster }}"
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

                {{-- Giảm giá Hạng thành viên (nếu có) --}}
                @if(($tierDiscountAmount ?? 0) > 0)
                <div class="ticket-section">
                    <div class="price-breakdown">
                        <div class="price-row discount-row" style="color: #10b981;">
                            <span>🎖️ Ưu đãi Hạng {{ $tierName ?? 'MEMBER' }} ({{ $tierPercent ?? 0 }}%)</span>
                            <span>-{{ number_format($tierDiscountAmount, 0, ',', '.') }}đ</span>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Giảm giá Voucher (nếu có) --}}
                @if($discountAmount > 0)
                <div class="ticket-section">
                    <div class="price-breakdown">
                        <div class="price-row discount-row">
                            <span>🎟️ Voucher giảm</span>
                            <span>-{{ number_format($discountAmount, 0, ',', '.') }}đ</span>
                        </div>
                    </div>
                </div>
                @endif

                {{-- GIẢM GIÁ XU --}}
                @if($coinDiscountAmount > 0)
                <div class="ticket-section">
                    <div class="price-breakdown">
                        <div class="price-row coin-discount-row">
                            <span>🪙 Xu giảm ({{ number_format($coinUsed) }} xu)</span>
                            <span>-{{ number_format($coinDiscountAmount, 0, ',', '.') }}đ</span>
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

        {{-- SECTION: SỬ DỤNG XU --}}
        <div class="payment-method-box coin-redemption-box">
            <h4>
                <i class="fa-solid fa-coins" style="color: #f59e0b;"></i>
                Sử dụng Xu để giảm giá
            </h4>

            @if(($coinInfo['balance'] ?? 0) <= 0)
                {{-- Không có xu --}}
                <div class="coin-empty-state">
                    <i class="fa-solid fa-piggy-bank"></i>
                    <p>Bạn chưa có xu nào. Hãy <a href="{{ \App\Helpers\TabAuthHelper::route('membership.index', ['id' => auth()->id()]) }}">diểm danh hàng ngày</a> hoặc mua vé để tích xu!</p>
                </div>
            @else
                {{-- Có xu --}}
                <div class="coin-info-grid">
                    <div class="coin-stat">
                        <span class="coin-stat-label">Số dư hiện tại</span>
                        <span class="coin-stat-value">
                            <i class="fa-solid fa-coins" style="color: #f59e0b; margin-right: 4px;"></i>
                            {{ number_format($coinInfo['balance']) }} xu
                        </span>
                    </div>
                    <div class="coin-stat">
                        <span class="coin-stat-label">Tối đa có thể dùng</span>
                        <span class="coin-stat-value">
                            {{ number_format($coinInfo['maxRedeemable']) }} xu
                            <small style="color: #9ca3af; font-weight: 400;">(= {{ number_format($coinInfo['maxDiscountVND']) }}đ)</small>
                        </span>
                    </div>
                </div>

                @if(($coinUsed ?? 0) > 0)
                    {{-- Đã áp dụng xu --}}
                    <div class="coin-applied-badge">
                        <div class="coin-applied-info">
                            <i class="fa-solid fa-check-circle" style="color: #34d399; font-size: 18px;"></i>
                            <span>Đã dùng <strong>{{ number_format($coinUsed) }} xu</strong> = Giảm <strong>{{ number_format($coinDiscountAmount) }}đ</strong></span>
                        </div>
                        <form action="{{ \App\Helpers\TabAuthHelper::route('booking.coin.remove') }}" method="POST" style="margin: 0;">
                            @csrf
                            <button type="submit" class="btn-coin-remove">
                                <i class="fa-solid fa-xmark"></i> Huỷ
                            </button>
                        </form>
                    </div>
                @else
                    {{-- Form nhập xu --}}
                    @if(($coinInfo['maxRedeemable'] ?? 0) > 0)
                    <form action="{{ \App\Helpers\TabAuthHelper::route('booking.coin.apply') }}" method="POST" class="coin-apply-form">
                        @csrf
                        <div class="coin-input-group">
                            <div class="coin-input-wrapper">
                                <i class="fa-solid fa-coins coin-input-icon"></i>
                                <input type="number"
                                       name="coin_amount"
                                       id="coinAmountInput"
                                       min="1"
                                       max="{{ $coinInfo['maxRedeemable'] }}"
                                       placeholder="Nhập số xu muốn dùng..."
                                       class="coin-input"
                                       value="{{ old('coin_amount') }}">
                                <span class="coin-input-hint" id="coinHint"></span>
                            </div>
                            <button type="submit" class="btn-coin-apply">
                                <i class="fa-solid fa-coins"></i> Áp dụng
                            </button>
                        </div>
                        <button type="button" class="btn-coin-use-all" id="btnUseAllCoins"
                                data-max="{{ $coinInfo['maxRedeemable'] }}"
                                data-discount="{{ $coinInfo['maxDiscountVND'] }}">
                            Dùng tất cả {{ number_format($coinInfo['maxRedeemable']) }} xu (giảm {{ number_format($coinInfo['maxDiscountVND']) }}đ)
                        </button>
                    </form>
                    @else
                    <div class="coin-empty-state" style="padding: 12px;">
                        <p style="margin: 0; color: #9ca3af; font-size: 14px;">
                            <i class="fa-solid fa-info-circle" style="margin-right: 4px;"></i>
                            Không thể dùng xu cho đơn hàng này (giá trị đơn hàng đã được giảm hết bởi voucher).
                        </p>
                    </div>
                    @endif
                @endif
            @endif
        </div>

        <form action="{{ \App\Helpers\TabAuthHelper::route('booking.checkout') }}" method="POST">
            @csrf

            <div class="payment-method-box">
                <h4>
                    <i class="fa-solid fa-user"></i>
                    Thông tin khách hàng
                </h4>

                <div class="customer-info-form" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 10px;">
                    <div>
                        <label style="color: #9ca3af; font-size: 14px; margin-bottom: 5px; display: block;">Họ và Tên <span style="color: #ef4444;">*</span></label>
                        <input type="text" name="customer_name" required value="{{ old('customer_name', auth()->user()->name ?? '') }}" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #374151; background: #111827; color: #f8fafc; font-size: 15px; outline: none;" placeholder="Nhập họ và tên..." onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#374151'">
                        @error('customer_name') <small style="color: #ef4444; margin-top: 4px; display: block;">{{ $message }}</small> @enderror
                    </div>
                    <div>
                        <label style="color: #9ca3af; font-size: 14px; margin-bottom: 5px; display: block;">Số điện thoại <span style="color: #ef4444;">*</span></label>
                        <input type="text" name="customer_phone" required value="{{ old('customer_phone', auth()->user()->phone ?? '') }}" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #374151; background: #111827; color: #f8fafc; font-size: 15px; outline: none;" placeholder="Nhập số điện thoại..." onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#374151'">
                        @error('customer_phone') <small style="color: #ef4444; margin-top: 4px; display: block;">{{ $message }}</small> @enderror
                    </div>
                    <div style="grid-column: span 2;">
                        <label style="color: #9ca3af; font-size: 14px; margin-bottom: 5px; display: block;">Email nhận hoá đơn <span style="color: #ef4444;">*</span></label>
                        <input type="email" name="customer_email" required value="{{ old('customer_email', auth()->user()->email ?? '') }}" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #374151; background: #111827; color: #f8fafc; font-size: 15px; outline: none;" placeholder="Nhập email nhận hoá đơn..." onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#374151'">
                        @error('customer_email') <small style="color: #ef4444; margin-top: 4px; display: block;">{{ $message }}</small> @enderror
                    </div>
                </div>
            </div>

            <div class="payment-method-box">

                <h4>
                    <i class="fa-solid fa-credit-card"></i>
                    Chọn phương thức thanh toán
                </h4>

                <div >
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

                <a href="{{ \App\Helpers\TabAuthHelper::route('booking.combo') }}"
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

.coin-discount-row span:last-child {
    color: #f59e0b;
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

/* === COIN REDEMPTION STYLES === */
.coin-redemption-box {
    border-color: rgba(245, 158, 11, 0.3);
    background: linear-gradient(135deg, #1f2937 0%, rgba(245, 158, 11, 0.05) 100%);
}

.coin-redemption-box h4 i { color: #f59e0b !important; }

.coin-empty-state {
    text-align: center;
    padding: 20px;
    color: #9ca3af;
}

.coin-empty-state i {
    font-size: 32px;
    color: #4b5563;
    margin-bottom: 10px;
    display: block;
}

.coin-empty-state a {
    color: #f59e0b;
    text-decoration: underline;
}

.coin-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 16px;
}

.coin-stat {
    background: #111827;
    border: 1px solid #374151;
    border-radius: 10px;
    padding: 14px 16px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.coin-stat-label {
    font-size: 12px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.coin-stat-value {
    font-size: 16px;
    color: #f59e0b;
    font-weight: 700;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 4px;
}

.coin-applied-badge {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: rgba(52, 211, 153, 0.08);
    border: 1px solid rgba(52, 211, 153, 0.3);
    border-radius: 10px;
    padding: 14px 18px;
}

.coin-applied-info {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #d1fae5;
    font-size: 15px;
}

.btn-coin-remove {
    background: rgba(239, 68, 68, 0.1);
    color: #fca5a5;
    border: 1px solid rgba(239, 68, 68, 0.3);
    padding: 8px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 4px;
}

.btn-coin-remove:hover {
    background: rgba(239, 68, 68, 0.2);
    border-color: #ef4444;
}

.coin-apply-form {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.coin-input-group {
    display: flex;
    gap: 10px;
    align-items: stretch;
}

.coin-input-wrapper {
    flex: 1;
    position: relative;
}

.coin-input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #f59e0b;
    font-size: 14px;
    pointer-events: none;
}

.coin-input {
    width: 100%;
    padding: 12px 90px 12px 38px; /* Increased right padding to prevent text overlap with hint */
    border-radius: 8px;
    border: 1px solid #374151;
    background: #111827;
    color: #f8fafc;
    font-size: 15px;
    outline: none;
    transition: border-color 0.2s;
}

/* Ẩn nút mũi tên tăng giảm của input number */
.coin-input::-webkit-outer-spin-button,
.coin-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
.coin-input[type=number] {
    -moz-appearance: textfield;
}

.coin-input:focus {
    border-color: #f59e0b;
}

.coin-input-hint {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    font-size: 12px;
    pointer-events: none;
}

.btn-coin-apply {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #111827;
    border: none;
    padding: 12px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
}

.btn-coin-apply:hover {
    background: linear-gradient(135deg, #d97706, #b45309);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.btn-coin-use-all {
    width: 100%;
    background: rgba(245, 158, 11, 0.08);
    color: #f59e0b;
    border: 1px dashed rgba(245, 158, 11, 0.4);
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-coin-use-all:hover {
    background: rgba(245, 158, 11, 0.15);
    border-color: #f59e0b;
}
/* === END COIN STYLES === */

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
    .coin-info-grid { grid-template-columns: 1fr; }
    .coin-input-group { flex-direction: column; }
}
</style>

{{-- Coin Input JS --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const coinInput = document.getElementById('coinAmountInput');
    const coinHint = document.getElementById('coinHint');
    const btnUseAll = document.getElementById('btnUseAllCoins');

    if (coinInput && coinHint) {
        coinInput.addEventListener('input', function() {
            const val = parseInt(this.value) || 0;
            if (val > 0) {
                coinHint.textContent = '= ' + val.toLocaleString('vi-VN') + 'đ';
            } else {
                coinHint.textContent = '';
            }
        });
    }

    if (btnUseAll && coinInput) {
        btnUseAll.addEventListener('click', function() {
            const max = parseInt(this.dataset.max) || 0;
            coinInput.value = max;
            if (coinHint) {
                coinHint.textContent = '= ' + max.toLocaleString('vi-VN') + 'đ';
            }
            // Auto submit
            coinInput.closest('form').submit();
        });
    }
});

    // Phát hiện người dùng nhấn Back trên trình duyệt để quay về trang trước đó
    window.addEventListener("pageshow", function (event) {
        var historyTraversal = event.persisted || 
                               (typeof window.performance != "undefined" && 
                                window.performance.navigation.type === 2);
        if (historyTraversal) {
            // Đã back lại, chuyển về trang chọn ghế với ?reset=1 để controller reset toàn bộ luồng
            var resetUrl = "{{ \App\Helpers\TabAuthHelper::route('booking.seat', ['showtime_id' => $showtime_id ?? ($showtime->id ?? 0)]) }}";
            resetUrl += (resetUrl.includes('?') ? '&' : '?') + 'reset=1';
            window.location.replace(resetUrl);
        }
    });
</script>

{{-- Kiểm tra suất chiếu bị huỷ bởi admin (polling mỗi 3s) --}}
@include('booking._showtime_cancelled_check', ['checkShowtimeId' => $showtime_id ?? ($showtime->id ?? null)])

@endsection
