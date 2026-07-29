<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MovieZone</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])
</head>
<body>

    @include('include.header')

    @if(session('success'))
        <div class="flash-message-container">
            <div id="success-alert" class="alert alert-success">
                <span>{{ session('success') }}</span>
                <button type="button" class="btn-close" aria-label="Close">&times;</button>
            </div>
        </div>
    @endif

    @yield('content')

    @include('include.footer')

    @include('include.chatbot')

    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            easing: 'ease-out-cubic',
            once: true,
            offset: 100
        });
    </script>
    @stack('scripts')
</body>
<script>

document.addEventListener('DOMContentLoaded', () => {
    // 1. Phải import hoặc định nghĩa các module trước (nếu bạn dùng CDN hoặc ES Modules)
    // Nếu dùng bản Bundle đầy đủ, bạn cần khai báo nó trong mảng modules:

    new Swiper('.movieSwiper', {
        // Thêm dòng này để kích hoạt tính năng Autoplay
        modules: [Autoplay],

        loop: true,
        centeredSlides: true,
        slidesPerView: 2,
        spaceBetween: 20,
        grabCursor: true,

        autoplay: {
            delay: 1000,
            disableOnInteraction: false,
            pauseOnMouseEnter: true,
        },
        speed: 800,
    });

    const alertBox = document.getElementById('success-alert');
    if (alertBox) {
        const closeButton = alertBox.querySelector('.btn-close');
        const hideAlert = () => {
            alertBox.style.opacity = '0';
            setTimeout(() => {
                alertBox.remove();
            }, 300);
        };
        if (closeButton) {
            closeButton.addEventListener('click', hideAlert);
        }
        setTimeout(hideAlert, 3000);
    }
});

</script>

{{-- ============================================================
     MULTI-TAB AUTH DETECTION
     Phát hiện khi session bị thay đổi ở tab khác (ví dụ: admin
     đăng nhập ở tab khác ghi đè session customer)
     ============================================================ --}}
@auth
<div id="sessionSwitchOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); backdrop-filter:blur(8px); z-index:99999; align-items:center; justify-content:center;">
    <div style="background:linear-gradient(145deg,#1e293b,#111827); border:1px solid #374151; border-radius:20px; padding:40px; max-width:480px; width:90%; text-align:center; box-shadow:0 25px 60px rgba(0,0,0,0.6);">
        <div style="width:80px; height:80px; border-radius:50%; background:rgba(245,158,11,0.1); border:2px solid rgba(245,158,11,0.3); display:flex; align-items:center; justify-content:center; margin:0 auto 20px; font-size:36px; color:#f59e0b;">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h3 style="color:#f8fafc; font-size:20px; margin:0 0 10px; font-weight:700;">Phiên đăng nhập đã thay đổi</h3>
        <p id="sessionSwitchMsg" style="color:#9ca3af; font-size:15px; line-height:1.6; margin:0 0 24px;">
            Tài khoản đăng nhập đã được thay đổi ở tab khác.
        </p>
        <div style="display:flex; flex-direction:column; gap:12px;">
            <a id="sessionSwitchRedirect" href="/" style="display:inline-flex; align-items:center; justify-content:center; gap:8px; background:#f59e0b; color:#111; padding:12px 28px; border-radius:10px; font-weight:600; font-size:15px; text-decoration:none; transition:all 0.3s;">
                <i class="fa-solid fa-arrow-right"></i> Chuyển trang phù hợp
            </a>
            <button onclick="document.getElementById('sessionSwitchOverlay').style.display='none'" style="background:transparent; border:1px solid #374151; color:#9ca3af; padding:10px; border-radius:10px; cursor:pointer; font-size:14px;">
                Đóng và tiếp tục
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    // Lưu user_id ban đầu khi trang load
    const initialUserId = {{ Auth::id() }};
    const checkUrl = '{{ route("api.check-auth-role") }}';
    let hasShownWarning = false;

    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible' && !hasShownWarning) {
            // Tab vừa được focus lại → kiểm tra session
            fetch(checkUrl, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                if (!data.authenticated) {
                    // User đã bị đăng xuất ở tab khác
                    showSessionWarning('Bạn đã bị đăng xuất ở tab khác. Vui lòng đăng nhập lại.', '{{ route("login") }}', 'Đăng nhập lại');
                } else if (data.user_id !== initialUserId) {
                    // Session đã chuyển sang user khác
                    let redirectUrl = '/';
                    let btnText = 'Về trang chủ';
                    let msg = `Phiên đăng nhập đã chuyển sang tài khoản "${data.name}".`;

                    if (data.role === 'admin') {
                        redirectUrl = '{{ route("admin.dashboard") }}';
                        btnText = 'Đến trang Quản trị';
                        msg = `Tài khoản Admin "${data.name}" đã đăng nhập ở tab khác. Trang customer không còn hiển thị đúng thông tin.`;
                    } else if (data.role === 'staff') {
                        redirectUrl = '{{ route("staff.dashboard") }}';
                        btnText = 'Đến trang Nhân viên';
                        msg = `Tài khoản Staff "${data.name}" đã đăng nhập ở tab khác.`;
                    }

                    showSessionWarning(msg, redirectUrl, btnText);
                }
            })
            .catch(() => {}); // Bỏ qua lỗi mạng
        }
    });

    function showSessionWarning(msg, redirectUrl, btnText) {
        hasShownWarning = true;
        const overlay = document.getElementById('sessionSwitchOverlay');
        const msgEl = document.getElementById('sessionSwitchMsg');
        const redirectBtn = document.getElementById('sessionSwitchRedirect');

        if (overlay && msgEl && redirectBtn) {
            msgEl.textContent = msg;
            redirectBtn.href = redirectUrl;
            redirectBtn.innerHTML = '<i class="fa-solid fa-arrow-right"></i> ' + btnText;
            overlay.style.display = 'flex';
        }
    }
})();
</script>
@endauth

</html>
