<!DOCTYPE html>
<html lang="vi" data-theme="dark" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Admin')</title>

    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
<!-- hoặc CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
</head>

<body>

<div class="admin-shell">

    @include('block_admin.sidebar')

    <div class="admin-main">

        @include('block_admin.navbar')

        <main class="dashboard-content">

            <div class="container-fluid px-3 px-lg-4 py-4">

                @yield('content')

            </div>

        </main>

        @include('block_admin.footer')

    </div>

</div>

<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/main.js') }}"></script>

{{-- <script>
(() => {
  const html = document.documentElement;
  const toggleBtn = document.getElementById('theme-toggle');
  // debug: ensure toggle is clickable
  // console.log('theme-toggle found:', !!toggleBtn);


  function applyTheme(theme) {
    html.setAttribute('data-theme', theme);
    html.setAttribute('data-bs-theme', theme);

    localStorage.setItem('theme', theme);
  }

  const savedTheme = localStorage.getItem('theme');
  if (savedTheme) {
    applyTheme(savedTheme);
  } else {
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    applyTheme(prefersDark ? 'dark' : 'light');
  }

  if (toggleBtn) {

    toggleBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();

      const current = (html.getAttribute('data-bs-theme') || 'light').toLowerCase();
      applyTheme(current === 'dark' ? 'light' : 'dark');

    });
  }
})();
</script> --}}


@stack('scripts')

{{-- MULTI-TAB AUTH DETECTION cho Admin --}}
@auth
<div id="adminSessionSwitchOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); backdrop-filter:blur(8px); z-index:99999; align-items:center; justify-content:center;">
    <div style="background:linear-gradient(145deg,#1e293b,#111827); border:1px solid #374151; border-radius:20px; padding:40px; max-width:480px; width:90%; text-align:center; box-shadow:0 25px 60px rgba(0,0,0,0.6);">
        <div style="width:80px; height:80px; border-radius:50%; background:rgba(239,68,68,0.1); border:2px solid rgba(239,68,68,0.3); display:flex; align-items:center; justify-content:center; margin:0 auto 20px; font-size:36px; color:#ef4444;">
            <i class="bi bi-shield-exclamation"></i>
        </div>
        <h3 style="color:#f8fafc; font-size:20px; margin:0 0 10px; font-weight:700;">Phiên đăng nhập đã thay đổi</h3>
        <p id="adminSessionSwitchMsg" style="color:#9ca3af; font-size:15px; line-height:1.6; margin:0 0 24px;">
            Tài khoản đăng nhập đã được thay đổi ở tab khác.
        </p>
        <div style="display:flex; flex-direction:column; gap:12px;">
            <a id="adminSessionSwitchRedirect" href="{{ route('login') }}" style="display:inline-flex; align-items:center; justify-content:center; gap:8px; background:#ef4444; color:#fff; padding:12px 28px; border-radius:10px; font-weight:600; font-size:15px; text-decoration:none;">
                <i class="bi bi-box-arrow-in-right"></i> Đăng nhập lại
            </a>
            <button onclick="document.getElementById('adminSessionSwitchOverlay').style.display='none'" style="background:transparent; border:1px solid #374151; color:#9ca3af; padding:10px; border-radius:10px; cursor:pointer; font-size:14px;">
                Đóng và tiếp tục
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    const initialUserId = {{ Auth::id() }};
    const checkUrl = '{{ route("api.check-auth-role") }}';
    let hasShown = false;

    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible' && !hasShown) {
            fetch(checkUrl, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                if (!data.authenticated) {
                    showAdminWarning('Bạn đã bị đăng xuất ở tab khác.', '{{ route("login") }}', 'Đăng nhập lại');
                } else if (data.user_id !== initialUserId) {
                    let msg = `Phiên đã chuyển sang tài khoản "${data.name}" (${data.role}).`;
                    let url = data.role === 'admin' ? '{{ route("admin.dashboard") }}' : '/';
                    let btn = data.role === 'admin' ? 'Tải lại trang' : 'Về trang chủ';
                    showAdminWarning(msg, url, btn);
                } else if (data.role !== 'admin') {
                    showAdminWarning('Tài khoản hiện tại không còn quyền admin.', '/', 'Về trang chủ');
                }
            })
            .catch(() => {});
        }
    });

    function showAdminWarning(msg, url, btnText) {
        hasShown = true;
        const o = document.getElementById('adminSessionSwitchOverlay');
        const m = document.getElementById('adminSessionSwitchMsg');
        const r = document.getElementById('adminSessionSwitchRedirect');
        if (o && m && r) {
            m.textContent = msg;
            r.href = url;
            r.innerHTML = '<i class="bi bi-box-arrow-in-right"></i> ' + btnText;
            o.style.display = 'flex';
        }
    }
})();
</script>
@endauth

</body>

</html>
