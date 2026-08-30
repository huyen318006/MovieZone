{{-- ============================================================
     SHOWTIME CANCELLED CHECK - Component tái sử dụng
     Polling kiểm tra trạng thái suất chiếu mỗi 3 giây.
     Khi admin huỷ suất chiếu → hiển thị modal và redirect về trang chủ.
     
     Include vào bất kỳ trang booking nào cần kiểm tra:
     @include('booking._showtime_cancelled_check', ['checkShowtimeId' => $showtime->id])
     ============================================================ --}}

{{-- Modal thông báo suất chiếu đã bị huỷ --}}
<div class="showtime-cancelled-overlay" id="showtimeCancelledOverlay">
    <div class="showtime-cancelled-modal">
        <div class="cancelled-icon-wrapper">
            <i class="fa-solid fa-ban"></i>
        </div>
        <h3>Suất chiếu đã bị huỷ!</h3>
        <p id="cancelledMessage">Suất chiếu bạn đang đặt vé đã bị huỷ bởi quản trị viên.</p>
        <div class="cancelled-reason" id="cancelledReason" style="display: none;">
            <i class="fa-solid fa-quote-left"></i>
            <span id="cancelledReasonText"></span>
        </div>
        <div class="cancelled-redirect-info">
            <div class="cancelled-spinner"></div>
            <span>Đang chuyển về trang chủ... (<span id="cancelledRedirectCountdown">5</span>s)</span>
        </div>
        <a href="{{ route('home') }}" class="cancelled-btn-home" id="cancelledBtnHome">
            <i class="fa-solid fa-house"></i> Về trang chủ ngay
        </a>
    </div>
</div>

<style>
/* ==========================================
   SHOWTIME CANCELLED MODAL OVERLAY
   ========================================== */
.showtime-cancelled-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.9);
    backdrop-filter: blur(10px);
    z-index: 99999;
    align-items: center;
    justify-content: center;
}

.showtime-cancelled-overlay.show {
    display: flex;
    animation: cancelledFadeIn 0.4s ease;
}

@keyframes cancelledFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.showtime-cancelled-modal {
    background: linear-gradient(145deg, #1e293b, #111827);
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 20px;
    padding: 40px;
    max-width: 460px;
    width: 90%;
    text-align: center;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6), 0 0 40px rgba(239, 68, 68, 0.1);
    animation: cancelledSlideUp 0.5s ease;
}

@keyframes cancelledSlideUp {
    from { transform: translateY(40px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.cancelled-icon-wrapper {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(239, 68, 68, 0.1);
    border: 2px solid rgba(239, 68, 68, 0.35);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 36px;
    color: #ef4444;
    animation: cancelledIconPulse 1.5s infinite;
}

@keyframes cancelledIconPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.3); }
    50% { box-shadow: 0 0 0 15px rgba(239, 68, 68, 0); }
}

.showtime-cancelled-modal h3 {
    color: #f8fafc;
    font-size: 22px;
    margin: 0 0 10px;
    font-weight: 700;
}

.showtime-cancelled-modal p {
    color: #9ca3af;
    font-size: 15px;
    line-height: 1.6;
    margin: 0 0 16px;
}

.cancelled-reason {
    background: rgba(239, 68, 68, 0.08);
    border: 1px solid rgba(239, 68, 68, 0.2);
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 20px;
    text-align: left;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.cancelled-reason i {
    color: #ef4444;
    font-size: 14px;
    margin-top: 2px;
    flex-shrink: 0;
}

.cancelled-reason span {
    color: #fca5a5;
    font-size: 14px;
    line-height: 1.5;
    font-style: italic;
}

.cancelled-redirect-info {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: #f59e0b;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 20px;
}

.cancelled-spinner {
    width: 18px;
    height: 18px;
    border: 2px solid rgba(245, 158, 11, 0.3);
    border-top: 2px solid #f59e0b;
    border-radius: 50%;
    animation: cancelledSpin 0.8s linear infinite;
}

@keyframes cancelledSpin {
    to { transform: rotate(360deg); }
}

.cancelled-btn-home {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #fff;
    padding: 14px 28px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 15px;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
}

.cancelled-btn-home:hover {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
}

@media (max-width: 640px) {
    .showtime-cancelled-modal {
        padding: 28px 20px;
        margin: 20px;
    }
}
</style>

<script>
(function() {
    const SHOWTIME_ID = @json($checkShowtimeId ?? null);
    if (!SHOWTIME_ID) return;

    const CHECK_URL = '{{ route("booking.showtime.status") }}';
    const HOME_URL = '{{ route("home") }}';
    let cancelled = false;

    function checkShowtimeStatus() {
        if (cancelled) return;

        fetch(CHECK_URL + '?showtime_id=' + SHOWTIME_ID, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'CANCELLED') {
                cancelled = true;
                showCancelledModal(data.reason || 'Không rõ lý do');
            }
        })
        .catch(err => {
            console.error('Showtime status check failed:', err);
        });
    }

    function showCancelledModal(reason) {
        const overlay = document.getElementById('showtimeCancelledOverlay');
        const reasonBox = document.getElementById('cancelledReason');
        const reasonText = document.getElementById('cancelledReasonText');
        const countdownEl = document.getElementById('cancelledRedirectCountdown');

        if (!overlay) return;

        // Hiển thị lý do huỷ
        if (reason && reasonBox && reasonText) {
            reasonText.textContent = reason;
            reasonBox.style.display = 'flex';
        }

        // Hiện modal
        overlay.classList.add('show');

        // Chặn mọi tương tác phía sau
        document.body.style.overflow = 'hidden';

        // Countdown redirect 5 giây
        let seconds = 5;
        const redirectInterval = setInterval(function() {
            seconds--;
            if (countdownEl) countdownEl.textContent = seconds;
            if (seconds <= 0) {
                clearInterval(redirectInterval);
                window.location.href = HOME_URL;
            }
        }, 1000);
    }

    // Polling mỗi 3 giây
    const statusCheckInterval = setInterval(checkShowtimeStatus, 3000);
    // Kiểm tra ngay lần đầu sau 1 giây
    setTimeout(checkShowtimeStatus, 1000);
})();
</script>
