<div id="masterCountdownContainer" class="alert alert-warning py-2 mb-4 d-flex justify-content-between align-items-center">
    <div>
        <strong><i class="bi bi-clock-history"></i> Thời gian giữ ghế còn lại:</strong>
        <span id="masterCountdownDisplay" class="fs-4 text-danger fw-bold ms-2">--:--</span>
    </div>
    <small class="text-muted" id="masterCountdownWarning" class="d-none"></small>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const expiresAtIso = @json($expiresAt ?? null);
        const serverTimeIso = @json($serverTime ?? null);
        const warningSeconds = @json($warningSeconds ?? 60);
        const seatPageUrl = @json($seatPageUrl ?? route('home'));
        
        if (!expiresAtIso || !serverTimeIso) return;

        const clientNow = Date.now();
        const serverNow = new Date(serverTimeIso).getTime();
        const serverOffset = clientNow - serverNow;
        const currentExpiresAt = new Date(expiresAtIso).getTime();
        
        const display = document.getElementById('masterCountdownDisplay');
        const warning = document.getElementById('masterCountdownWarning');
        const container = document.getElementById('masterCountdownContainer');

        const interval = setInterval(() => {
            const now = Date.now() - serverOffset;
            const remaining = Math.max(0, currentExpiresAt - now);
            const secondsLeft = Math.floor(remaining / 1000);

            if (secondsLeft <= 0) {
                clearInterval(interval);
                display.innerText = "00:00";
                
                Swal.fire({
                    icon: 'warning',
                    title: 'Hết thời gian giữ ghế!',
                    text: 'Phiên giữ vé của bạn đã hết hạn do quá thời gian.',
                    confirmButtonText: 'Quay lại chọn ghế',
                    allowOutsideClick: false
                }).then(() => {
                    window.location.replace(seatPageUrl);
                });
                return;
            }

            if (secondsLeft <= warningSeconds) {
                container.classList.remove('alert-warning');
                container.classList.add('alert-danger');
                warning.innerText = "Sắp hết thời gian!";
                warning.classList.remove('d-none');
            }

            const m = Math.floor(secondsLeft / 60).toString().padStart(2, '0');
            const s = (secondsLeft % 60).toString().padStart(2, '0');
            display.innerText = `${m}:${s}`;
        }, 1000);
    });
</script>
