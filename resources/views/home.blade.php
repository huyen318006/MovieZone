@extends('layout.app')

@push('styles')
<style>
    body {
        padding-top: 0 !important;
    }
</style>
@endpush
@section('content')

{{-- HERO SECTION --}}

<section class="hero-slider">
    @forelse($banners ?? [] as $index => $banner)
        <div class="hero-slide {{ $index === 0 ? 'active' : '' }}">
            @if($banner->link_url)
                    <div class="hero-buttons">
                        <a href="{{ $banner->link_url }}">
                            <img src="{{ asset('storage/' . $banner->image_url) }}" alt="{{ $banner->title }}" style="width: 100%; height: 100%;">
                        </a>
                    </div>
                @endif
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
            href="{{ \App\Helpers\TabAuthHelper::route('movie.detail', $m->slug) }}"
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
                            <img src="{{ asset('storage/' . $banner->image_url) }}" alt="{{ $banner->title }}" style="width: 100%; max-height: 700px; object-fit: cover; border-radius: 12px;">
                        </a>
                    @else
                        <img src="{{ asset('storage/' . $banner->image_url) }}" alt="{{ $banner->title }}" style="width: 100%; max-height: 700px; object-fit: cover; border-radius: 12px;">
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
        <h2>Tin Tức & Khuyến Mãi</h2>
        <a href="{{ \App\Helpers\TabAuthHelper::route('news') }}">Xem tất cả</a>
    </div>

    <div class="news-grid">

        <article class="news-feature">
            @forelse($latestArticles->take(3) as $index => $article)
                <div class="news-slide {{ $index === 0 ? 'active' : '' }}">
                    <a href="{{ \App\Helpers\TabAuthHelper::route('news.detail', $article->slug) }}" style="text-decoration: none; color: inherit; display: block; height: 100%;">
                        <img src="{{ $article->thumbnail_url ? asset($article->thumbnail_url) : asset('assets/news/batman.jpg') }}" alt="{{ $article->title }}">
                        <div class="news-overlay">
                            <span class="news-tag">{{ $article->category }}</span>
                            <h3>{{ $article->title }}</h3>
                            <p>{{ $article->summary }}</p>
                        </div>
                    </a>
                </div>
            @empty
                <div class="news-slide active">
                    <img src="{{ asset('assets/news/batman.jpg') }}" alt="No news">
                    <div class="news-overlay">
                        <span class="news-tag">Thông báo</span>
                        <h3>Chưa có tin tức nào được đăng tải</h3>
                        <p>Vui lòng quay lại sau để cập nhật các tin tức điện ảnh mới nhất.</p>
                    </div>
                </div>
            @endforelse

            @if($latestArticles->take(3)->count() > 1)
                <div class="news-dots">
                    @foreach($latestArticles->take(3) as $index => $article)
                        <span class="{{ $index === 0 ? 'active' : '' }}"></span>
                    @endforeach
                </div>
            @endif

        </article>

        <div class="news-side">
            @foreach($latestArticles->slice(3) as $article)
                <article class="news-small">
                    <a href="{{ \App\Helpers\TabAuthHelper::route('news.detail', $article->slug) }}" class="d-flex gap-3 text-decoration-none w-100" style="color: inherit;">
                        <img src="{{ $article->thumbnail_url ? asset($article->thumbnail_url) : asset('assets/news/batman.jpg') }}" alt="{{ $article->title }}" style="width: 120px; height: 80px; object-fit: cover; border-radius: 8px;">
                        <div class="news-content">
                            <span>{{ $article->created_at->format('d/m/Y') }}</span>
                            <h4 style="margin: 0; font-size: 14px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">{{ $article->title }}</h4>
                        </div>
                    </a>
                </article>
            @endforeach
        </div>

    </div>

</section>
<style>
    .news-slide img {
        object-fit: contain !important;
        background-color: #0b0f19 !important;
    }
    .news-side {
        height: 778px !important;
    }
    .news-small {
        flex: 1 !important;
        display: flex !important;
        flex-direction: column !important;
        height: 100% !important;
    }
    .news-small a {
        display: flex !important;
        flex-direction: column !important;
        height: 100% !important;
        color: inherit !important;
        text-decoration: none !important;
    }
    .news-small img {
        width: 100% !important;
        flex: 1 !important;
        min-height: 0 !important;
        object-fit: contain !important;
        background-color: #0b0f19 !important;
    }
</style>
@endsection
