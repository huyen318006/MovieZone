{{-- ============================================================
     BOOKING COUNTDOWN TIMER - Component tái sử dụng
     Include vào bất kỳ trang nào cần hiển thị bộ đếm ngược:
     @include('booking._countdown_timer', ['secondsLeft' => $secondsLeft])
     ============================================================ --}}

{{-- Thanh countdown nổi sticky ở đầu trang --}}
<div class="booking-countdown-bar" id="bookingCountdownBar">
    <div class="countdown-inner">
        <div class="countdown-left">
            <div class="countdown-icon-pulse">
                <i class="fa-solid fa-stopwatch"></i>
            </div>
            <div class="countdown-label">
                <span class="countdown-label-text">Thời gian giữ ghế</span>
                <span class="countdown-time" id="countdownClock">05:00</span>
            </div>
        </div>
        <div class="countdown-right">
            <div class="countdown-progress-track">
                <div class="countdown-progress-fill" id="countdownProgressFill"></div>
            </div>
        </div>
    </div>
</div>

{{-- Modal hết thời gian --}}
<div class="countdown-expired-overlay" id="countdownExpiredOverlay">
    <div class="countdown-expired-modal">
        <div class="expired-icon-wrapper">
            <i class="fa-solid fa-clock-rotate-left"></i>
        </div>
        <h3>Hết thời gian giữ ghế!</h3>
        <p>Phiên giữ ghế 5 phút đã hết. Ghế của bạn đã được giải phóng cho khách khác.</p>
        <div class="expired-countdown-redirect">
            <div class="expired-spinner"></div>
            <span>Đang chuyển về chọn ghế... (<span id="redirectCountdown">3</span>s)</span>
        </div>
        @php
            $resolvedShowtimeId = $showtime_id ?? (session('booking_tam.showtime_id') ?? null);
        @endphp
        <a href="{{ $resolvedShowtimeId
            ? \App\Helpers\TabAuthHelper::route('booking.seat', ['showtime_id' => $resolvedShowtimeId])
            : \App\Helpers\TabAuthHelper::route('showtimes') }}" class="expired-btn-back">
            <i class="fa-solid fa-arrow-left"></i> Chọn ghế lại
        </a>
    </div>
</div>

<style>
/* ==========================================
   COUNTDOWN BAR - Sticky top
   ========================================== */
.booking-countdown-bar {
    position: sticky;
    top: 0;
    z-index: 900;
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border-bottom: 2px solid #f59e0b;
    padding: 14px 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
    transition: all 0.4s ease;
}

.countdown-inner {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
}

.countdown-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.countdown-icon-pulse {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: rgba(245, 158, 11, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #f59e0b;
    animation: iconPulse 2s infinite;
    flex-shrink: 0;
}

@keyframes iconPulse {
    0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.3); }
    50% { transform: scale(1.05); box-shadow: 0 0 0 8px rgba(245, 158, 11, 0); }
}

.countdown-label {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.countdown-label-text {
    font-size: 12px;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 500;
}

.countdown-time {
    font-size: 28px;
    font-weight: 800;
    color: #f59e0b;
    font-variant-numeric: tabular-nums;
    letter-spacing: 2px;
    line-height: 1;
    text-shadow: 0 0 20px rgba(245, 158, 11, 0.3);
}

.countdown-right {
    flex: 1;
    max-width: 400px;
}

.countdown-progress-track {
    width: 100%;
    height: 6px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    overflow: hidden;
}

.countdown-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #f59e0b, #eab308);
    border-radius: 10px;
    transition: width 1s linear;
    width: 100%;
    box-shadow: 0 0 8px rgba(245, 158, 11, 0.4);
}

/* === DANGER MODE (còn < 60 giây) === */
.booking-countdown-bar.danger {
    border-bottom-color: #ef4444;
    animation: barPulseDanger 1s infinite;
}

.booking-countdown-bar.danger .countdown-icon-pulse {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
    animation: iconPulseDanger 0.8s infinite;
}

.booking-countdown-bar.danger .countdown-time {
    color: #ef4444;
    text-shadow: 0 0 20px rgba(239, 68, 68, 0.4);
    animation: timeBlink 1s infinite;
}

.booking-countdown-bar.danger .countdown-progress-fill {
    background: linear-gradient(90deg, #ef4444, #dc2626);
    box-shadow: 0 0 8px rgba(239, 68, 68, 0.5);
}

@keyframes barPulseDanger {
    0%, 100% { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); }
    50% { background: linear-gradient(135deg, #2a1215 0%, #1a0a0c 100%); }
}

@keyframes iconPulseDanger {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.15); }
}

@keyframes timeBlink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

