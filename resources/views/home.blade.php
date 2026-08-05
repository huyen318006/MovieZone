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
                        <img src="{{ $combo->image_url ? asset($combo->image_url) : asset('assets/promo/1.jpg') }}" alt="{{ $combo->name }}">
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
{{-- NEWS SECTION - bên cạnh phim --}}
<section class="home-layout">

    <div class="movie-content">

        <div class="section-title">
            <h2>Tin Tức &amp; Khuyến Mãi</h2>
            <a href="{{ \App\Helpers\TabAuthHelper::route('news') }}">Xem tất cả</a>
        </div>

        <div class="news-inline-grid">
            @forelse($latestArticles as $article)
                <article class="news-inline-card">
                    <a href="{{ \App\Helpers\TabAuthHelper::route('news.detail', $article->slug) }}" class="news-inline-link">
                        <img src="{{ $article->thumbnail_url ? asset($article->thumbnail_url) : asset('assets/news/batman.jpg') }}" alt="{{ $article->title }}">
                        <div class="news-inline-content">
                            <span class="news-inline-date">{{ optional($article->created_at)->format('d/m/Y') }}</span>
                            <h4>{{ $article->title }}</h4>
                            <p>{{ Str::limit($article->summary, 100) }}</p>
                        </div>
                    </a>
                </article>
            @empty
                <div style="grid-column: 1/-1; padding: 40px; text-align: center; color: #aaa;">
                    <h4>Chưa có tin tức</h4>
                    <p>Vui lòng quay lại sau để xem những cập nhật mới nhất.</p>
                </div>
            @endforelse
        </div>

    </div>

    <aside class="promo-sidebar">

        <h3>Khuyến Mãi Nổi Bật</h3>

        @forelse($promotions as $promotion)
            <div class="promo-card">
                <a href="{{ \App\Helpers\TabAuthHelper::route('promotion.show', $promotion->id) }}" style="text-decoration: none; color: inherit;">
                    <img src="{{ $promotion->banner_url ? asset('storage/' . $promotion->banner_url) : asset('assets/promo/1.jpg') }}" alt="{{ $promotion->title }}">
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
    /* NEWS INLINE GRID */
    .news-inline-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    .news-inline-card {
        background: var(--card, #1a2035);
        border-radius: 12px;
        overflow: hidden;
        transition: transform 0.25s, box-shadow 0.25s;
        display: flex;
        flex-direction: column;
    }
    .news-inline-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 30px rgba(126, 166, 255, 0.15);
    }
    .news-inline-link {
        display: flex;
        flex-direction: column;
        height: 100%;
        text-decoration: none;
        color: inherit;
    }
    .news-inline-card img {
        width: 100%;
        height: 160px;
        object-fit: cover;
        flex-shrink: 0;
    }
    .news-inline-content {
        padding: 14px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .news-inline-date {
        font-size: 12px;
        color: var(--text-soft, #9ca3af);
        margin-bottom: 6px;
        display: block;
    }
    .news-inline-content h4 {
        font-size: 14px;
        font-weight: 700;
        margin-bottom: 6px;
        color: #fff;
        line-height: 1.4;
    }
    .news-inline-content p {
        font-size: 13px;
        color: var(--text-soft, #9ca3af);
        line-height: 1.5;
        margin: 0;
    }
</style>
@endsection
