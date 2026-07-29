<header class="header">

    <div class="logo">

        <a href="{{ \App\Helpers\TabAuthHelper::route('home') }}">

            MOVIE<span>ZONE</span>

        </a>

    </div>

    <nav class="main-nav">

        <a href="{{ \App\Helpers\TabAuthHelper::route('home') }}">Trang Chủ</a>

        <a href="{{ \App\Helpers\TabAuthHelper::route('movies') }}">Phim</a>

        <a href="{{ \App\Helpers\TabAuthHelper::route('showtimes') }}">Lịch Chiếu</a>

        <a href="{{ \App\Helpers\TabAuthHelper::route('promotions') }}">Khuyến Mãi</a>

        <a href="{{ \App\Helpers\TabAuthHelper::route('news') }}">Tin Tức</a>

    </nav>

    <div class="header-right">

        <div class="search-wrapper">

            <button class="icon-btn">
                <i class="bi bi-search"></i>
            </button>

            <input type="text" placeholder="Tìm phim..." class="search-input">

        </div>

@php
    $currentUser = \App\Helpers\TabAuthHelper::currentUser();
@endphp

@if($currentUser)
    <a href="{{ \App\Helpers\TabAuthHelper::route('coin.index', $currentUser->id) }}"
       class="btn btn-warning rounded-pill d-flex align-items-center gap-2 fw-bold px-3">
        <i class="bi bi-coin"></i>
        <span>{{ number_format($currentUser->coin?->balance ?? 0) }}</span>
    </a>

    <div class="user-dropdown">
        <div class="user-btn">
            <i class="bi bi-person-circle"></i>
            <span>{{ $currentUser->name }}</span>
        </div>
        <div class="dropdown-content">
            <a href="{{ \App\Helpers\TabAuthHelper::route('profile') }}" class="dropdown-item">
                <i class="bi bi-person"></i>
                <span>Xem hồ sơ</span>
            </a>

            <form action="{{ \App\Helpers\TabAuthHelper::route('logout') }}" method="POST" style="margin: 0;">
                @csrf
                @method('POST')
                <input type="hidden" name="tab_token" value="{{ request('tab_token') }}">
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
@endif

    </div>

</header>
