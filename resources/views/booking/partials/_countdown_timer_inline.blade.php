<div class="booking-countdown-bar" id="inlineCountdownBar" style="display: none;">
    <div class="countdown-inner">
        <div class="countdown-left">
            <div class="countdown-icon-pulse">
                <i class="fa-solid fa-stopwatch"></i>
            </div>
            <div class="countdown-label">
                <span class="countdown-label-text">Thời gian giữ ghế</span>
                <span class="countdown-time" id="inlineCountdownDisplay">05:00</span>
            </div>
        </div>
        <div class="countdown-right">
            <div class="countdown-progress-track">
                <div class="countdown-progress-fill" id="inlineCountdownFill"></div>
            </div>
        </div>
    </div>
</div>

<style>
/* ==========================================
   COUNTDOWN BAR - INLINE SIDEBAR
   ========================================== */
.booking-countdown-bar {
    position: relative;
    background: #111827;
    border: 1px solid #1f2937;
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    border-bottom: 2px solid #f59e0b;
}

.countdown-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.countdown-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.countdown-icon-pulse {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(245, 158, 11, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    color: #f59e0b;
    animation: iconPulse 2s infinite;
    flex-shrink: 0;
}

@keyframes iconPulse {
    0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.3); }
    50% { transform: scale(1.05); box-shadow: 0 0 0 6px rgba(245, 158, 11, 0); }
}

.countdown-label {
    display: flex;
    flex-direction: column;
    gap: 0px;
}

.countdown-label-text {
    font-size: 10px;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.countdown-time {
    font-size: 20px;
    font-weight: 800;
    color: #f59e0b;
    font-variant-numeric: tabular-nums;
    letter-spacing: 1px;
    line-height: 1.1;
}

.countdown-right {
    flex: 1;
    display: flex;
    align-items: center;
}

.countdown-progress-track {
    width: 100%;
    height: 4px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    overflow: hidden;
}

.countdown-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #f59e0b, #eab308);
    border-radius: 10px;
    transition: width 1s linear;
    width: 100%;
}

/* === DANGER MODE (còn < 60 giây) === */
.booking-countdown-bar.danger {
    border-bottom-color: #ef4444;
}

.booking-countdown-bar.danger .countdown-icon-pulse {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}

.booking-countdown-bar.danger .countdown-time {
    color: #ef4444;
    animation: timeBlink 1s infinite;
}

.booking-countdown-bar.danger .countdown-progress-fill {
    background: linear-gradient(90deg, #ef4444, #dc2626);
}

@keyframes timeBlink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}
</style>

<script>
    let inlineCountdownInterval = null;
    let currentExpiresAt = null;

    function startInlineCountdown(expiresAtIso, serverTimeIso, totalSeconds) {
        const clientNow = Date.now();
        const serverNow = new Date(serverTimeIso).getTime();
        const serverOffset = clientNow - serverNow;
        
        currentExpiresAt = new Date(expiresAtIso).getTime();
        
        const bar = document.getElementById('inlineCountdownBar');
        const display = document.getElementById('inlineCountdownDisplay');
        const fill = document.getElementById('inlineCountdownFill');
        
        bar.style.display = 'block';
        bar.classList.remove('danger');

        if (inlineCountdownInterval) {
            clearInterval(inlineCountdownInterval);
        }

        inlineCountdownInterval = setInterval(() => {
            const now = Date.now() - serverOffset;
            const remaining = Math.max(0, currentExpiresAt - now);
            const secondsLeft = Math.floor(remaining / 1000);

            if (secondsLeft <= 0) {
                stopInlineCountdown();
                
                // UX: Hết hạn giữ ghế (non-blocking banner/toast)
                if (typeof toastr !== 'undefined') {
                    toastr.warning('Phiên giữ ghế của bạn đã hết hạn. Vui lòng chọn lại ghế.', 'Hết thời gian!');
                } else {
                    alert('Phiên giữ ghế của bạn đã hết hạn. Vui lòng chọn lại.');
                }
                
                // Xóa token
                sessionStorage.removeItem('hold_token');
                
                // Refresh UI
                if (typeof selectedSeats !== 'undefined') {
                    selectedSeats.clear();
                    if (typeof updateUI === 'function') {
                        updateUI();
                    }
                }
                
                if (typeof refreshSeatStates === 'function') {
                    refreshSeatStates();
                }

                return;
            }

            const m = Math.floor(secondsLeft / 60).toString().padStart(2, '0');
            const s = (secondsLeft % 60).toString().padStart(2, '0');
            display.innerText = `${m}:${s}`;
            
            // Progress bar
            const pct = (secondsLeft / totalSeconds) * 100;
            fill.style.width = pct + '%';
            
            // Danger mode khi còn <= 60 giây
            if (secondsLeft <= 60 && secondsLeft > 0) {
                bar.classList.add('danger');
            }
        }, 1000);
    }

    function stopInlineCountdown() {
        if (inlineCountdownInterval) {
            clearInterval(inlineCountdownInterval);
        }
        document.getElementById('inlineCountdownBar').style.display = 'none';
        document.getElementById('inlineCountdownDisplay').innerText = "--:--";
        currentExpiresAt = null;
    }
</script>
