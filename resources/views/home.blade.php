@extends('layout.app')

@push('styles')
<style>
    .hero-slider {
        position: relative;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: hidden;
        display: grid;
        grid-template-areas: "slide";
        background: transparent;
    }

    .hero-slide {
        grid-area: slide;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        opacity: 0;
        transition: opacity 1s ease-in-out;
        display: block !important;
    }

    .hero-slide.active {
        opacity: 1;
        z-index: 2;
    }

    .hero-slide a {
        display: block !important;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        line-height: 0 !important;
        text-decoration: none;
    }

    .hero-slide img {
        width: 100% !important;
        min-width: 100% !important;
        max-width: 100% !important;
        height: auto !important;
        display: block !important;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
    }
</style>
@endpush

@section('content')

{{-- HERO SECTION --}}

<section class="hero-slider">
    @forelse($banners ?? [] as $index => $banner)
        <div class="hero-slide {{ $index === 0 ? 'active' : '' }}">
            @if($banner->link_url)
                <a href="{{ $banner->link_url }}">
                    <img src="{{ asset('storage/' . $banner->image_url) }}" alt="{{ $banner->title ?? 'Banner' }}">
                </a>
            @else
                <img src="{{ asset('storage/' . $banner->image_url) }}" alt="{{ $banner->title ?? 'Banner' }}">
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

        <img src="{{ $m->poster }}" alt="{{ $m->title }}">

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
        @if(isset($promoCombos) && $promoCombos->isNotEmpty())
            @foreach($promoCombos as $combo)
                <div class="promo-card">
                    <a href="{{ route('booking.combo') }}" style="text-decoration: none; color: inherit;">
                        <img src="{{ $combo->image ?? asset('assets/promo/1.jpg') }}" alt="{{ $combo->name }}">
                        <div class="promo-info">
                            <strong style="display:block; margin-bottom:4px;">{{ $combo->name }}</strong>
                            <small>{{ number_format($combo->price ?? 0, 0, ',', '.') }} VNĐ</small>
                        </div>
                    </a>
                </div>
            @endforeach
        @else
            <div class="promo-card">
                <img src="{{ asset('assets/promo/1.jpg') }}" alt="">
                <div class="promo-info">Giảm 50% vé thứ 2</div>
            </div>

            <div class="promo-card">
                <img src="{{ asset('assets/promo/2.jpg') }}" alt="">
                <div class="promo-info">Combo bắp nước chỉ từ 49K</div>
            </div>
        @endif

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

        <img src="{{ $m->poster }}" alt="{{ $m->title }}">

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
{{-- NEWS SECTION - bên cạnh phim --}}
<section class="home-layout">

    <div class="movie-content">

        <div class="section-title">
            <h2>Tin Tức &amp; Khuyến Mãi</h2>
            <a href="{{ \App\Helpers\TabAuthHelper::route('news') }}">Xem tất cả</a>
        </div>

        <div class="news-slider-wrapper">
            <div id="newsSliderCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="4000">
                @if(count($latestArticles ?? []) > 1)
                    <div class="carousel-indicators news-carousel-indicators">
                        @foreach($latestArticles as $index => $article)
                            <button type="button" data-bs-target="#newsSliderCarousel" data-bs-slide-to="{{ $index }}" class="{{ $index === 0 ? 'active' : '' }}" aria-current="{{ $index === 0 ? 'true' : 'false' }}" aria-label="Slide {{ $index + 1 }}"></button>
                        @endforeach
                    </div>
                @endif

                <div class="carousel-inner news-carousel-inner">
                    @forelse($latestArticles as $index => $article)
                        <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                            <a href="{{ \App\Helpers\TabAuthHelper::route('news.detail', $article->slug) }}" class="news-slide-card">
                                <div class="news-slide-img-box">
                                    <img src="{{ $article->thumbnail }}" alt="{{ $article->title }}">
                                    <div class="news-slide-overlay"></div>
                                </div>
                                <div class="news-slide-info">
                                    <span class="news-slide-date">
                                        <i class="fa-regular fa-calendar-days"></i> {{ optional($article->created_at)->format('d/m/Y') }}
                                    </span>
                                    <h3 class="news-slide-title">{{ $article->title }}</h3>
                                    @if($article->summary)
                                        <p class="news-slide-summary">{{ Str::limit($article->summary, 120) }}</p>
                                    @endif
                                </div>
                            </a>
                        </div>
                    @empty
                        <div class="news-slide-empty">
                            <h4>Chưa có tin tức</h4>
                            <p>Vui lòng quay lại sau để xem những cập nhật mới nhất.</p>
                        </div>
                    @endforelse
                </div>

                @if(count($latestArticles ?? []) > 1)
                    <button class="carousel-control-prev news-carousel-btn prev-btn" type="button" data-bs-target="#newsSliderCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next news-carousel-btn next-btn" type="button" data-bs-target="#newsSliderCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                @endif
            </div>
        </div>

    </div>

    <aside class="promo-sidebar">

        <h3>Khuyến Mãi Nổi Bật</h3>

        @forelse($promotions as $promotion)
            <div class="promo-card">
                <a href="{{ \App\Helpers\TabAuthHelper::route('promotion.show', $promotion->id) }}" style="text-decoration: none; color: inherit;">
                    <img src="{{ $promotion->banner }}" alt="{{ $promotion->title }}">
                    <div class="promo-info">
                        <strong style="display:block; margin-bottom:4px; color:#fff;">{{ $promotion->title }}</strong>
                        <small>{{ optional($promotion->start_date)->format('d/m/Y') }} - {{ optional($promotion->end_date)->format('d/m/Y') }}</small>
                    </div>
                </a>
            </div>
        @empty
            <div class="promo-card" style="padding: 20px; text-align: center; color: #aaa;">
                <p>Hiện không có chương trình nào đang chạy.</p>
            </div>
        @endforelse

    </aside>

