@extends('layout.staff')

@section('title', 'Bán Vé - Staff')

@section('content')
<div class="modern-sell-page">
    <div class="container-fluid px-2 px-md-4">

        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h2 class="fw-bold text-white mb-1 d-flex align-items-center gap-2">
                    <i class="bi bi-ticket-perforated-fill text-danger modern-text-gradient"></i>
                    <span>Bán Vé</span>
                </h2>
                <p class="text-secondary mb-0 fs-14">Chọn phim và suất chiếu để tiến hành bán vé nhanh.</p>
            </div>

        </div>

        <div class="modern-search-card mb-4">
           <!-- #region -->
            <form action="{{ route('staff.sell-tickets') }}" method="GET" class="d-flex align-items-center gap-2 p-2">
                <input
                    type="text"
                    name="search"
                    class="form-control border-0 bg-transparent text-white"
                    placeholder="Tìm kiếm phim..."
                    value="{{ request('search') }}"
                >
                <button type="submit" class="btn btn-danger d-flex align-items-center gap-1">
                    <i class="bi bi-search"></i>
                    Tìm kiếm
                </button>
            </form>
            <!-- #endregion -->
        </div>

        @if(empty($movies))
            <div class="modern-empty-box">
                <div class="modern-empty-icon-wrapper">
                    <i class="bi bi-film"></i>
                </div>
                <h3 class="fw-bold text-white mt-3">Không có suất chiếu nào</h3>
                <p class="text-secondary">Vui lòng kiểm tra lại lịch chiếu hoặc bộ lọc tìm kiếm.</p>
            </div>
        @else
            <div class="row g-4">
                @foreach($movies as $movie)
                    <div class="col-12">
                        <div class="modern-movie-item">
                            <div class="modern-movie-row d-flex flex-column flex-md-row">
                                <div class="modern-movie-poster-col">
                                    <img
                                        src="{{ asset('storage/'.$movie['poster']) }}"
                                        class="modern-movie-poster"
                                        alt="{{ $movie['title'] }}"
                                    >
                                </div>

                                <div class="modern-movie-content-col flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <div>
                                            <h3 class="modern-movie-title mb-2">{{ $movie['title'] }}</h3>
                                            <div class="d-flex align-items-center gap-3">
                                                <span class="badge bg-secondary-subtle text-secondary py-1.5 px-3 rounded-pill">
                                                    <i class="bi bi-clock-history me-1.5 text-danger"></i>
                                                    {{ $movie['duration'] }} phút
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modern-showtime-title">
                                        <i class="bi bi-grid-3x3-gap-fill text-danger"></i>
                                        Danh sách suất chiếu
                                    </div>

                                    <div class="row g-3">
                                        @foreach($movie['showtimes'] as $show)
                                            @php
                                                $start = $show['start_time'];
                                                $end = $show['end_time'];
                                                $roomName = $show['room_name'] ?? ('Phòng '.$show['room_id']);
                                                $showDate = $show['show_date'] ?? '';
                                            @endphp
                                            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                                                <a
                                                    href="{{ route('staff.sell-seat',$show['showtime_id']) }}"
                                                    class="modern-showtime-card text-decoration-none"
                                                >
                                                    <div class="modern-showtime-date">
                                                        <i class="bi bi-calendar3"></i>
                                                        {{ $showDate }}
                                                    </div>

                                                    <div class="modern-showtime-header">
                                                        <span class="modern-showtime-hour">{{ $start }}</span>
                                                        <span class="modern-showtime-end">~{{ $end }}</span>
                                                    </div>

                                                    <div class="modern-room-name">
                                                        <i class="bi bi-door-open-fill"></i>
                                                        {{ $roomName }}
                                                    </div>

                                                    <div class="modern-sell-btn">
                                                        <i class="bi bi-cart-plus-fill"></i>
                                                        Bán vé
                                                    </div>
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
/* Sử dụng !important ở các thuộc tính màu sắc cốt lõi để đè bẹp hoàn toàn CSS cũ */
.modern-sell-page {
    min-height: 100vh !important;
    background: #090d16 !important;
    padding: 24px 0 !important;
    color: #f8fafc !important;
    font-family: system-ui, -apple-system, sans-serif !important;
}

.fs-14 { font-size: 14px !important; }

