@extends('layout.app')

@section('content')

{{-- HERO SECTION --}}

<section class="hero-slider">
    @forelse($banners ?? [] as $index => $banner)
        <div class="hero-slide {{ $index === 0 ? 'active' : '' }}">
            <img src="{{ asset('storage/' . $banner->image_url) }}" alt="{{ $banner->title }}">
            <div class="overlay"></div>

            <div class="hero-content">
                <span class="badge">CHƯƠNG TRÌNH KHUYẾN MÃI</span>
                <h1>{{ $banner->title }}</h1>
                <p>
                    Cập nhật các chương trình ưu đãi, sự kiện và phim hot nhất tại MovieZone.
                </p>

                @if($banner->link_url)
                    <div class="hero-buttons">
                        <a href="{{ $banner->link_url }}" class="hero-btn-primary">Xem Chi Tiết</a>
                    </div>
                @endif
            </div>
        </div>
    @empty
    @endforelse
</section>

{{-- PHIM ĐANG CHIẾU --}}

<section class="home-layout">

    <div class="movie-content">

        <div class="section-title">

            <h2>Phim Đang Chiếu</h2>

            <a href="#">
                Xem tất cả
            </a>

        </div>

        <div class="movie-row">
@foreach($showingMovies as $m)            
    <div class="movie-card">

        <img src="{{ $m->poster_url ? asset('assets/' . $m->poster_url) : asset('assets/hero/avatar.jpg') }}" alt="{{ $m->title }}">

        <div class="info">

            <h4>{{ $m->title }}</h4>
            <span>Khởi chiếu: {{ \Carbon\Carbon::parse($m->release_date)->format('d/m/Y') }}</span>

        </div>

        <a
            href="{{ route('movie.detail', $m->slug) }}"
            class="book-btn">

            Chi Tiết

        </a>

    </div>

@endforeach
</div>

    </div>

    <aside class="promo-sidebar">

        <h3>Khuyến Mãi</h3>
        <div class="promo-card">
            <img
                src="{{ asset('assets/promo/1.jpg') }}"
                alt="">
            <div class="promo-info">
                Giảm 50% vé thứ 2
            </div>
        </div>

        <div class="promo-card">

            <img
                src="{{ asset('assets/promo/2.jpg') }}"
                alt="">

            <div class="promo-info">

                Combo bắp nước chỉ từ 49K

            </div>

        </div>

    </aside>

</section>

{{--  BANNER GIỮA TRANG (HOME_MIDDLE)  --}}
@php
    $homeMiddleBanners = \App\Models\Banner::where('position', 'HOME_MIDDLE')
        ->where('status', 'ACTIVE')
        ->where(function ($q) {
            $q->whereNull('start_date')->orWhere('start_date', '<=', now());
        })
        ->where(function ($q) {
            $q->whereNull('end_date')->orWhere('end_date', '>=', now());
        })
        ->limit(1)
        ->get();
@endphp
@if(isset($homeMiddleBanners) && $homeMiddleBanners->count() > 0)
    <div class="container my-4">
        <div class="middle-banner-wrapper" style="width: 100%; text-align: center; margin: 30px 0;">
            @foreach($homeMiddleBanners as $banner)
                <div class="banner-item mb-3">
                    @if($banner->link_url)
                        <a href="{{ $banner->link_url }}" target="_blank">
                            <img src="{{ asset('storage/' . $banner->image_url) }}" alt="{{ $banner->title }}" style="width: 100%; max-height: 600px; object-fit: cover; border-radius: 12px;">
                        </a>
                    @else
                        <img src="{{ asset('storage/' . $banner->image_url) }}" alt="{{ $banner->title }}" style="width: 100%; max-height: 600px; object-fit: cover; border-radius: 12px;">
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- PHIM SẮP CHIẾU --}}

<section class="home-layout">

    <div class="movie-content">

        <div class="section-title">

            <h2>Phim Sắp Chiếu</h2>

            <a href="#">
                Xem tất cả
            </a>

        </div>

        <div class="movie-row">
@foreach($upcomingMovies as $m)
    <div class="movie-card">

        <img src="{{ $m->poster_url ? asset('assets/' . $m->poster_url) : asset('assets/hero/avatar.jpg') }}" alt="{{ $m->title }}">

        <div class="info">

            <h4>{{ $m->title }}</h4>
                        <span>Khởi chiếu: {{ \Carbon\Carbon::parse($m->release_date)->format('d/m/Y') }}</span>

        </div>

        <a href="{{ route('movie.detail', $m->slug) }}" class="book-btn">Chi Tiết</a>

    </div>

@endforeach
</div>

    </div>

    <aside class="promo-sidebar small">

        <div class="promo-card">

            <img
                src="{{ asset('assets/promo/3.jpg') }}"
                alt="">

            <div class="promo-info">

                Thành viên MovieZone giảm 20%

            </div>

        </div>
        <div class="promo-card">

            <img
                src="{{ asset('assets/promo/4.png') }}"
                alt="">

            <div class="promo-info">

                Combo bắp nước chỉ từ 49K

            </div>

        </div>

    </aside>

</section>
{{-- NEWS SECTION --}}
<section class="news-section">

    <div class="section-title">
        <h2>Tin Tức Điện Ảnh</h2>
        <a href="#">Xem tất cả</a>
    </div>

    <div class="news-grid">

        <article class="news-feature">

            <div class="news-slide active">
                <img src="{{ asset('assets/news/batman.jpg') }}" alt="">
                <div class="news-overlay">
                    <span class="news-tag">Tin nổi bật</span>
                    <h3>Avatar 3 chính thức tung trailer đầu tiên</h3>
                    <p>Bộ phim bom tấn tiếp theo của James Cameron sẽ ra mắt cuối năm nay.</p>
                </div>
            </div>

            <div class="news-slide">
                <img src="{{ asset('assets/news/mechanic.jpg') }}" alt="">
                <div class="news-overlay">
                    <span class="news-tag">Điện ảnh</span>
                    <h3>Dune Part Three xác nhận khởi quay</h3>
                    <p>Phần tiếp theo của thương hiệu Dune đang được sản xuất.</p>
                </div>
            </div>

            <div class="news-slide">
                <img src="{{ asset('assets/news/deadpool.jpg') }}" alt="">
                <div class="news-overlay">
                    <span class="news-tag">Bom tấn</span>
                    <h3>Deadpool & Wolverine lập kỷ lục doanh thu</h3>
                    <p>Bộ đôi Marvel tiếp tục thống trị phòng vé toàn cầu.</p>
                </div>
            </div>

            <div class="news-dots">
                <span class="active"></span>
                <span></span>
                <span></span>
            </div>

        </article>

        <div class="news-side">

            <article class="news-small">
                <img src="{{ asset('assets/news/conan.jpg') }}" alt="">
                <div class="news-content">
                    <span>03/06/2026</span>
                    <h4>Dune Part Three xác nhận khởi quay</h4>
                </div>
            </article>

            <article class="news-small">
                <img src="{{ asset('assets/news/deadpool.jpg') }}" alt="">
                <div class="news-content">
                    <span>02/06/2026</span>
                    <h4>John Wick trở lại với phần phim mới</h4>
                </div>
            </article>

        </div>

    </div>

</section>
@endsection
