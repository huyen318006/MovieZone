@extends('layout.app')

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <section class="seat-page">
        <div class="seat-wrapper">
            <div class="seat-info">
                <img src="{{ $showtime->movie->poster }}" class="movie-poster" alt="Movie Poster">
                <h2>THÔNG TIN ĐẶT VÉ</h2>

                <div class="booking-summary">
                    <p><i class="fa-solid fa-film"></i> Phim: {{ $showtime->movie->title }}</p>
                    <p><i class="fa-solid fa-door-open"></i> Phòng chiếu: {{ $showtime->room->name }}</p>
                    <p><i class="fa-solid fa-clock"></i> Khung giờ:
                        {{ \Carbon\Carbon::parse($showtime->start_time)->format('H:i - d/m/Y') }}</p>
                </div>

                <div class="selected-seat-box">
                    {{-- Timer giữ ghế đồng bộ thiết kế mới --}}
                    <div id="timer-box" class="seat-timer-box" style="display: none;">
                        <div class="seat-timer-inner">
                            <div class="seat-timer-icon">
                                <i class="fa-solid fa-stopwatch"></i>
                            </div>
                            <div class="seat-timer-info">
                                <span class="seat-timer-label">Thời gian giữ ghế</span>
                                <span class="seat-timer-clock" id="clock">05:00</span>
                            </div>
                        </div>
                        <div class="seat-timer-progress">
                            <div class="seat-timer-progress-fill" id="seatTimerProgressFill"></div>
                        </div>
                    </div>

                    <h3>GHẾ ĐÃ CHỌN</h3>

                    <div id="selectedSeats">Chưa chọn ghế</div>

                    <div class="total-price" id="totalPrice">0VNĐ

                    </div>

                    <form action="{{ \App\Helpers\TabAuthHelper::route('booking.seats.submit') }}" method="POST" id="bookingForm">
                        @csrf
                        <input type="hidden" name="showtime_id" value="{{ $showtime->id }}">
                        <div id="hidden-seat-inputs"></div>



                        <button type="submit" class="btn-payment" id="btnPayment" disabled>
                            Vui lòng chọn ghế
                        </button>
                    </form>
                </div>
            </div>

            <div class="seat-map-container">
                <div class="screen-wrapper">
                    <div class="screen-curve"></div>
                    <div class="screen-text">SCREEN</div>
                </div>

                @if (session('error'))
                    <div class="alert alert-danger"
                        style="color: white; background: #dc3545; padding: 10px; text-align: center; margin-bottom: 20px; border-radius: 5px;">
                        {{ session('error') }}
                    </div>
                @endif
                <div id="ajax-error-box"
                    style="display: none; color: white; background: #dc3545; padding: 10px; text-align: center; margin-bottom: 20px; border-radius: 5px;">
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

                                        // Kiểm tra nếu là ghế COUPLE và ghế tiếp theo cũng là COUPLE (bắt cặp tự động)
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

                                        @if ($nextS['is_aisle'])
                                            <div class="aisle"></div>
                                        @endif
                                    @else
                                        @php
                                            if ($s['type'] === 'sweetbox') {
                                                $btnClass = 'sweet-seat-btn';
                                                $icon = '<i class="fa-solid fa-heart"></i> ';
                                            } elseif ($s['type'] === 'vip') {
                                                $btnClass = 'vip-seat-btn';
                                                $icon =
                                                    '<i class="fa-solid fa-crown" style="font-size: 11px; margin-right: 2px;"></i>';
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

                                        @if ($s['is_aisle'])
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
                    <div class="legend-item"><i class="fa-solid fa-couch" style="color: #64748b;"></i><span>Thường</span>
                    </div>
                    <div class="legend-item"><i class="fa-solid fa-crown vip-seat-icon"></i><span>VIP</span></div>
                    <div class="legend-item"><i class="fa-solid fa-heart sweet-seat-icon"></i><span>Sweetbox</span></div>
                    <div class="legend-item"><i class="fa-solid fa-couch held-seat" style="color: #28a745;"></i><span>Đã
                            chọn</span></div>
                    <div class="legend-item"><i class="fa-solid fa-couch" style="color: #ff9800;"></i><span>Đang giữ</span>
                    </div>
                    <div class="legend-item"><i class="fa-solid fa-couch sold-seat" style="color: #6b7280;"></i><span>Đã
                            đặt</span></div>
                </div>
            </div>
        </div>
    </section>

    <style>
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

        .SOLD {
            background-color: #6b7280 !important;
            color: white !important;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .BLOCKED,
        .LOCKED {
            background-color: #343a40 !important;
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

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.15);
            }
        }

        /* Trạng thái được chọn cho block gộp */
        .couple-seat-btn.HELD_BY_ME {
            background: linear-gradient(135deg, #28a745, #1e7e34) !important;
            border-color: #71e38c !important;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
        }

        .couple-seat-btn.HELD_BY_ME .couple-icon {
            color: #ffffff !important;
            animation: none;
        }

        /* Trạng thái đã bán / khóa cho block gộp */
        .couple-seat-btn.SOLD {
            background: #6b7280 !important;
            border-color: #9ca3af !important;
            opacity: 0.7;
            transform: none !important;
            box-shadow: none !important;
        }

        .couple-seat-btn.HELD,
        .couple-seat-btn.BLOCKED,
        .couple-seat-btn.LOCKED {
            background: #343a40 !important;
            border-color: #495057 !important;
            opacity: 0.7;
            transform: none !important;
            box-shadow: none !important;
        }

        .couple-seat-btn.SOLD .couple-icon {
            color: #d1d5db !important;
            animation: none;
        }

        .couple-seat-btn.HELD .couple-icon,
        .couple-seat-btn.BLOCKED .couple-icon,
        .couple-seat-btn.LOCKED .couple-icon {
            color: #6c757d !important;
            animation: none;
        }

.sweet-seat-icon {
            color: #ec4899 !important;
        }

        /* Row labels (A, B, C...) - bright white for readability */
        .row-label {
            color: #e2e8f0 !important;
        }

        /* === SEAT TIMER BOX (đồng bộ design mới) === */
        .seat-timer-box {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border: 1px solid #f59e0b;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 15px;
        }

        .seat-timer-inner {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .seat-timer-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(245, 158, 11, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: #f59e0b;
            flex-shrink: 0;
            animation: seatIconPulse 2s infinite;
        }

        @keyframes seatIconPulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }
        }

        .seat-timer-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .seat-timer-label {
            font-size: 11px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
        }

        .seat-timer-clock {
            font-size: 22px;
            font-weight: 800;
            color: #f59e0b;
            font-variant-numeric: tabular-nums;
            letter-spacing: 2px;
            line-height: 1;
        }

        .seat-timer-progress {
            width: 100%;
            height: 4px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            overflow: hidden;
        }

        .seat-timer-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #f59e0b, #eab308);
            border-radius: 10px;
            transition: width 1s linear;
            width: 100%;
        }

        /* Danger mode */
        .seat-timer-box.danger {
            border-color: #ef4444;
            animation: seatTimerPulse 1s infinite;
        }

        .seat-timer-box.danger .seat-timer-icon {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }

        .seat-timer-box.danger .seat-timer-clock {
            color: #ef4444;
            animation: seatTimeBlink 1s infinite;
        }

        .seat-timer-box.danger .seat-timer-progress-fill {
            background: linear-gradient(90deg, #ef4444, #dc2626);
        }

        @keyframes seatTimerPulse {

            0%,
            100% {
                border-color: #ef4444;
            }

            50% {
                border-color: rgba(239, 68, 68, 0.4);
            }
        }

        @keyframes seatTimeBlink {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        /* === EXPIRED MODAL cho seat page === */
        .seat-expired-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .seat-expired-overlay.show {
            display: flex;
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .seat-expired-modal {
            background: linear-gradient(145deg, #1e293b, #111827);
            border: 1px solid #374151;
            border-radius: 20px;
            padding: 40px;
            max-width: 420px;
            width: 90%;
            text-align: center;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6);
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateY(40px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .seat-expired-modal .expired-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid rgba(239, 68, 68, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
            color: #ef4444;
        }

        .seat-expired-modal h3 {
            color: #f8fafc;
            font-size: 20px;
            margin: 0 0 10px;
        }

        .seat-expired-modal p {
            color: #9ca3af;
            font-size: 14px;
            margin: 0 0 20px;
            line-height: 1.6;
        }

        .seat-expired-modal .btn-reload {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #ef4444;
            color: #fff;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .seat-expired-modal .btn-reload:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }
    </style>

    {{-- Modal hết giờ cho seat page --}}
    <div class="seat-expired-overlay" id="seatExpiredOverlay">
        <div class="seat-expired-modal">
            <div class="expired-icon">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <h3>Hết thời gian giữ ghế!</h3>
            <p>Phiên giữ ghế 5 phút đã hết. Vui lòng chọn lại ghế.</p>
            <a href="{{ \App\Helpers\TabAuthHelper::route('booking.seat', ['showtime_id' => $showtime->id]) }}" id="seatPageReloadLink" class="btn-reload">
                <i class="fa-solid fa-rotate-right"></i> Chọn ghế lại
            </a>
        </div>
    </div>

    {{-- Phát hiện khi user rời trang chọn ghế rồi quay lại → redirect với ?reset=1 để server reset timer --}}
    <script>
    (function() {
        // Bỏ qua AJAX refresh (polling 2.5s)
        if (new URLSearchParams(window.location.search).has('refresh')) return;

        var showtimeId = '{{ $showtime->id }}';
        var timerKey = 'seat_timer_active_' + showtimeId;
        var currentUrl = new URL(window.location.href);
        var hasReset = currentUrl.searchParams.get('reset') === '1';

        // Nếu đang xử lý ?reset=1 → đánh dấu lại cho lần sau, xóa ?reset khỏi URL
        if (hasReset) {
            sessionStorage.setItem(timerKey, '1');
            currentUrl.searchParams.delete('reset');
            history.replaceState(null, '', currentUrl.toString());
            return;
        }

        // Kiểm tra kiểu navigation: F5/reload → giữ nguyên timer, navigate/back_forward → reset
        var isReload = false;
        try {
            var navEntries = performance.getEntriesByType('navigation');
            if (navEntries.length > 0) {
                isReload = navEntries[0].type === 'reload';
            } else if (typeof performance.navigation !== 'undefined') {
                isReload = performance.navigation.type === 1;
            }
        } catch(e) {}

        // Nếu KHÔNG phải F5 VÀ đã từng vào trang này trước đó → user rời đi rồi quay lại → reset timer
        if (!isReload && sessionStorage.getItem(timerKey)) {
            currentUrl.searchParams.set('reset', '1');
            window.location.replace(currentUrl.toString());
            return;
        }

        // Lần đầu vào trang trong tab này → đánh dấu
        sessionStorage.setItem(timerKey, '1');

        // Xử lý bfcache: khi trình duyệt restore trang từ bộ nhớ (Back/Forward cache)
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                currentUrl.searchParams.set('reset', '1');
                window.location.replace(currentUrl.toString());
            }
        });
    })();
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selectedSeats = new Map();
            const selectedSeatsEl = document.getElementById('selectedSeats');
            const totalPriceEl = document.getElementById('totalPrice');
            const hiddenSeatInputs = document.getElementById('hidden-seat-inputs');
const btnPayment = document.getElementById('btnPayment');
            const bookingForm = document.getElementById('bookingForm');
            const seatPageReloadLink = document.getElementById('seatPageReloadLink');

            // Giới hạn tối đa số ghế 1 khách được chọn (lấy từ backend MAX_SEATS_PER_BOOKING — 1 nguồn duy nhất)
            const MAX_SEATS = {{ $maxSeats }};

            // === POPUP XÁC NHẬN ĐỦ TUỔI KHI CHUYỂN TRANG ===
            let seatAgeConfirmed = false;

            // === SERVER-AUTHORITATIVE TIMER ===
            const holdTotalSeconds = {{ $holdTotalSeconds ?? 300 }};
            let holdExpiresAt = @json($holdExpiresAt ?? null);
            let clockOffset = 0;

            // Tính clock offset từ serverTime ban đầu
            const initialServerTime = @json($serverTime ?? null);
            if (initialServerTime) {
                clockOffset = new Date(initialServerTime).getTime() - Date.now();
            }

            // Convert holdExpiresAt thành milliseconds
            let expiresAtMs = holdExpiresAt ? new Date(holdExpiresAt).getTime() : null;

            let timerInterval;
            let timerExpired = false;
            const currentSearch = window.location.search;
            const tabToken = new URLSearchParams(currentSearch).get('tab_token');

            let holdSeatUrl = new URL("{{ route('booking.holdSeat') }}", window.location.origin);
            if (tabToken) {
                holdSeatUrl.searchParams.set('tab_token', tabToken);
            }

            if (bookingForm && tabToken && !bookingForm.action.includes('tab_token=')) {
                bookingForm.action += (bookingForm.action.includes('?') ? '&' : '?') + 'tab_token=' + tabToken;
            }
            if (seatPageReloadLink && tabToken && !seatPageReloadLink.href.includes('tab_token=')) {
                seatPageReloadLink.href += (seatPageReloadLink.href.includes('?') ? '&' : '?') + 'tab_token=' + tabToken;
            }

            /**
             * Tính seconds left dựa trên server timestamp + clock offset.
             */
            function getSecondsLeft() {
                if (!expiresAtMs) return 0;
                const effectiveNow = Date.now() + clockOffset;
                return Math.max(0, Math.floor((expiresAtMs - effectiveNow) / 1000));
            }

            // 1. TỰ ĐỘNG KHÔI PHỤC GHẾ ĐANG CHỌN (Từ class HELD_BY_ME trên màn hình)
            document.querySelectorAll('.HELD_BY_ME').forEach(btn => {
                const seatCode = btn.dataset.seat;
                selectedSeats.set(seatCode, {
                    id: btn.dataset.id,
                    code: seatCode,
                    type: btn.dataset.type,
                    price: parseInt(btn.dataset.price)
                });
            });

            // 2. BẮT ĐẦU CHẠY TIMER LUÔN KHI VÀO TRANG (KHÔNG CẦN CHỜ CHỌN GHẾ)
            if (selectedSeats.size > 0) {
                updateUI();
            }
            if (expiresAtMs && getSecondsLeft() > 0) {
                startTimer();
            }

            function startTimer() {
                document.getElementById('timer-box').style.display = 'block';
                updateSeatTimerDisplay();
                timerInterval = setInterval(() => {
                    const remaining = getSecondsLeft();
                    if (remaining <= 0) {
                        timerExpired = true;
                        clearInterval(timerInterval);
                        updateSeatTimerDisplay();
                        // Hiển thị modal hết giờ
                        document.getElementById('seatExpiredOverlay').classList.add('show');
                        return;
                    }
                    updateSeatTimerDisplay();
                }, 1000);
            }

            function updateSeatTimerDisplay() {
                const safe = getSecondsLeft();
                let m = Math.floor(safe / 60).toString().padStart(2, '0');
                let s = (safe % 60).toString().padStart(2, '0');
                document.getElementById('clock').textContent = m + ':' + s;

                // Progress bar
                const pct = (safe / holdTotalSeconds) * 100;
                const fillEl = document.getElementById('seatTimerProgressFill');
                if (fillEl) fillEl.style.width = pct + '%';

                // Danger mode khi còn < 60 giây
                const timerBox = document.getElementById('timer-box');
                if (safe <= 60 && safe > 0 && timerBox) {
                    timerBox.classList.add('danger');
                }
            }

            /**
             * VISIBILITY CHANGE: Khi user quay lại tab sau khi tab bị background,
             * tính lại thời gian ngay lập tức.
             */
            document.addEventListener('visibilitychange', function() {
                if (document.visibilityState === 'visible' && expiresAtMs && !timerExpired) {
                    const remaining = getSecondsLeft();
                    updateSeatTimerDisplay();
                    if (remaining <= 0) {
                        timerExpired = true;
                        clearInterval(timerInterval);
                        document.getElementById('seatExpiredOverlay').classList.add('show');
                    }
                }
            });

            document.querySelector('.seat-map').addEventListener('click', async (e) => {
                const btn = e.target.closest('.seat');
                if (!btn || btn.disabled) return;

                const seatIdAttr = btn.dataset.id;
                const seatCode = btn.dataset.seat;
                const seatType = btn.dataset.type;
                const seatPrice = parseInt(btn.dataset.price);

const seatIds = seatIdAttr.split(',');

                const isSelecting = !btn.classList.contains('HELD_BY_ME');
                const action = isSelecting ? 'hold' : 'release';

                // Chặn chọn quá MAX_SEATS ghế (đồng bộ với backend MAX_SEATS_PER_BOOKING)
                // Lưu ý: Map key = seatCode, nhưng ghế COUPLE gộp 2 ghế (seat.id = "J1,J2").
                // Vì vậy phải đếm số ghế THỰC SỰ (tổng các id trong Map) chứ không dùng selectedSeats.size.
                if (isSelecting) {
                    let currentSeatCount = 0;
                    selectedSeats.forEach((seat) => {
                        currentSeatCount += seat.id.split(',').length;
                    });

                    if (currentSeatCount + seatIds.length > MAX_SEATS) {
                        const errorBox = document.getElementById('ajax-error-box');
                        errorBox.innerText = `Bạn chỉ được chọn tối đa ${MAX_SEATS} ghế cho 1 lần đặt vé.`;
                        errorBox.style.display = 'block';
                        errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        return;
                    }
                }

                try {
                    let allSuccess = true;
                    let lastMessage = '';
                    let lastServerTime = null;
                    let lastExpiresAt = null;

                    for (const sId of seatIds) {
                        const response = await fetch(holdSeatUrl.toString(), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                showtime_id: "{{ $showtime->id }}",
                                seat_id: sId,
                                action: action
                            })
                        });

                        const res = await response.json();
                        if (!res.success) {
                            allSuccess = false;
                            if (res.error_type === 'HELD' || res.error_type === 'UNAVAILABLE') {
                                btn.classList.remove('AVAILABLE', 'available-seat-btn', 'vip-seat-btn', 'sweet-seat-btn', 'HELD_BY_ME');
                                btn.classList.add(res.error_type === 'HELD' ? 'HELD' : 'SOLD');
                                btn.disabled = true;
                            } else {
                                lastMessage = res.message;

                                // Nếu timer hết hạn, hiển thị modal
                                if (res.error_type === 'EXPIRED') {
                                    document.getElementById('seatExpiredOverlay').classList.add('show');
                                }
                            }
                            break;
                        }

                        // Cập nhật serverTime và expiresAt từ response
                        if (res.serverTime) lastServerTime = res.serverTime;
                        if (res.expiresAt) lastExpiresAt = res.expiresAt;
                    }

                    if (allSuccess) {
                        // Cập nhật clock offset và expiresAt từ response mới nhất
                        if (lastServerTime) {
                            clockOffset = new Date(lastServerTime).getTime() - Date.now();
                        }
                        if (lastExpiresAt) {
                            expiresAtMs = new Date(lastExpiresAt).getTime();
                        }

                        if (isSelecting) {
                            // Timer đã chạy sẵn từ lúc load trang, không cần gọi startTimer() nữa
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
                    } else {
                        if (lastMessage) {
                            document.getElementById('ajax-error-box').innerText = lastMessage;
                            document.getElementById('ajax-error-box').style.display = 'block';
                            setTimeout(() => location.reload(), 1500);
                        }
                    }
                } catch (error) {
                    document.getElementById('ajax-error-box').innerText =
                        'Lỗi kết nối hệ thống.'; // E5: Lỗi kết nối
                    document.getElementById('ajax-error-box').style.display = 'block';
                } finally {
                    btn.disabled = false;
                }
            });

            if (window.Echo && typeof window.Echo.channel === 'function') {
                window.Echo.channel(`showtime.{{ $showtime->id }}`)
                    .listen('.seat.updated', (payload) => {
                        if (!payload || payload.showtime_id != {{ $showtime->id }}) {
                            return;
                        }

                        const updatedSeatId = String(payload.seat_id);
                        const status = payload.status;
                        const seatButtons = document.querySelectorAll('.seat[data-id]');

                        seatButtons.forEach((b) => {
                            const ids = b.dataset.id.split(',');
                            if (!ids.includes(updatedSeatId)) return;

                            if (b.classList.contains('HELD_BY_ME') && status === 'HELD') return;

                            b.classList.remove('AVAILABLE', 'HELD', 'HELD_BY_ME', 'SOLD', 'BLOCKED', 'LOCKED', 'available-seat-btn', 'vip-seat-btn', 'sweet-seat-btn');
                            b.disabled = false;

                            if (status === 'AVAILABLE') {
                                if (b.dataset.type === 'vip') {
                                    b.classList.add('vip-seat-btn');
                                } else if (b.dataset.type === 'COUPLE') {
                                    b.classList.add('sweet-seat-btn');
                                } else {
                                    b.classList.add('available-seat-btn');
                                }
                            } else if (status === 'HELD') {
                                b.classList.add('HELD');
                                b.disabled = true;
                            } else if (status === 'SOLD') {
                                b.classList.add('SOLD');
                                b.disabled = true;
                            } else if (status === 'BLOCKED' || status === 'LOCKED') {
                                b.classList.add(status);
                                b.disabled = true;
                            }
                        });
                    });
            }

// =================================================================
            // TỰ ĐỘNG CẬP NHẬT GHẾ (giống Staff): khi staff/người khác chọn ghế
            // thì trang customer tự quét lại sơ đồ ghế mỗi 2,5 giây.
            // Ghế customer đang chọn (HELD_BY_ME) sẽ được giữ màu xanh lá.
            // =================================================================
            function seatTypeClass(type) {
                if (type === 'vip') return 'vip-seat-btn';
                if (type === 'COUPLE') return 'couple-seat-btn';
                if (type === 'sweetbox') return 'sweet-seat-btn';
                return 'available-seat-btn';
            }

            function restoreSelectedSeatsOnRefreshedMap() {
                const seatButtons = document.querySelectorAll('.seat[data-seat]');
                selectedSeats.forEach((seat, seatCode) => {
                    let restored = false;

                    seatButtons.forEach((btn) => {
                        if (btn.dataset.seat !== seat.code) {
                            return;
                        }

                        const currentClassList = Array.from(btn.classList);
                        const isUnavailable = currentClassList.some((cls) => ['HELD', 'SOLD', 'BLOCKED', 'LOCKED'].includes(cls));

                        if (!isUnavailable) {
                            btn.classList.remove('AVAILABLE', 'HELD', 'HELD_BY_ME', 'SOLD', 'BLOCKED', 'LOCKED', 'available-seat-btn', 'vip-seat-btn', 'sweet-seat-btn');
                            btn.classList.add('HELD_BY_ME', seatTypeClass(seat.type));
                            btn.disabled = false;
                            restored = true;
                        }
                    });
                });
            }

            async function refreshSeatStates() {
                try {
                    const refreshUrl = new URL("{{ \App\Helpers\TabAuthHelper::route('booking.seat', ['showtime_id' => $showtime->id]) }}", window.location.origin);
                    refreshUrl.searchParams.set('refresh', '1');

                    const response = await fetch(refreshUrl.toString(), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const html = await response.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newSeatMap = doc.querySelector('.seat-map');
                    if (!newSeatMap) return;

                    const currentSeatMap = document.querySelector('.seat-map');
                    if (currentSeatMap) {
                        currentSeatMap.innerHTML = newSeatMap.innerHTML;
                        restoreSelectedSeatsOnRefreshedMap();
                    }
                } catch (error) {
                    console.error('Refresh seat states failed', error);
                }
            }

            setInterval(refreshSeatStates, 2500);

            function updateUI() {
                hiddenSeatInputs.innerHTML = '';
                if (selectedSeats.size === 0) {
                    selectedSeatsEl.innerHTML = 'Chưa chọn ghế';
                    totalPriceEl.textContent = '0 VNĐ';
                    btnPayment.disabled = true;
                    btnPayment.textContent = 'Vui lòng chọn ghế';
                } else {
                    const seatTags = [];
                    let total = 0;
                    selectedSeats.forEach((seat) => {
                        total += seat.price;
                        const typeLabel = seat.type === 'vip' ? '👑' : seat.type === 'COUPLE' ? '💕' :
                            seat.type === 'demo' ? '🧪' : '🎬';
                        // Hiển thị đẹp hơn cho COUPLE (ví dụ J1,J2 -> J1-J2)
                        const displayCode = seat.code.replace(',', '-');
                        seatTags.push(
                            `<span class="seat-tag seat-tag-${seat.type}">${typeLabel} ${displayCode}</span>`
                        );

                        const ids = seat.id.split(',');
                        ids.forEach(id => {
                            hiddenSeatInputs.innerHTML +=
                                `<input type="hidden" name="seats[]" value="${id}">`;
                        });
                    });
                    selectedSeatsEl.innerHTML = seatTags.join(' ');
                    totalPriceEl.textContent = total.toLocaleString('vi-VN') + 'VNĐ';
                    btnPayment.disabled = false;
                    btnPayment.textContent = `Tiếp tục`;
                }
            }
            // =================================================================
            // ĐOẠN MÃ MỚI: KIỂM TRA LỖI LẺ 1 GHẾ TRỐNG KHI BẤM TIẾP TỤC
            // =================================================================
            document.getElementById('bookingForm').addEventListener('submit', function(e) {
                const allSeats = document.querySelectorAll('.seat');
                let seatMap = {};

                // 1. Quét toàn bộ cấu hình ghế hiển thị trên màn hình hiện tại
                allSeats.forEach(seat => {
                    const seatCode = seat.getAttribute('data-seat');
                    if (!seatCode) return;

                    // Phân loại trạng thái ghế chuẩn theo Class hiện tại trên giao diện
                    let status = 'available';
                    if (seat.classList.contains('HELD_BY_ME')) {
                        status = 'selected'; // Ghế khách này đang chọn
                    } else if (
                        seat.classList.contains('SOLD') ||
                        seat.classList.contains('HELD') ||
                        seat.classList.contains('BLOCKED') ||
                        seat.classList.contains('LOCKED') ||
                        seat.disabled
                    ) {
                        status = 'unavailable'; // Ghế không thể mua
                    }

                    // Hỗ trợ xử lý data-seat chứa nhiều ghế (COUPLE gộp, ví dụ "J1,J2")
                    const codes = seatCode.split(',');
                    codes.forEach(code => {
                        const rowMatch = code.match(/[a-zA-Z]+/);
                        const numMatch = code.match(/\d+/);
                        if (!rowMatch || !numMatch) return;

                        const row = rowMatch[0].toUpperCase();
                        const num = parseInt(numMatch[0]);

                        if (!seatMap[row]) {
                            seatMap[row] = {};
                        }
                        seatMap[row][num] = status;
                    });
                });

                let orphanSeats = []; // Gom các mã ghế trống đang bị để lẻ để báo rõ cho khách

                // 2. Thuật toán kiểm tra ghế trống bị cô lập thông minh
                for (let row in seatMap) {
                    // NGOẠI LỆ (QUYẾT ĐỊNH CUỐI): Đếm số ghế trống của hàng TRƯỚC khi khách chọn
                    // (= available + selected trên hàng). Nếu ≤ 2 VÀ khách chọn đúng 1 ghế ở hàng này
                    // → bỏ qua kiểm tra "lẻ 1 ghế trống".
                    // Lý do: hàng chỉ còn tối đa 2 ghế trống mà khách chỉ mua 1 ghế thì 1 ghế sót lại
                    // là không thể tránh khỏi (không thể ghép cặp được).
                    // Đếm theo kiểu "trước khi chọn" (<=> sau khi đặt chỉ còn ≤ 1 ghế trống) thay vì
                    // "sau khi chọn ≤ 2" để CHẶN lỗ hổng: hàng còn 3 ghế trống liên tiếp (A8,A9,A10) mà
                    // khách chọn ghế giữa A9 → nếu đếm "sau khi chọn" sẽ ra 2 (≤2) và bị bỏ qua, để lại
                    // 2 ghế lẻ rời rạc. Đếm "trước khi chọn" = 3 (>2) nên vẫn báo lỗi đúng.
                    let remainingEmptyBefore = 0;
                    let selectedInRow = 0;
                    for (let numStr in seatMap[row]) {
                        if (seatMap[row][numStr] === 'available' || seatMap[row][numStr] === 'selected') {
                            remainingEmptyBefore++;
                        }
                        if (seatMap[row][numStr] === 'selected') {
                            selectedInRow++;
                        }
                    }
                    if (remainingEmptyBefore <= 2 && selectedInRow === 1) {
                        continue;
                    }

                    for (let numStr in seatMap[row]) {
                        let num = parseInt(numStr);

                        // CHỈ xét những ghế đang thực sự TRỐNG (available)
                        if (seatMap[row][num] === 'available') {
                            // Lấy trạng thái 2 bên cạnh (Nếu không có ghế bên cạnh -> coi như là Tường/Lối đi)
                            let leftStatus = seatMap[row][num - 1] || 'wall';
                            let rightStatus = seatMap[row][num + 1] || 'wall';

                            // Ghế trống này bị chặn 2 bên nếu hàng xóm không phải là ghế trống ('available')
                            let isLeftBlocked = (leftStatus !== 'available');
                            let isRightBlocked = (rightStatus !== 'available');

                            // Nếu bị kẹp cứng cả 2 bên (Trở thành ghế lẻ duy nhất)
                            if (isLeftBlocked && isRightBlocked) {
                                // CHỈ tính là lỗi nếu ít nhất 1 trong 2 bên chặn nó là ghế DO KHÁCH NÀY ĐANG CHỌN ('selected')
                                if (leftStatus === 'selected' || rightStatus === 'selected') {
                                    // Gom lại mã ghế bị bỏ trống để báo rõ cho khách
                                    const orphanCode = row + String(num).padStart(2, '0');
                                    if (!orphanSeats.includes(orphanCode)) {
                                        orphanSeats.push(orphanCode);
                                    }
                                }
                            }
                        }
                    }
                }

                const hasError = orphanSeats.length > 0;

                // 3. Xử lý xuất thông báo trực quan cho khách hàng
                if (hasError) {
                    e.preventDefault(); // Chặn đứng hành động gửi form lên hệ thống thanh toán

                    const errorBox = document.getElementById('ajax-error-box');
                    const seatLabel = orphanSeats.join(', ');
                    const summary = orphanSeats.length > 1
                        ? `Bạn đang bỏ trống các ghế lẻ: <b>${seatLabel}</b>.`
                        : `Bạn đang bỏ trống 1 ghế lẻ: <b>${seatLabel}</b>.`;

                    if (errorBox) {
                        errorBox.innerHTML =
                            `<i class="fa-solid fa-triangle-exclamation"></i> ${summary} Vui lòng chọn thêm ghế liền kề hoặc đổi vị trí để không tạo ghế trống đơn lẻ trong hàng.`;
                        errorBox.style.display = 'block'; // Hiện hộp thông báo màu đỏ lên

                        // Tự động cuộn màn hình mượt mà đến vùng báo lỗi để khách nhìn thấy ngay lập tức
                        errorBox.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    } else {
                        // Phòng hờ nếu lỗi giao diện không tìm thấy thẻ div thì xài tạm alert mặc định
                        alert(`${summary} Vui lòng chọn thêm ghế liền kề hoặc đổi vị trí.`);
                    }
                }

                // === POPUP XÁC NHẬN ĐỦ TUỔI TRƯỚC KHI CHUYỂN TRANG ===
                // Luôn hiện sau khi chọn ghế (không phụ thuộc rating phim)
                if (! hasError && ! seatAgeConfirmed) {
                    e.preventDefault();

                    const overlay = document.getElementById('ageConfirmOverlay');
                    if (overlay) {
                        overlay.classList.add('show');
                    }
                }

            }); // hết listener submit

            // === XỬ LÝ MODAL XÁC NHẬN ĐỦ TUỔI (chạy 1 lần) ===
            const ageOkBtn = document.getElementById('ageConfirmOk');
            const ageCancelBtn = document.getElementById('ageConfirmCancel');
            const ageOverlayEl = document.getElementById('ageConfirmOverlay');

            if (ageOkBtn) {
                ageOkBtn.addEventListener('click', function() {
                    seatAgeConfirmed = true;
                    if (ageOverlayEl) ageOverlayEl.classList.remove('show');
                    bookingForm.submit(); // gửi trực tiếp, tránh chạy lại listener submit
                });
            }
            if (ageCancelBtn) {
                ageCancelBtn.addEventListener('click', function() {
                    if (ageOverlayEl) ageOverlayEl.classList.remove('show');
                });
            }
        }); // hết DOMContentLoaded
    </script>

