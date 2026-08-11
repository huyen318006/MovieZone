<div id="inlineCountdownContainer" class="d-none">
    <div class="alert alert-warning py-2 mb-3 text-center">
        <strong><i class="bi bi-clock-history"></i> Thời gian giữ ghế:</strong>
        <span id="inlineCountdownDisplay" class="fs-5 text-danger fw-bold ms-2">--:--</span>
    </div>
</div>

<script>
    let inlineCountdownInterval = null;
    let currentExpiresAt = null;

    function startInlineCountdown(expiresAtIso, serverTimeIso, totalSeconds) {
        const clientNow = Date.now();
        const serverNow = new Date(serverTimeIso).getTime();
        const serverOffset = clientNow - serverNow;
        
        currentExpiresAt = new Date(expiresAtIso).getTime();
        
        const container = document.getElementById('inlineCountdownContainer');
        const display = document.getElementById('inlineCountdownDisplay');
        container.classList.remove('d-none');

        if (inlineCountdownInterval) {
            clearInterval(inlineCountdownInterval);
        }

        inlineCountdownInterval = setInterval(() => {
            const now = Date.now() - serverOffset;
            const remaining = Math.max(0, currentExpiresAt - now);
            const secondsLeft = Math.floor(remaining / 1000);

            if (secondsLeft <= 0) {
                clearInterval(inlineCountdownInterval);
                display.innerText = "00:00";
                
                // UX: Hết hạn giữ ghế
                Swal.fire({
                    icon: 'warning',
                    title: 'Hết thời gian giữ ghế!',
                    text: 'Phiên giữ ghế của bạn đã hết hạn. Vui lòng chọn lại.',
                    confirmButtonText: 'Đã hiểu'
                }).then(() => {
                    window.location.reload();
                });
                return;
            }

            const m = Math.floor(secondsLeft / 60).toString().padStart(2, '0');
            const s = (secondsLeft % 60).toString().padStart(2, '0');
            display.innerText = `${m}:${s}`;
        }, 1000);
    }

    function stopInlineCountdown() {
        if (inlineCountdownInterval) {
            clearInterval(inlineCountdownInterval);
        }
        document.getElementById('inlineCountdownContainer').classList.add('d-none');
        document.getElementById('inlineCountdownDisplay').innerText = "--:--";
        currentExpiresAt = null;
    }
</script>
