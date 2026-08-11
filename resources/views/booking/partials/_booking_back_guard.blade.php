<script>
    (function() {
        // Chỉ hoạt động khi có đủ thông tin
        const holdToken = sessionStorage.getItem('hold_token');
        const showtimeId = @json($showtime_id ?? $bookingTam['showtime_id'] ?? null);
        const tabToken = new URLSearchParams(window.location.search).get('tab_token') || sessionStorage.getItem('tab_token');
        const expiresAt = @json($expiresAt ?? null);
        const seatPageUrl = @json($seatPageUrl ?? null);
        const quickCancelUrl = @json(route('booking.quickCancel'));
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // Push state để bắt sự kiện popstate (back)
        history.pushState({ bookingGuard: true }, '', window.location.href);

        window.addEventListener('popstate', function(e) {
            // Khi user nhấn browser back, state sẽ thay đổi
            if (holdToken && showtimeId && tabToken && expiresAt && seatPageUrl && csrfToken) {
                // Sử dụng sendBeacon để request không bị hủy khi chuyển trang
                const fd = new FormData();
                fd.append('_token', csrfToken);
                fd.append('hold_token', holdToken);
                fd.append('showtime_id', showtimeId);
                fd.append('tab_token', tabToken);
                fd.append('expires_at', expiresAt);
                
                navigator.sendBeacon(quickCancelUrl, fd);
                
                // Ép quay về trang chọn ghế
                window.location.replace(seatPageUrl);
            } else {
                // Fallback nếu thiếu data (có thể đã hết session)
                window.location.replace('/');
            }
        });
    })();
</script>
