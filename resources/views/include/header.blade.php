<header class="header">

    <div class="logo">

        <a href="{{ route('home') }}">

            MOVIE<span>ZONE</span>

        </a>

    </div>

    <nav class="main-nav">

        <a href="{{ route('home') }}">Trang Chủ</a>

        <a href="{{ route('movies') }}">Phim</a>

        <a href="{{ route('showtimes') }}">Lịch Chiếu</a>

        <a href="{{ route('promotions') }}">Khuyến Mãi</a>

        <a href="{{ route('news') }}">Tin Tức</a>

    </nav>

    <div class="header-right">

        <div class="search-wrapper">

            <button class="icon-btn">
                <i class="bi bi-search"></i>
            </button>

            <input type="text" placeholder="Tìm phim..." class="search-input">

        </div>


        @auth
            @php
                $currentUserRoles = Auth::user()->roles()->pluck('name')->toArray();
                $isAdminUser = in_array('admin', $currentUserRoles);
                $isStaffUser = in_array('staff', $currentUserRoles);
            @endphp

            @if($isAdminUser)
                {{-- Người dùng admin đang truy cập trang customer --}}
                <div class="admin-warning-badge">
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-warning rounded-pill d-flex align-items-center gap-2 fw-bold px-3" style="font-size: 13px;">
                        <i class="bi bi-shield-exclamation"></i>
                        <span>Bạn đang đăng nhập Admin → Quay về Quản trị</span>
                    </a>
                </div>
            @elseif($isStaffUser)
                {{-- Nhân viên đang truy cập trang customer --}}
                <div class="admin-warning-badge">
                    <a href="{{ route('staff.dashboard') }}" class="btn btn-info rounded-pill d-flex align-items-center gap-2 fw-bold px-3" style="font-size: 13px;">
                        <i class="bi bi-person-badge"></i>
                        <span>Bạn đang đăng nhập Staff → Quay về Trang nhân viên</span>
                    </a>
                </div>
            @else
                <a href="{{ route('coin.index', Auth::user()->id) }}" class="btn btn-warning rounded-pill d-flex align-items-center gap-2 fw-bold px-3">
                    <i class="bi bi-coin"></i>
                    <span>{{ number_format(Auth::user()->coin?->balance ?? 0) }}</span>
                </a>
                <div class="user-dropdown">
                    <div class="user-btn">
                        <i class="bi bi-person-circle"></i>
                        <span>{{ Auth::user()->name }}</span>
                    </div>
                    <div class="dropdown-content">
                        <a href="{{ route('profile') }}" class="dropdown-item">
                            <i class="bi bi-person"></i>
                            <span>Xem hồ sơ</span>
                        </a>
                        <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                            @csrf
                            @method('POST')
                            <button type="submit" class="logout-btn">
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Đăng xuất</span>
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        @else
            <a href="{{ route('login') }}" class="user-btn">
                <i class="bi bi-person-circle"></i>
                <span>Đăng Nhập</span>
            </a>
        @endauth

    </div>

</header>
