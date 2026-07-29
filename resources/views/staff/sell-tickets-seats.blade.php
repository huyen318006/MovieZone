@extends('layout.staff')

@section('title', 'Chọn Ghế - Bán Vé')
@section('page-title', 'Bán Vé — Chọn Ghế')

@section('content')
<div class="sell-seat-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 text-white">Sơ đồ phòng chiếu: {{ $showtime->room->name }}</h4>
        <a href="{{ \App\Helpers\TabAuthHelper::route('staff.sell-tickets') }}" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left"></i> Quay lại</a>
    </div>

    <section class="seat-page" style="background: transparent; padding: 0;">
        <div class="seat-wrapper" style="margin: 0; max-width: 100%;">
            <!-- LEFT PANEL -->
            <div class="seat-info" style="background: var(--staff-surface, #1e293b); border: 1px solid var(--staff-border, #334155);">
                <img src="{{ $showtime->movie->poster_url ? asset('storage/' . $showtime->movie->poster_url) : asset('assets/hero/avatar.jpg') }}" class="movie-poster" alt="Movie Poster">
                <h2>THÔNG TIN ĐẶT VÉ</h2>

                <div class="booking-summary">
                    <p><i class="fa-solid fa-film"></i> Phim: {{ $showtime->movie->title }}</p>
                    <p><i class="fa-solid fa-door-open"></i> Phòng chiếu: {{ $showtime->room->name }}</p>
                    <p><i class="fa-solid fa-clock"></i> Khung giờ:
                        {{ \Carbon\Carbon::parse($showtime->start_time)->format('H:i - d/m/Y') }}</p>
                </div>

                <div class="selected-seat-box">
                    <h3>GHẾ ĐÃ CHỌN</h3>
                    <div id="selectedSeats">Chưa chọn ghế</div>
                    <div class="total-price" id="totalPrice">0VNĐ</div>

                    <form action="{{ \App\Helpers\TabAuthHelper::route('staff.sell-tickets.submitseat') }}" method="GET" id="bookingForm">
                        @csrf
                        <input type="hidden" name="tab_token" value="{{ \App\Helpers\TabAuthHelper::gettoken() }}">
                        <input type="hidden" name="showtime_id" value="{{ $showtime->id }}">
                        <div id="hidden-seat-inputs"></div>

                        <button type="submit" class="btn-payment" id="btnPayment" disabled>
                            Tiếp tục
                        </button>
                    </form>

                </div>
            </div>

            <!-- RIGHT SIDE -->
            <div class="seat-map-container" style="background: radial-gradient(circle at center, #0b1c3f 0%, #050b18 75%); border: 1px solid var(--staff-border, #334155); border-radius: 12px; padding: 30px;">
                <div class="screen-wrapper">
                    <div class="screen-curve"></div>
                    <div class="screen-text">SCREEN</div>
                </div>

                @if (session('error'))
                    <div class="alert alert-danger" style="color: white; background: #dc3545; padding: 10px; text-align: center; margin-bottom: 20px; border-radius: 5px;">
                        {{ session('error') }}
                    </div>
                @endif

                <div id="ajax-error-box" style="display: none; color: white; background: #dc3545; padding: 10px; text-align: center; margin-bottom: 20px; border-radius: 5px;">
                </div>

                <div class="seat-map">
                    @foreach ($seatMap as $rowLabel => $rowSeats)
                        @if (!empty($rowSeats))
                            <div class="seat-row">
                                <span class="row-label">{{ $rowLabel }}</span>

                                @php $skipNext = false; @endphp
                                @foreach ($rowSeats as $index => $s)
                                    @if ($skipNext)
                                        @php $skipNext = false; @endphp
                                        @continue
                                    @endif

                                    @php
                                        $isDisabled = in_array($s['status'], ['SOLD', 'BLOCKED', 'LOCKED', 'HELD'])
                                            ? 'disabled'
                                            : '';
                                        $isCouple = $s['type'] === 'sweetbox';
                                        $nextS = $rowSeats[$index + 1] ?? null;
                                        $isPair = $isCouple && $nextS && $nextS['type'] === 'sweetbox';
                                    @endphp

                                    @if ($isPair)
                                        @php
                                            $skipNext = true;
                                            $isDisabled2 = in_array($nextS['status'], [
                                                'SOLD',
                                                'BLOCKED',
                                                'LOCKED',
                                                'HELD',
                                            ])
                                                ? 'disabled'
                                                : '';
                                            $isBothDisabled =
                                                $isDisabled === 'disabled' || $isDisabled2 === 'disabled'
                                                    ? 'disabled'
                                                    : '';
                                            $combinedStatus =
                                                $s['status'] !== 'AVAILABLE' ? $s['status'] : $nextS['status'];
                                            if (
                                                $combinedStatus === 'AVAILABLE' &&
                                                $s['status'] === 'AVAILABLE' &&
                                                $nextS['status'] === 'AVAILABLE'
                                            ) {
                                                $combinedStatus = 'AVAILABLE';
                                            }
                                        @endphp
                                        <button type="button" class="seat couple-seat-btn {{ $combinedStatus }}"
                                            data-id="{{ $s['id'] }},{{ $nextS['id'] }}"
                                            data-seat="{{ $s['code'] }},{{ $nextS['code'] }}" data-type="COUPLE"
                                            data-price="{{ $s['price'] + $nextS['price'] }}" {{ $isBothDisabled }}
                                            title="Couple seat (2 tickets) - {{ number_format($s['price'] + $nextS['price']) }}đ">
                                            <span class="seat-label">{{ $s['label'] }}</span>
                                            <i class="fa-solid fa-heart couple-icon"></i>
                                            <span class="seat-label">{{ $nextS['label'] }}</span>
                                        </button>

                                        @if (isset($nextS['is_aisle']) && $nextS['is_aisle'])
                                            <div class="aisle"></div>
                                        @endif
                                    @else
                                        @php
                                            if ($s['type'] === 'sweetbox') {
                                                $btnClass = 'sweet-seat-btn';
                                                $icon = '<i class="fa-solid fa-heart"></i> ';
                                            } elseif ($s['type'] === 'vip') {
                                                $btnClass = 'vip-seat-btn';
                                                $icon = '<i class="fa-solid fa-crown" style="font-size: 11px; margin-right: 2px;"></i>';
                                            } else {
                                                $btnClass = 'available-seat-btn';
                                                $icon = '';
                                            }
                                        @endphp
                                        <button type="button" class="seat {{ $btnClass }} {{ $s['status'] }}"
                                            data-id="{{ $s['id'] }}" data-seat="{{ $s['code'] }}"
                                            data-type="{{ $s['type'] }}" data-price="{{ $s['price'] }}"
                                            {{ $isDisabled }}>
                                            {!! $icon !!}{{ $s['label'] }}
                                        </button>

                                        @if (isset($s['is_aisle']) && $s['is_aisle'])
                                            <div class="aisle"></div>
                                        @endif
                                    @endif
                                @endforeach

                                <span class="row-label">{{ $rowLabel }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>

                <div class="seat-legend" style="margin-top: 30px;">
                    <div class="legend-item"><i class="fa-solid fa-couch" style="color: #64748b;"></i><span>Thường</span></div>
                    <div class="legend-item"><i class="fa-solid fa-crown vip-seat-icon"></i><span>VIP</span></div>
                    <div class="legend-item"><i class="fa-solid fa-heart sweet-seat-icon"></i><span>Sweetbox</span></div>
                    <div class="legend-item"><i class="fa-solid fa-couch held-seat" style="color: #28a745;"></i><span>Đã chọn</span></div>
                    <div class="legend-item"><i class="fa-solid fa-couch" style="color: #ff9800;"></i><span>Đang giữ</span></div>
                    <!-- [FIX] Đã đổi chú thích từ "Đã đặt" → "Đã đặt / Đã khóa" vì SOLD, BLOCKED, LOCKED đều màu xám -->
                    <div class="legend-item"><i class="fa-solid fa-couch" style="color: #6b7280;"></i><span>Đã đặt / Đã khóa</span></div>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
    /* Add FontAwesome just in case layout.staff doesn't have it */
    @import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');

    .sell-seat-wrapper { padding: 20px; }

    /* =========================
    SEAT PAGE CSS (Exactly from customer app.css and seat.blade.php)
    ========================= */
    .seat-page {
        min-height: 100vh;
        background: transparent;
        color: #fff;
        margin-top: 0;
    }

    .seat-wrapper {
        display: flex;
        gap: 30px;
        max-width: 1600px;
        margin: auto;
    }

    /* LEFT PANEL */
    .seat-info {
        width: 360px;
        border-radius: 20px;
        padding: 25px;
        position: sticky;
        top: 20px;
        height: fit-content;
    }

    .movie-poster {
        width: 100%;
        border-radius: 15px;
        margin-bottom: 20px;
        object-fit: cover;
    }

    .seat-info h2 {
        font-size: 26px;
        color: #3b82f6;
        margin-bottom: 20px;
        text-align: left;
        font-weight: bold;
    }

    .booking-summary {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .booking-summary p {
        margin: 0;
        color: #d1d5db;
        line-height: 1.6;
        font-size: 16px;
    }

    .booking-summary i {
        width: 20px;
        color: #3b82f6;
    }

    /* SELECTED BOX */
    .selected-seat-box {
        margin-top: 25px;
        padding: 20px;
        border-radius: 15px;
        background: #111827;
        border: 1px solid rgba(255, 255, 255, .08);
    }

    .selected-seat-box h3 {
        text-align: center;
        color: #3b82f6;
        margin-bottom: 15px;
        font-size: 1.17em;
        font-weight: bold;
    }

    #selectedSeats {
        min-height: 60px;
        color: #f8fafc;
        text-align: center;
    }

    .total-price {
        margin-top: 20px;
        text-align: center;
        color: #60a5fa;
        font-size: 38px;
        font-weight: 700;
    }

    .btn-payment {
        width: 100%;
        margin-top: 20px;
        padding: 14px;
        border: none;
        border-radius: 10px;
        color: #fff;
        font-weight: 600;
        cursor: pointer;
        background: linear-gradient(90deg, #2563eb, #60a5fa);
        transition: .3s;
    }

    .btn-payment:hover:not(:disabled) {
        transform: translateY(-2px);
    }

    .btn-payment:disabled {
        background: #334155;
        color: #94a3b8;
        cursor: not-allowed;
    }

    /* RIGHT SIDE */
    .seat-map-container {
        flex: 1;
        overflow-x: auto;
    }

    /* SCREEN */
    .screen-wrapper {
        margin-bottom: 60px;
    }

    .screen-curve {
        width: 75%;
        height: 50px;
        margin: auto;
        border-top: 4px solid #60a5fa;
        border-radius: 100% 100% 0 0;
    }

    .screen-text {
        text-align: center;
        margin-top: -10px;
        letter-spacing: 8px;
        color: #dbeafe;
        font-weight: 600;
    }

    /* SEAT MAP */
    .seat-map {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .seat-row {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .row-label {
        width: 30px;
        text-align: center;
        color: #6b7280;
        font-size: 22px;
        font-weight: 600;
    }

    .aisle {
        width: 40px;
    }

    /* NORMAL SEAT */
    .seat {
        width: 40px;
        height: 36px;
        border: 2px solid #204d80;
        background: transparent;
        border-radius: 8px;
        color: #fff;
        cursor: pointer;
        transition: .3s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
    }

    .seat:hover:not(:disabled) {
        border-color: #3b82f6;
        transform: translateY(-2px);
    }

    .HELD_BY_ME {
        background-color: #28a745 !important;
        color: white !important;
        border: none !important;
    }

    .HELD {
        background-color: #ff9800 !important;
        color: white !important;
        cursor: not-allowed;
        opacity: 0.7;
    }

    /* ========================================
       === [FIX] SOLD, BLOCKED, LOCKED đều dùng màu xám giống nhau ===
       === Đã sửa: gộp chung 3 trạng thái này thành 1 màu #6b7280, thay vì BLOCKED/LOCKED riêng #343a40 ===
       ======================================== */
    .SOLD,
    .BLOCKED,
    .LOCKED {
        background-color: #6b7280 !important;
        color: white !important;
        cursor: not-allowed;
        opacity: 0.7;
    }

    .vip-seat-btn {
        color: white !important;
        border: 2px solid #eab308 !important;
        font-weight: bold !important;
    }

    .vip-seat-btn:hover {
        background-color: #eab308 !important;
    }

    .vip-seat-icon {
        color: #ffc107 !important;
    }

    /* Tách sweetbox lẻ và COUPLE pair (2 ghế) */
    .sweet-seat-btn {
        color: white !important;
        font-weight: bold !important;
        border-radius: 8px !important;
        margin: 3px 6px !important;
        background: linear-gradient(135deg, #db2777, #9d174d) !important;
        border: 2px solid #f9a8d4 !important;
        transition: all 0.2s ease;
    }

    .sweet-seat-btn:hover:not([disabled]) {
        background: linear-gradient(135deg, #be185d, #831843) !important;
    }

    .couple-seat-btn {
        color: white !important;
        width: 80px !important;
        /* Độ rộng gộp 2 ghế CGV style */
        display: inline-flex !important;
        align-items: center;
        justify-content: space-between;
        font-weight: bold !important;
        border-radius: 12px !important;
        margin: 3px 6px !important;
        padding: 0 10px !important;
        background: linear-gradient(135deg, #ec4899, #be185d) !important;
        border: 2px solid #fbcfe8 !important;
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px rgba(236, 72, 153, 0.2);
    }

    .couple-seat-btn:hover:not([disabled]) {
        background: linear-gradient(135deg, #db2777, #9d174d) !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(236, 72, 153, 0.4);
    }

    .couple-seat-btn .couple-icon {
        color: #fbcfe8 !important;
        font-size: 14px;
        opacity: 0.9;
        animation: heartbeat 2s infinite;
    }

    @keyframes heartbeat {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.15); }
    }

    /* Override trạng thái riêng cho Couple */
    .couple-seat-btn.HELD_BY_ME {
        background: linear-gradient(135deg, #28a745, #1e7e34) !important;
        border-color: #71e38c !important;
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
    }

    .couple-seat-btn.HELD_BY_ME .couple-icon {
        color: #ffffff !important;
        animation: none;
    }

    /* === [FIX] Couple SOLD/BLOCKED/LOCKED đều dùng màu xám #6b7280 ===
       === Đã sửa: gộp BLOCKED/LOCKED vào chung với SOLD thay vì màu #343a40 riêng === */
    .couple-seat-btn.SOLD,
    .couple-seat-btn.BLOCKED,
    .couple-seat-btn.LOCKED {
        background: #6b7280 !important;
        border-color: #9ca3af !important;
        opacity: 0.7;
        transform: none !important;
        box-shadow: none !important;
    }

    .couple-seat-btn.HELD {
        background: #343a40 !important;
        border-color: #495057 !important;
        opacity: 0.7;
        transform: none !important;
        box-shadow: none !important;
    }

    /* === [FIX] Couple icon cho SOLD/BLOCKED/LOCKED ===
       === Đã sửa: gộp chung 3 trạng thái, icon màu #9ca3af === */
    .couple-seat-btn.SOLD .couple-icon,
    .couple-seat-btn.BLOCKED .couple-icon,
    .couple-seat-btn.LOCKED .couple-icon {
        color: #9ca3af !important;
        animation: none;
    }

    .couple-seat-btn.HELD .couple-icon {
        color: #6c757d !important;
        animation: none;
    }

    .sweet-seat-icon {
        color: #ec4899 !important;
    }

    /* LEGEND */
    .seat-legend {
        margin-top: 40px;
        padding: 25px;
        border-radius: 16px;
        background: #08111f;
        border: 1px solid rgba(255, 255, 255, .08);
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 35px;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #d1d5db;
        font-size: 14px;
    }

    .legend-item i {
        font-size: 24px;
    }

    /* Responsive */
    @media(max-width:1200px) {
        .seat-wrapper {
            flex-direction: column;
        }
        .seat-info {
            width: 100%;
            position: relative;
            top: 0;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const selectedSeats = new Map();
        const selectedSeatsEl = document.getElementById('selectedSeats');
        const totalPriceEl = document.getElementById('totalPrice');
        const hiddenSeatInputs = document.getElementById('hidden-seat-inputs');
        const btnPayment = document.getElementById('btnPayment');

        document.querySelector('.seat-map').addEventListener('click', (e) => {
            const btn = e.target.closest('.seat');
            if (!btn || btn.disabled) return;

            const seatIdAttr = btn.dataset.id;
            const seatCode = btn.dataset.seat;
            const seatType = btn.dataset.type;
            const seatPrice = parseInt(btn.dataset.price);

            const isSelecting = !btn.classList.contains('HELD_BY_ME');

            if (isSelecting) {
                selectedSeats.set(seatCode, {
                    id: seatIdAttr,
                    code: seatCode,
                    type: seatType,
                    price: seatPrice
                });
                btn.classList.add('HELD_BY_ME');
            } else {
                selectedSeats.delete(seatCode);
                btn.classList.remove('HELD_BY_ME');
            }
            updateUI();
        });

        // Hiển thị ghế đã chọn ở panel bên trái ngay lập tức khi click
        // (updateUI chạy sau mỗi lần chọn/hủy ghế)
        function updateUI() {
            hiddenSeatInputs.innerHTML = '';

            if (selectedSeats.size === 0) {
                selectedSeatsEl.textContent = 'Chưa chọn ghế';
                totalPriceEl.textContent = '0 VNĐ';
                btnPayment.disabled = true;
                btnPayment.textContent = 'Tiếp tục';
                return;
            }

            const seatTags = [];
            let total = 0;

            selectedSeats.forEach((seat) => {
                total += seat.price;

                const typeLabel = seat.type === 'vip'
                    ? '👑'
                    : (seat.type === 'COUPLE' || seat.type === 'sweetbox')
                        ? '💕'
                        : (seat.type === 'demo')
                            ? '🧪'
                            : '🎬';

                // seat.code bên staff hiện tại là kiểu string, có thể là "J1,J2" với couple
                const displayCode = seat.code.replace(',', '-');

                seatTags.push(
                    `<span class="seat-tag" style="display:inline-block; padding: 6px 10px; margin: 2px; background: rgba(255,255,255,0.1); border-radius: 6px;">
                        ${typeLabel} ${displayCode}
                    </span>`
                );

                // seats[] gửi về server là list id showtime_seat
                seat.id.split(',').forEach((id) => {
                    hiddenSeatInputs.innerHTML += `<input type="hidden" name="seats[]" value="${id}">`;
                });
            });

            selectedSeatsEl.innerHTML = seatTags.join('');
            totalPriceEl.textContent = total.toLocaleString('vi-VN') + ' VNĐ';
            btnPayment.disabled = false;
        }



    });
</script>
@endsection
