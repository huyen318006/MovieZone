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
    <div class="user-btn">
        <i class="bi bi-person-circle"></i>
        <span>{{ Auth::user()->name }}</span>
    </div>

    <form action="{{ route('logout') }}" method="POST" style="display:inline;">
        @csrf
        @method('POST')
        <button type="submit" class="user-btn border-0 bg-transparent">
            <i class="bi bi-box-arrow-right"></i>
            <span>Đăng xuất</span>
        </button>
    </form>
@else
    <a href="{{ route('login') }}" class="user-btn">
        <i class="bi bi-person-circle"></i>
        <span>Đăng Nhập</span>
    </a>
@endauth

</div>

</header>
