@extends('layout.app')

@section('content')
@php
    $selectedDateValue = $selectedDate ? \Carbon\Carbon::parse($selectedDate)->format('Y-m-d') : '';
@endphp

<main class="showtime-page">
    <section class="showtime-hero" data-aos="fade-up">
        <span class="badge">MOVIEZONE SCHEDULE</span>
        <h1>Lịch Chiếu</h1>
        <p>Chọn phim, rạp và ngày chiếu để tìm suất chiếu phù hợp nhất với bạn.</p>
    </section>

    @if(session('error'))
        <section class="showtime-alert" data-aos="fade-up">
            <i class="bi bi-exclamation-triangle"></i>
            <span>{{ session('error') }}</span>
        </section>
    @endif

    <section class="showtime-filter-panel" data-aos="fade-up">
        <form action="{{ route('showtimes') }}" method="GET" class="showtime-filter-form">
            <select id="showtime-movie" name="movie">
                <option value="">Tất cả phim</option>
                @foreach($movies as $movie)
                    <option value="{{ $movie->id }}" @selected(($filters['movie'] ?? '') == $movie->id)>
                        {{ $movie->title }}
                    </option>
                @endforeach
            </select>

            <select id="showtime-cinema" name="cinema">
                <option value="">Tất cả rạp</option>
                @foreach($cinemas as $cinema)
                    <option value="{{ $cinema->id }}" @selected(($filters['cinema'] ?? '') == $cinema->id)>
                        {{ $cinema->name }} - {{ $cinema->city }}
                    </option>
                @endforeach
            </select>

            <select id="showtime-date" name="date">
                <option value="">Ngày gần nhất</option>
                @foreach($availableDates as $date)
                    @php($dateValue = \Carbon\Carbon::parse($date)->format('Y-m-d'))
                    <option value="{{ $dateValue }}" @selected($selectedDateValue === $dateValue)>
                        {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
                    </option>
                @endforeach
            </select>

            <div class="showtime-filter-actions">
                <button id="showtime-filter-submit" type="submit">
                    <i class="bi bi-search"></i> Tìm suất chiếu
                </button>
                <a id="showtime-filter-reset" href="{{ route('showtimes') }}">Xóa lọc</a>
            </div>
        </form>
    </section>

    <section class="showtime-context" data-aos="fade-up">
        <div>
            <strong>Phim</strong>
            <span>{{ $selectedMovie?->title ?: 'Tất cả phim' }}</span>
        </div>
        <div>
            <strong>Rạp</strong>
            <span>{{ $selectedCinema?->name ?: 'Tất cả rạp' }}</span>
        </div>
        <div>
            <strong>Ngày</strong>
            <span>{{ $selectedDate ? \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') : 'Ngày gần nhất' }}</span>
        </div>
    </section>

    <section class="showtime-results" data-aos="fade-up">
        <div class="section-title">
            <h2>Suất Chiếu Phù Hợp</h2>
            <a href="{{ route('movies') }}">Xem danh sách phim</a>
        </div>

        @if($showtimes->isEmpty())
            <div class="showtime-empty-state">
                <i class="bi bi-calendar-x"></i>
                <h3>Không có suất chiếu phù hợp</h3>
                <p>Bạn có thể chọn rạp hoặc ngày khác để tiếp tục tìm kiếm.</p>
            </div>
        @else
            <div class="showtime-grid">
                @foreach($showtimes as $showtime)
                    @php
                        $startTime = \Carbon\Carbon::parse($showtime->start_time);
                        $endTime = \Carbon\Carbon::parse($showtime->end_time);
                        $poster = $showtime->movie?->poster_url
                            ? asset($showtime->movie->poster_url)
                            : asset('assets/movies/dune.jpg');
                    @endphp

                    <article class="showtime-card">
                        <div class="showtime-card-poster">
                            <img src="{{ $poster }}" alt="Poster {{ $showtime->movie?->title }}">
                        </div>

                        <div class="showtime-card-body">
                            <div class="showtime-card-head">
                                <div>
                                    <span>{{ $startTime->format('d/m/Y') }}</span>
                                    <h3>{{ $showtime->movie?->title ?: 'Phim đang cập nhật' }}</h3>
                                </div>
                                <strong>{{ $startTime->format('H:i') }}</strong>
                            </div>

                            <div class="showtime-card-meta">
                                <span><i class="bi bi-building"></i>{{ $showtime->cinema?->name ?: 'Rạp đang cập nhật' }}</span>
                                <span><i class="bi bi-door-open"></i>{{ $showtime->room?->name ?: 'Phòng đang cập nhật' }}</span>
                                <span><i class="bi bi-clock"></i>{{ $startTime->format('H:i') }} - {{ $endTime->format('H:i') }}</span>
                                <span><i class="bi bi-badge-3d"></i>{{ $showtime->format }} • {{ $showtime->language_type }}</span>
                            </div>

                            <div class="showtime-card-footer">
                                <div>
                                    <small>Giá từ</small>
                                    <strong>
                                        {{ $showtime->min_ticket_price ? number_format($showtime->min_ticket_price, 0, ',', '.') . 'đ' : 'Hết ghế' }}
                                    </strong>
                                </div>
                                <div>
                                    <small>Ghế còn lại</small>
                                    <strong>{{ $showtime->available_seats_count }}</strong>
                                </div>
                            </div>

                            @if($showtime->available_seats_count > 0)
                                <a href="{{ route('showtimes.select', $showtime) }}" class="showtime-select-btn">
                                    Chọn suất chiếu <i class="bi bi-arrow-right"></i>
                                </a>
                            @else
                                <button class="showtime-select-btn disabled" type="button" disabled>Hết ghế</button>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</main>
@endsection
