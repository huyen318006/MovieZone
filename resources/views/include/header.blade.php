<header class="header">

    <div class="logo">

        <a href="{{ route('home') }}">

            MOVIE<span>ZONE</span>

        </a>

    </div>

    <nav class="main-nav">

        <a href="#">Trang Chủ</a>

        <a href="#">Phim</a>

        <a href="#">Lịch Chiếu</a>

        <a href="#">Rạp Chiếu</a>

        <a href="#">Khuyến Mãi</a>

        <a href="#">Tin Tức</a>

    </nav>

    <div class="header-right">

    <div class="search-wrapper">

        <button class="icon-btn">
            <i class="bi bi-search"></i>
        </button>

        <input
            type="text"
            placeholder="Tìm phim..."
            class="search-input">

    </div>

 @auth
    <div class="user-dropdown">
        <div class="user-btn">
            <i class="bi bi-person-circle"></i>
            <span>{{ Auth::user()->name }}</span>
        </div>
        <div class="dropdown-content">
            <a href="#" class="dropdown-item">
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
@else
    <a href="{{ route('login') }}" class="user-btn">
        <i class="bi bi-person-circle"></i>
        <span>Đăng Nhập</span>
    </a>
@endauth

</div>

</header>
