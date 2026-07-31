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

        <a class="nav-link" href="{{ \App\Helpers\TabAuthHelper::route('admin.dashboard') }}">
            <i class="bi bi-speedometer2"></i>
            Dashboard
        </a>

        <a class="nav-link" href="{{ \App\Helpers\TabAuthHelper::route('admin.film') }}">
            <i class="bi bi-film"></i>
            Quản lý phim
        </a>

        <a class="nav-link {{ Request::routeIs('admin.rooms.*') ? 'active' : '' }}" href="{{ \App\Helpers\TabAuthHelper::route('admin.rooms.index') }}">
            <i class="bi bi-door-open"></i>
            Quản lý phòng chiếu
        </a>

        {{-- <a class="nav-link {{ Request::routeIs('admin.seats.*') ? 'active' : '' }}" href="{{ \App\Helpers\TabAuthHelper::route('admin.seats.index') }}">
            <i class="bi bi-grid-3x3-gap"></i>
             Ghế ngồi
        </a> --}}

        <a class="nav-link" href="{{ \App\Helpers\TabAuthHelper::route('admin.showtime') }}">
            <i class="bi bi-calendar-event"></i>
            Suất chiếu
        </a>

        <a class="nav-link" href="{{ \App\Helpers\TabAuthHelper::route('admin.bookings.index') }}">
            <i class="bi bi-ticket-detailed"></i>
            Quản lý đơn đặt vé
        </a>

        <a class="nav-link {{ Request::routeIs('admin.products.*') ? 'active' : '' }}" href="{{ \App\Helpers\TabAuthHelper::route('admin.products.index') }}">
            <i class="bi bi-box"></i>
            Sản phẩm lẻ
        </a>

        <a class="nav-link {{ Request::routeIs('admin.combos.*') ? 'active' : '' }}" href="{{ \App\Helpers\TabAuthHelper::route('admin.combos.index') }}">
            <i class="bi bi-cup-straw"></i>
            Combo bắp nước
        </a>

        <a class="nav-link {{ Request::routeIs('admin.vouchers.*') ? 'active' : '' }}" href="{{ \App\Helpers\TabAuthHelper::route('admin.vouchers.index') }}">
            <i class="bi bi-percent"></i>
            Mã giảm giá (Voucher)
        </a>

        <a class="nav-link {{ Request::routeIs('admin.promotions.*') ? 'active' : '' }}" href="{{ \App\Helpers\TabAuthHelper::route('admin.promotions.index') }}">
            <i class="bi bi-megaphone"></i>
            Khuyến mãi
        </a>

        <a class="nav-link" href="{{ \App\Helpers\TabAuthHelper::route('admin.list_account') }}">
            <i class="bi bi-people"></i>
            Người dùng
        </a>

        <a class="nav-link {{ Request::routeIs('admin.memberships.*') ? 'active' : '' }}" href="{{ \App\Helpers\TabAuthHelper::route('admin.memberships.index') }}">
            <i class="bi bi-shield-check"></i>
            Quản lý Membership
        </a>

<a class="nav-link {{ Request::routeIs('admin.articles.*') ? 'active' : '' }}" href="{{ \App\Helpers\TabAuthHelper::route('admin.articles.index') }}">
            <i class="bi bi-newspaper"></i>
            Bài viết
        </a>

        <a class="nav-link {{ Request::routeIs('admin.banners.*') ? 'active' : '' }}" href="{{ \App\Helpers\TabAuthHelper::route('admin.banners.index') }}">
            <i class="bi bi-image"></i>
            Banner
        </a>

{{-- <a class="nav-link" href="">
            <i class="bi bi-bar-chart"></i>
            Báo cáo
        </a> --}}
        {{-- <button id="theme-toggle" class="btn btn-outline-secondary">

            <i class="bi bi-moon-stars"></i>
                Giao diện

        </button> --}}

    </nav>

</aside>