{{-- ===== MODAL XÁC NHẬN ĐỦ TUỔI (giống các rạp phim lớn) ===== --}}
<div class="age-confirm-overlay" id="ageConfirmOverlay">
    <div class="age-confirm-modal">
        <div class="age-confirm-icon">
            <i class="fa-solid fa-id-card"></i>
        </div>
        <h3>Xác nhận thông tin</h3>
        <p>Tôi xác nhận đủ số tuổi mà phim yêu cầu và hiểu rằng MovieZone sẽ không hoàn lại tiền nếu không chứng thực được độ tuổi của khán giả.</p>
        <div class="age-confirm-actions">
            <button type="button" class="age-btn age-btn-cancel" id="ageConfirmCancel">Hủy</button>
            <button type="button" class="age-btn age-btn-ok" id="ageConfirmOk">Xác nhận</button>
        </div>
    </div>
</div>

<style>
    .age-confirm-overlay {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 1200;
        background: rgba(2, 6, 23, 0.75);
        backdrop-filter: blur(4px);
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .age-confirm-overlay.show {
        display: flex;
    }
    .age-confirm-modal {
        background: linear-gradient(160deg, #1e293b 0%, #0f172a 100%);
        border: 1px solid rgba(59, 130, 246, 0.35);
        border-radius: 18px;
        max-width: 420px;
        width: 100%;
        padding: 34px 30px 28px;
        text-align: center;
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.55);
        animation: ageSlideUp 0.3s ease;
    }
    @keyframes ageSlideUp {
        from { transform: translateY(24px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .age-confirm-icon {
        width: 68px;
        height: 68px;
        margin: 0 auto 16px;
        border-radius: 50%;
        background: rgba(59, 130, 246, 0.15);
        border: 2px solid rgba(59, 130, 246, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        color: #60a5fa;
    }
    .age-confirm-modal h3 {
        color: #f8fafc;
        font-size: 20px;
        font-weight: 700;
        margin: 0 0 10px;
    }
    .age-confirm-modal p {
        color: #cbd5e1;
        font-size: 14.5px;
        line-height: 1.6;
        margin: 0 0 24px;
    }
    .age-confirm-modal p b {
        color: #3b82f6;
    }
    .age-confirm-actions {
        display: flex;
        gap: 12px;
    }
    .age-btn {
        flex: 1;
        padding: 12px 14px;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .age-btn-cancel {
        background: rgba(148, 163, 184, 0.15);
        color: #cbd5e1;
        border: 1px solid rgba(148, 163, 184, 0.3);
    }
    .age-btn-cancel:hover {
        background: rgba(148, 163, 184, 0.25);
    }
    .age-btn-ok {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: #fff;
    }
    .age-btn-ok:hover {
        filter: brightness(1.1);
    }
</style>
@endsection
