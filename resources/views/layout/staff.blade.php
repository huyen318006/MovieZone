<!DOCTYPE html>
<html lang="vi" data-theme="dark" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Staff Portal') — MovieZone</title>

    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

    <style>
        /* ══════ STAFF PORTAL LAYOUT ══════ */
        :root {
            --staff-sidebar-w: 260px;
            --staff-primary: #8b5cf6;
            --staff-primary-hover: #7c3aed;
            --staff-bg: #0f172a;
            --staff-surface: #1e293b;
            --staff-surface-hover: #334155;
            --staff-border: #334155;
            --staff-text: #e2e8f0;
            --staff-text-muted: #94a3b8;
            --staff-success: #10b981;
            --staff-warning: #f59e0b;
            --staff-danger: #ef4444;
            --staff-info: #3b82f6;
        }

        body { background: var(--staff-bg); color: var(--staff-text); font-family: 'Segoe UI', system-ui, sans-serif; }

        .staff-shell { display: flex; min-height: 100vh; }

        /* ── SIDEBAR ── */
        .staff-sidebar {
            width: var(--staff-sidebar-w); background: var(--staff-surface); border-right: 1px solid var(--staff-border);
            display: flex; flex-direction: column; position: fixed; top: 0; left: 0; bottom: 0; z-index: 100;
            transition: transform 0.3s;
        }
        .staff-sidebar .sidebar-brand {
            padding: 20px; border-bottom: 1px solid var(--staff-border);
            display: flex; align-items: center; gap: 12px; text-decoration: none;
        }
        .staff-sidebar .sidebar-brand .brand-icon {
            width: 40px; height: 40px; border-radius: 10px;
            background: linear-gradient(135deg, var(--staff-primary), #6d28d9);
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; color: #fff;
        }
        .staff-sidebar .sidebar-brand .brand-title { font-size: 18px; font-weight: 700; color: var(--staff-text); }
        .staff-sidebar .sidebar-brand .brand-sub { font-size: 11px; color: var(--staff-text-muted); text-transform: uppercase; letter-spacing: 1px; }

        .staff-sidebar .sidebar-nav { padding: 12px; flex: 1; overflow-y: auto; }
        .staff-sidebar .sidebar-nav .nav-label {
            font-size: 10px; text-transform: uppercase; letter-spacing: 1.5px;
            color: var(--staff-text-muted); padding: 12px 12px 6px; font-weight: 600;
        }
        .staff-sidebar .sidebar-nav .nav-link {
            display: flex; align-items: center; gap: 10px; padding: 10px 12px;
            border-radius: 8px; color: var(--staff-text-muted); text-decoration: none;
            font-size: 14px; transition: all 0.2s; margin-bottom: 2px;
        }
        .staff-sidebar .sidebar-nav .nav-link:hover { background: var(--staff-surface-hover); color: var(--staff-text); }
        .staff-sidebar .sidebar-nav .nav-link.active {
            background: rgba(139, 92, 246, 0.15); color: var(--staff-primary); font-weight: 600;
        }
        .staff-sidebar .sidebar-nav .nav-link i { font-size: 18px; width: 22px; text-align: center; }

        .staff-sidebar .sidebar-footer {
            padding: 16px; border-top: 1px solid var(--staff-border);
            display: flex; align-items: center; gap: 10px;
        }
        .staff-sidebar .sidebar-footer .avatar {
            width: 36px; height: 36px; border-radius: 50%; background: var(--staff-primary);
            display: flex; align-items: center; justify-content: center; font-weight: 700; color: #fff; font-size: 14px;
        }
        .staff-sidebar .sidebar-footer .user-info { flex: 1; }
        .staff-sidebar .sidebar-footer .user-name { font-size: 13px; font-weight: 600; color: var(--staff-text); }
        .staff-sidebar .sidebar-footer .user-role { font-size: 11px; color: var(--staff-text-muted); }

        /* ── MAIN ── */
        .staff-main { margin-left: var(--staff-sidebar-w); flex: 1; display: flex; flex-direction: column; }

        .staff-topbar {
            background: var(--staff-surface); border-bottom: 1px solid var(--staff-border);
            padding: 12px 24px; display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 50;
        }
        .staff-topbar .page-title { font-size: 16px; font-weight: 600; }
        .staff-topbar .topbar-actions { display: flex; align-items: center; gap: 12px; }

        .staff-content { padding: 24px; flex: 1; }

        /* ── RESPONSIVE ── */
        @media (max-width: 1024px) {
            .staff-sidebar { transform: translateX(-100%); }
            .staff-sidebar.open { transform: translateX(0); }
            .staff-main { margin-left: 0; }
        }

        @stack('styles')
    </style>
</head>

<body>

<div class="staff-shell">
    <!-- SIDEBAR -->
    <aside class="staff-sidebar" id="staffSidebar">
        <a class="sidebar-brand" href="{{ \App\Helpers\TabAuthHelper::route('staff.dashboard') }}">
            <div class="brand-icon"><i class="bi bi-film"></i></div>
            <div>
                <div class="brand-title">MovieZone</div>
                <div class="brand-sub">Staff Portal</div>
            </div>
        </a>

        <nav class="sidebar-nav">
            <div class="nav-label">Tổng quan</div>

            <a class="nav-link {{ Request::routeIs('staff.dashboard') ? 'active' : '' }}"
               href="{{ \App\Helpers\TabAuthHelper::route('staff.dashboard') }}">
                <i class="bi bi-speedometer2"></i> Staff Dashboard
            </a>

            <div class="nav-label">Chức năng</div>

            <a class="nav-link {{ Request::routeIs('staff.booking-lookup') ? 'active' : '' }}"
               href="{{ \App\Helpers\TabAuthHelper::route('staff.booking-lookup') }}">
                <i class="bi bi-search"></i> Tra cứu Booking/Vé
            </a>

            <a class="nav-link {{ Request::routeIs('staff.check-in') ? 'active' : '' }}"
               href="{{ \App\Helpers\TabAuthHelper::route('staff.check-in') }}">
                <i class="bi bi-qr-code-scan"></i> Check-in Vé
            </a>

            <a class="nav-link {{ Request::routeIs('staff.sell-tickets') ? 'active' : '' }}"
               href="{{ \App\Helpers\TabAuthHelper::route('staff.sell-tickets') }}">
                <i class="bi bi-ticket-perforated"></i> Bán vé
            </a>

            <a class="nav-link {{ Request::routeIs('staff.issue-support') ? 'active' : '' }}"
               href="{{ \App\Helpers\TabAuthHelper::route('staff.issue-support') }}">
                <i class="bi bi-life-preserver"></i> Hỗ trợ sự cố đặt vé
            </a>

            <div class="nav-label">Tiện ích</div>

            <a class="nav-link {{ Request::routeIs('staff.articles.*') ? 'active' : '' }}"
               href="{{ \App\Helpers\TabAuthHelper::route('staff.articles.index') }}">
                <i class="bi bi-newspaper"></i> Bài viết
            </a>

        </nav>

        <div class="sidebar-footer">
            <div class="avatar">{{ mb_substr(auth()->user()->name ?? 'S', 0, 1) }}</div>
            <div class="user-info">
                <div class="user-name">{{ auth()->user()->name ?? 'Staff' }}</div>
                <div class="user-role">Staff</div>
            </div>
            <form method="GET" action="{{ \App\Helpers\TabAuthHelper::route('logout') }}" style="margin:0;">
                <button type="submit" class="btn btn-sm" style="color: var(--staff-text-muted); padding: 4px 8px;" title="Đăng xuất">
                    <i class="bi bi-box-arrow-right"></i>
                </button>
            </form>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="staff-main">
        <header class="staff-topbar">
            <div style="display:flex; align-items:center; gap:12px;">
                <button class="btn btn-sm d-lg-none" onclick="document.getElementById('staffSidebar').classList.toggle('open')" style="color:var(--staff-text);">
                    <i class="bi bi-list" style="font-size:20px;"></i>
                </button>
                <span class="page-title">@yield('page-title', 'Staff Portal')</span>
            </div>
            <div class="topbar-actions">
                @yield('topbar-actions')
            </div>
        </header>

        <main class="staff-content">
            @yield('content')
        </main>
    </div>
</div>

<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
@stack('scripts')

</body>
</html>