/* ==========================================
   EXPIRED MODAL OVERLAY
   ========================================== */
.countdown-expired-overlay {
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

.countdown-expired-overlay.show {
    display: flex;
    animation: fadeInOverlay 0.4s ease;
}

@keyframes fadeInOverlay {
    from { opacity: 0; }
    to { opacity: 1; }
}

.countdown-expired-modal {
    background: linear-gradient(145deg, #1e293b, #111827);
    border: 1px solid #374151;
    border-radius: 20px;
    padding: 40px;
    max-width: 440px;
    width: 90%;
    text-align: center;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6);
    animation: slideUpModal 0.5s ease;
}

@keyframes slideUpModal {
    from { transform: translateY(40px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.expired-icon-wrapper {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(239, 68, 68, 0.1);
    border: 2px solid rgba(239, 68, 68, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 36px;
    color: #ef4444;
    animation: expiredIconPulse 1.5s infinite;
}

@keyframes expiredIconPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.3); }
    50% { box-shadow: 0 0 0 15px rgba(239, 68, 68, 0); }
}

.countdown-expired-modal h3 {
    color: #f8fafc;
    font-size: 22px;
    margin: 0 0 10px;
    font-weight: 700;
}

.countdown-expired-modal p {
    color: #9ca3af;
    font-size: 15px;
    line-height: 1.6;
    margin: 0 0 24px;
}

.expired-countdown-redirect {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: #f59e0b;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 20px;
}

.expired-spinner {
    width: 18px;
    height: 18px;
    border: 2px solid rgba(245, 158, 11, 0.3);
    border-top: 2px solid #f59e0b;
    border-radius: 50%;
    animation: spinLoader 0.8s linear infinite;
}

@keyframes spinLoader {
    to { transform: rotate(360deg); }
}

.expired-btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #ef4444;
    color: #fff;
    padding: 12px 28px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 15px;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
}

.expired-btn-back:hover {
    background: #dc2626;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
}

/* ==========================================
   RESPONSIVE
   ========================================== */
@media (max-width: 640px) {
    .booking-countdown-bar {
        padding: 10px 16px;
    }
    .countdown-inner {
        flex-direction: column;
        gap: 10px;
    }
    .countdown-time {
        font-size: 22px;
    }
    .countdown-right {
        max-width: 100%;
        width: 100%;
    }
    .countdown-expired-modal {
        padding: 28px 20px;
    }
}
</style>

<script>
(function() {
    let secondsLeft = Math.max(0, {{ $secondsLeft ?? 300 }});
    const totalSeconds = 300; // 5 phút
    const clockEl = document.getElementById('countdownClock');
    const barEl = document.getElementById('bookingCountdownBar');
    const fillEl = document.getElementById('countdownProgressFill');
    const overlay = document.getElementById('countdownExpiredOverlay');
    const redirectCountdownEl = document.getElementById('redirectCountdown');

// Lấy showtime_id từ URL hoặc session để build redirect URL
    const seatPageUrl = "{{ $resolvedShowtimeId
        ? \App\Helpers\TabAuthHelper::route('booking.seat', ['showtime_id' => $resolvedShowtimeId])
        : \App\Helpers\TabAuthHelper::route('showtimes') }}";

    function updateDisplay() {
        if (!clockEl || !fillEl) return;

        const safe = Math.max(0, secondsLeft);
        const m = Math.floor(safe / 60).toString().padStart(2, '0');
        const s = (safe % 60).toString().padStart(2, '0');
        clockEl.textContent = m + ':' + s;

        // Progress bar
        const pct = (safe / totalSeconds) * 100;
        fillEl.style.width = pct + '%';

        // Danger mode khi còn < 60 giây
        if (safe <= 60 && safe > 0 && barEl) {
            barEl.classList.add('danger');
        }
    }

    const interval = setInterval(function() {
        secondsLeft--;
        if (secondsLeft <= 0) {
            secondsLeft = 0;
            clearInterval(interval);
            updateDisplay();
            showExpiredModal();
            return;
        }
        updateDisplay();
    }, 1000);

    // Hiển thị ngay khi load
    updateDisplay();

    function showExpiredModal() {
        if (!overlay) return;
        overlay.classList.add('show');

        // Đếm ngược redirect 3 giây
        let redirectSeconds = 3;
        const redirectInterval = setInterval(function() {
            redirectSeconds--;
            if (redirectCountdownEl) {
                redirectCountdownEl.textContent = redirectSeconds;
            }
            if (redirectSeconds <= 0) {
                clearInterval(redirectInterval);
                window.location.href = seatPageUrl;
            }
        }, 1000);
    }
})();
</script>
