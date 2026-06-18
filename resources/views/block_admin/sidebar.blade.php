<aside class="admin-sidebar" id="adminSidebar">

    <div class="sidebar-header">

        <a class="brand-mark" href="">

            <span class="brand-icon">
                <i class="bi bi-film"></i>
            </span>

            <span class="brand-copy">
                <span class="brand-title">MovieZone</span>
                <span class="brand-subtitle">
                    Cinema Management
                </span>
            </span>

        </a>

    </div>

    <nav class="sidebar-nav">

        <a class="nav-link" href="{{ route('admin.dashboard') }}">
            <i class="bi bi-speedometer2"></i>
            Dashboard
        </a>

        <a class="nav-link" href="{{ route('admin.film') }}">
            <i class="bi bi-film"></i>
            Quản lý phim
        </a>

        <a class="nav-link" href="{{ route('admin.cinemas.index') }}">
            <i class="bi bi-building"></i>
            Quản lý rạp
        </a>

        <a class="nav-link {{ Request::routeIs('admin.seats.*') ? 'active' : '' }}" href="{{ route('admin.seats.index') }}">
            <i class="bi bi-grid-3x3-gap"></i>
             Ghế ngồi
        </a>

        <a class="nav-link" href="">
            <i class="bi bi-calendar-event"></i>
            Suất chiếu
        </a>

        <a class="nav-link" href="">
            <i class="bi bi-ticket-perforated"></i>
            Đặt vé
        </a>

        <a class="nav-link" href="">
            <i class="bi bi-cup-straw"></i>
            Combo
        </a>

        <a class="nav-link" href="">
            <i class="bi bi-percent"></i>
            Voucher
        </a>

        <a class="nav-link" href="">
            <i class="bi bi-people"></i>
            Người dùng
        </a>

        <a class="nav-link" href="">
            <i class="bi bi-newspaper"></i>
            Bài viết
        </a>

        <a class="nav-link" href="">
            <i class="bi bi-image"></i>
            Banner
        </a>

        <a class="nav-link" href="">
            <i class="bi bi-bar-chart"></i>
            Báo cáo
        </a>
        {{-- <button id="theme-toggle" class="btn btn-outline-secondary">

            <i class="bi bi-moon-stars"></i>
                Giao diện

        </button> --}}

    </nav>

</aside>