.modern-text-gradient {
    background: linear-gradient(135deg, #f43f5e, #e11d48) !important;
    -webkit-background-clip: text !important;
    -webkit-text-fill-color: transparent !important;
}

.modern-today-card {
    background: #131c2e !important;
    color: #fff !important;
    padding: 10px 18px !important;
    border-radius: 12px !important;
    border: 1px solid rgba(255, 255, 255, 0.05) !important;
    font-weight: 600 !important;
    font-size: 14px !important;
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
}

.modern-search-card {
    background: #131c2e !important;
    border-radius: 16px !important;
    border: 1px solid rgba(255, 255, 255, 0.05) !important;
    overflow: hidden !important;
    transition: all 0.3s ease !important;
}

.modern-search-card:focus-within {
    border-color: rgba(239, 68, 68, 0.4) !important;
}

.modern-search-card input {
    background: transparent !important;
    color: white !important;
    font-size: 15px !important;
}

.modern-movie-item {
    border-radius: 20px !important;
    overflow: hidden !important;
    border: 1px solid rgba(255, 255, 255, 0.05) !important;
    background: #131c2e !important;
    margin-bottom: 20px !important;
}

.modern-movie-poster-col {
    width: 100% !important;
    max-width: 200px !important;
    flex-shrink: 0 !important;
    background: #020617 !important;
}

.modern-movie-poster {
    width: 100% !important;
    height: 100% !important;
    min-height: 250px !important;
    max-height: 270px !important;
    object-fit: cover !important;
    display: block !important;
}

.modern-movie-content-col {
    padding: 24px !important;
}

.modern-movie-title {
    color: #fff !important;
    font-weight: 800 !important;
    margin: 0 !important;
    font-size: 22px !important;
}

.modern-showtime-title {
    color: #f1f5f9 !important;
    font-weight: 700 !important;
    margin: 24px 0 14px !important;
    font-size: 15px !important;
    text-transform: uppercase !important;
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
}

/* Thẻ suất chiếu kiểu mới */
.modern-showtime-card {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 6px !important;
    padding: 10px 10px !important;
    border-radius: 14px !important;
    background: rgba(255, 255, 255, 0.02) !important;
    border: 1px solid rgba(255, 255, 255, 0.06) !important;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

.modern-showtime-date {
    font-size: 11px !important;
    font-weight: 700 !important;
    color: #fbbf24 !important;
    display: flex !important;
    align-items: center !important;
    gap: 4px !important;
    background: rgba(251, 191, 36, 0.1) !important;
    padding: 2px 10px !important;
    border-radius: 20px !important;
    white-space: nowrap !important;
    margin-bottom: 2px !important;
}

.modern-showtime-date i {
    font-size: 10px !important;
}

.modern-showtime-card:hover .modern-showtime-date {
    color: #fff !important;
    background: rgba(251, 191, 36, 0.25) !important;
}

.modern-showtime-header {
    display: flex !important;
    align-items: baseline !important;
    gap: 4px !important;
}

.modern-showtime-hour {
    font-size: 18px !important;
    font-weight: 800 !important;
    color: #ffffff !important;
}

.modern-showtime-end {
    font-size: 11px !important;
    color: #94a3b8 !important;
}

.modern-room-name {
    font-size: 11px !important;
    font-weight: 600 !important;
    color: #94a3b8 !important;
    display: flex !important;
    align-items: center !important;
    gap: 4px !important;
}

.modern-sell-btn {
    width: 100% !important;
    text-align: center !important;
    background: rgba(255, 255, 255, 0.05) !important;
    color: #f1f5f9 !important;
    padding: 6px 10px !important;
    border-radius: 8px !important;
    font-size: 11px !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 4px !important;
    transition: all 0.2s ease !important;
}

/* Hover effect */
.modern-showtime-card:hover {
    transform: translateY(-4px) !important;
    background: rgba(239, 68, 68, 0.15) !important;
    border-color: #ef4444 !important;
    box-shadow: 0 8px 20px rgba(239, 68, 68, 0.2) !important;
}

.modern-showtime-card:hover .modern-showtime-hour {
    color: #ef4444 !important;
}

.modern-showtime-card:hover .modern-room-name {
    color: #f1f5f9 !important;
}

.modern-showtime-card:hover .modern-sell-btn {
    background: #ef4444 !important;
    color: #ffffff !important;
}

.modern-empty-box {
    text-align: center !important;
    padding: 80px 20px !important;
    background: #131c2e !important;
    border-radius: 20px !important;
    border: 1px dashed rgba(255, 255, 255, 0.1) !important;
}

.modern-empty-icon-wrapper {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 80px !important;
    height: 80px !important;
    border-radius: 50% !important;
    background: rgba(255, 255, 255, 0.03) !important;
    color: #475569 !important;
    font-size: 32px !important;
}

@media (max-width: 768px) {
    .modern-movie-row {
        flex-direction: column !important;
    }
    .modern-movie-poster-col {
        width: 100% !important;
        max-width: 100% !important;
    }
    .modern-movie-poster {
        height: 200px !important;
        min-height: auto !important;
    }
    .modern-movie-content-col {
        padding: 16px !important;
    }
}
</style>
@endpush