</section>
<style>
    /* NEWS SINGLE BOX SLIDER */
    .news-slider-wrapper {
        width: 100%;
        border-radius: 20px;
        overflow: hidden;
        background: var(--card, #161d2f);
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        position: relative;
    }

    .news-slide-card {
        display: block;
        position: relative;
        text-decoration: none;
        color: inherit;
        overflow: hidden;
        width: 100%;
        height: 460px;
        aspect-ratio: 16 / 9;
        max-height: 500px;
    }

    .news-slide-img-box {
        width: 100%;
        height: 100%;
        position: relative;
        overflow: hidden;
    }

    .news-slide-img-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        transition: transform 0.6s cubic-bezier(0.25, 1, 0.5, 1);
    }

    .news-slide-card:hover .news-slide-img-box img {
        transform: scale(1.05);
    }

    .news-slide-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(13, 17, 27, 0) 15%, rgba(13, 17, 27, 0.65) 60%, rgba(13, 17, 27, 0.98) 100%);
    }

    .news-slide-info {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 30px 35px;
        z-index: 2;
    }

    .news-slide-date {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 600;
        color: #7ea6ff;
        background: rgba(126, 166, 255, 0.15);
        border: 1px solid rgba(126, 166, 255, 0.3);
        padding: 4px 14px;
        border-radius: 20px;
        margin-bottom: 12px;
        backdrop-filter: blur(4px);
    }

    .news-slide-title {
        font-size: 22px;
        font-weight: 700;
        color: #ffffff;
        margin-bottom: 8px;
        line-height: 1.35;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        transition: color 0.3s ease;
    }

    .news-slide-card:hover .news-slide-title {
        color: #7ea6ff;
    }

    .news-slide-summary {
        font-size: 14px;
        color: #b9c2dc;
        line-height: 1.5;
        margin: 0;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        opacity: 0.9;
    }

    .news-slide-empty {
        padding: 60px 20px;
        text-align: center;
        color: #aaa;
    }

    /* CAROUSEL CONTROLS & INDICATORS */
    .news-carousel-btn {
        width: 44px;
        height: 44px;
        background: rgba(13, 17, 27, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 50%;
        top: 50%;
        transform: translateY(-50%);
        opacity: 0;
        transition: all 0.3s ease;
        backdrop-filter: blur(6px);
        margin: 0 15px;
    }

    .news-slider-wrapper:hover .news-carousel-btn {
        opacity: 0.85;
    }

    .news-carousel-btn:hover {
        opacity: 1 !important;
        background: #7ea6ff;
        border-color: #7ea6ff;
    }

    .news-carousel-indicators {
        margin-bottom: 12px;
        gap: 6px;
    }

    .news-carousel-indicators button {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.4);
        border: none;
        transition: all 0.3s ease;
        opacity: 0.6;
    }

    .news-carousel-indicators button.active {
        width: 28px;
        border-radius: 10px;
        background-color: #7ea6ff;
        opacity: 1;
    }
</style>
@endsection
