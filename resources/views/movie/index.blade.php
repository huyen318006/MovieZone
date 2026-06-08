@extends('layout.app')

@section('content')
@php
    $statusLabels = [
        'NOW_SHOWING' => 'Đang chiếu',
        'COMING_SOON' => 'Sắp chiếu',
        'ENDED' => 'Đã kết thúc',
    ];
@endphp

<main class="movie-list-page">
    <section class="movie-list-hero">
        <div class="movie-list-hero__glow"></div>
        <div class="movie-list-hero__content" data-aos="fade-up">
            <span class="badge">MOVIEZONE CINEMA</span>
            <h1>Khám phá kho phim</h1>
            <p>Lọc phim theo thể loại, độ tuổi, ngôn ngữ, rạp chiếu hoặc tìm nhanh bộ phim bạn yêu thích.</p>
        </div>
    </section>

    <section class="movie-filter-section" data-aos="fade-up">
        <form action="{{ route('movies') }}" method="GET" class="movie-filter-form">
            <div class="filter-search-field">
                <i class="bi bi-search"></i>
                <input
                    id="movie-keyword"
                    name="keyword"
                    type="text"
                    value="{{ $filters['keyword'] ?? '' }}"
                    placeholder="Tìm theo tên phim...">
            </div>

            <select id="movie-genre" name="genre">
                <option value="">Tất cả thể loại</option>
                @foreach($genres as $genre)
                    <option value="{{ $genre->id }}" @selected(($filters['genre'] ?? '') == $genre->id)>
                        {{ $genre->name }}
                    </option>
                @endforeach
            </select>

            <select id="movie-age-rating" name="age_rating">
                <option value="">Mọi độ tuổi</option>
                @foreach($ageRatings as $ageRating)
                    <option value="{{ $ageRating }}" @selected(($filters['age_rating'] ?? '') === $ageRating)>
                        {{ $ageRating }}
                    </option>
                @endforeach
            </select>

            <select id="movie-language" name="language">
                <option value="">Mọi ngôn ngữ</option>
                @foreach($languages as $language)
                    <option value="{{ $language }}" @selected(($filters['language'] ?? '') === $language)>
                        {{ $language }}
                    </option>
                @endforeach
            </select>

            <select id="movie-cinema" name="cinema">
                <option value="">Tất cả rạp</option>
                @foreach($cinemas as $cinema)
                    <option value="{{ $cinema->id }}" @selected(($filters['cinema'] ?? '') == $cinema->id)>
                        {{ $cinema->name }}
                    </option>
                @endforeach
            </select>

            <select id="movie-status" name="status">
                <option value="">Mọi trạng thái</option>
                @foreach($allowedStatuses as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
                        {{ $statusLabels[$status] ?? $status }}
                    </option>
                @endforeach
            </select>

            <div class="filter-actions">
                <button id="movie-filter-submit" type="submit" class="filter-submit-btn">
                    <i class="bi bi-funnel"></i> Lọc phim
                </button>
                <a id="movie-filter-reset" href="{{ route('movies') }}" class="filter-reset-btn">Xóa lọc</a>
            </div>
        </form>
    </section>

    <section class="movie-results-section">
        @if($loadError)
            <div class="movie-state-card error-state" data-aos="fade-up">
                <i class="bi bi-exclamation-triangle"></i>
                <h2>Lỗi tải danh sách phim</h2>
                <p>{{ $loadError }}</p>
                <a href="{{ route('movies') }}">Thử lại</a>
            </div>
        @elseif($movies->isEmpty())
            <div class="movie-state-card" data-aos="fade-up">
                <i class="bi bi-film"></i>
                <h2>Không tìm thấy phim phù hợp</h2>
                <p>Bạn có thể thay đổi từ khóa hoặc bộ lọc để xem thêm phim khác.</p>
                <a href="{{ route('movies') }}">Xem tất cả phim</a>
            </div>
        @else
            <div class="movie-results-header">
                <div>
                    <span class="badge">{{ $movies->total() }} phim</span>
                    <h2>Danh sách phim</h2>
                </div>
            </div>

            <div class="movie-list-grid">
                @foreach($movies as $movie)
                    @php
                        $poster = $movie->poster_url
                            ? asset($movie->poster_url)
                            : asset('assets/movies/dune.jpg');
                    @endphp
                    <article class="movie-list-card" data-aos="fade-up">
                        <div class="movie-list-poster">
                            <img src="{{ $poster }}" alt="Poster {{ $movie->title }}">
                            <span class="movie-status-badge status-{{ strtolower($movie->status) }}">
                                {{ $statusLabels[$movie->status] ?? $movie->status }}
                            </span>
                        </div>

                        <div class="movie-list-info">
                            <h3>{{ $movie->title }}</h3>
                            <p class="movie-original-title">{{ $movie->original_title ?: 'MovieZone Original' }}</p>

                            <div class="movie-genre-list">
                                @forelse($movie->genres as $genre)
                                    <span>{{ $genre->name }}</span>
                                @empty
                                    <span>Chưa cập nhật</span>
                                @endforelse
                            </div>

                            <div class="movie-meta-list">
                                <span><i class="bi bi-shield-check"></i>{{ $movie->age_rating }}</span>
                                <span><i class="bi bi-translate"></i>{{ $movie->language }}</span>
                                <span><i class="bi bi-clock"></i>{{ $movie->duration_minutes }} phút</span>
                            </div>

                            <a href="{{ route('movie.detail') }}" class="movie-detail-link">
                                Xem chi tiết <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="movie-pagination">
                {{ $movies->links() }}
            </div>
        @endif
    </section>
</main>
@endsection
