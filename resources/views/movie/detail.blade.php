@extends('layout.app')

@section('content')
@php
    $poster = $movie->poster_url ? asset($movie->poster_url) : asset('assets/hero/avatar.jpg');
    $banner = $movie->banner_url ? asset($movie->banner_url) : asset('assets/hero/avatar2.jpg');
    $averageRating = $movie->approvedReviews->avg('rating');
@endphp

<section class="movie-detail" style="--movie-banner: url('{{ $banner }}')">
    <div class="movie-backdrop"></div>
    <div class="movie-detail-card" data-aos="fade-up">
        <div class="movie-content">
            <h1 class="movie-title">{{ $movie->title }}</h1>

            <div class="movie-tags">
                @forelse($movie->genres as $genre)
                    <span>{{ $genre->name }}</span>
                @empty
                    <span>Chưa cập nhật thể loại</span>
                @endforelse
            </div>

            <div class="movie-stats">
                <span>
                    <i class="fa-solid fa-star"></i>
                    {{ $averageRating ? number_format($averageRating, 1) : 'Chưa có' }} / 5
                </span>
                <span><i class="fa-regular fa-clock"></i> {{ $movie->duration_minutes }} phút</span>
                <span>{{ $movie->age_rating }}</span>
                <span>{{ $movie->language }}</span>
            </div>

            <p class="movie-description">
                {{ $movie->description ?: 'Nội dung phim đang được cập nhật.' }}
            </p>

            <div class="movie-facts">
                <div>
                    <strong>Đạo diễn</strong>
                    <span>{{ $movie->director ?: 'Đang cập nhật' }}</span>
                </div>
                <div>
                    <strong>Diễn viên</strong>
                    <span>{{ $movie->cast ?: 'Đang cập nhật' }}</span>
                </div>
                <div>
                    <strong>Phụ đề</strong>
                    <span>{{ $movie->subtitle ?: 'Không có' }}</span>
                </div>
                <div>
                    <strong>Khởi chiếu</strong>
                    <span>{{ $movie->release_date ? \Carbon\Carbon::parse($movie->release_date)->format('d/m/Y') : 'Đang cập nhật' }}</span>
                </div>
            </div>

            <div class="movie-actions">
                <a href="#movie-showtimes" class="btn-book">
                    <i class="fa-solid fa-ticket"></i> Xem Suất Chiếu
                </a>
                <button class="btn-trailer" data-bs-toggle="modal" data-bs-target="#trailerModal">
                    <i class="fa-solid fa-play"></i> Xem Trailer
                </button>
            </div>
        </div>

        <div class="movie-poster">
            <img src="{{ $poster }}" alt="Poster {{ $movie->title }}">
            <button class="poster-play-btn" data-bs-toggle="modal" data-bs-target="#trailerModal">
                <i class="fa-solid fa-play"></i>
            </button>
        </div>
    </div>
</section>

<section id="movie-showtimes" class="movie-detail-section movie-showtime-section">
    <div class="section-title">
        <h2>Lịch Chiếu Liên Quan</h2>
        <a href="{{ route('showtimes') }}">Xem lịch chiếu đầy đủ</a>
    </div>

    @if($movie->showtimes->isEmpty())
        <div class="movie-detail-empty">
            <i class="bi bi-calendar-x"></i>
            <h3>Chưa có suất chiếu phù hợp</h3>
            <p>Lịch chiếu chi tiết sẽ được cập nhật ở UC-CUS-07.</p>
        </div>
    @else
        <div class="detail-showtime-grid">
            @foreach($movie->showtimes as $showtime)
                <article class="detail-showtime-card">
                    <div>
                        <span class="showtime-date">{{ $showtime->start_time ? \Carbon\Carbon::parse($showtime->start_time)->format('d/m/Y') : 'Đang cập nhật' }}</span>
                        <strong>{{ $showtime->start_time ? \Carbon\Carbon::parse($showtime->start_time)->format('H:i') : '--:--' }}</strong>
                    </div>
                    <p>{{ $showtime->cinema?->name ?: 'Rạp đang cập nhật' }}</p>
                    <span>{{ $showtime->room?->name ?: 'Phòng đang cập nhật' }} • {{ $showtime->format }} • {{ $showtime->language_type }}</span>
                    <a href="{{ route('booking.seat', ['showtime_id' => $showtime->id]) }}">Chọn suất</a>
                </article>
            @endforeach
        </div>
    @endif
</section>

<section class="movie-detail-section movie-review-section">
    <div class="section-title">
        <h2>Đánh Giá Từ Khán Giả</h2>
    </div>

    @if($movie->approvedReviews->isEmpty())
        <div class="movie-detail-empty">
            <i class="bi bi-chat-square-heart"></i>
            <h3>Chưa có đánh giá</h3>
            <p>Hãy quay lại sau để xem nhận xét từ người dùng khác.</p>
        </div>
    @else
        <div class="detail-review-grid">
            @foreach($movie->approvedReviews as $review)
                <article class="detail-review-card">
                    <div class="review-head">
                        <strong>{{ $review->user?->name ?: 'Khách hàng MovieZone' }}</strong>
                        <span><i class="fa-solid fa-star"></i> {{ $review->rating }}/5</span>
                    </div>
                    <p>{{ $review->comment ?: 'Người dùng chưa để lại bình luận.' }}</p>
                    <small>{{ $review->created_at?->format('d/m/Y') }}</small>
                </article>
            @endforeach
        </div>
    @endif
</section>

<div class="modal fade" id="trailerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content trailer-modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Trailer - {{ $movie->title }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if($trailerEmbedUrl)
                    <iframe
                        src="{{ $trailerEmbedUrl }}"
                        title="Trailer {{ $movie->title }}"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen></iframe>
                @else
                    <div class="trailer-error">
                        <i class="bi bi-exclamation-triangle"></i>
                        <p>Không thể tải trailer</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection