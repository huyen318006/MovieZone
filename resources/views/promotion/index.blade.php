@extends('layout.app')

@section('content')
<style>
.promo-hero {
    position: relative;
    overflow: hidden;
    border-radius: 28px;
    padding: 54px 36px;
    margin-bottom: 28px;
    color: #fff;
    background:
        radial-gradient(circle at top left, rgba(250,204,21,.38), transparent 34%),
        radial-gradient(circle at bottom right, rgba(239,68,68,.28), transparent 30%),
        linear-gradient(135deg, #111827 0%, #312e81 52%, #7f1d1d 100%);
    box-shadow: 0 22px 70px rgba(15, 23, 42, .24);
}
.promo-hero h1 { font-size: clamp(2.3rem, 5vw, 4.5rem); letter-spacing: -.05em; }
.promo-filter-card { border-radius: 22px; background: #111827; color: #f8fafc; }
.promo-filter-card .form-label { color: #e5e7eb; }
.promo-card {
    border-radius: 24px;
    overflow: hidden;
    color: #f8fafc;
    background: linear-gradient(180deg, #172033 0%, #111827 100%);
    border: 1px solid rgba(148, 163, 184, .18) !important;
    transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
}
.promo-card:hover {
    transform: translateY(-6px);
    border-color: rgba(96, 165, 250, .45) !important;
    box-shadow: 0 20px 48px rgba(15, 23, 42, .36) !important;
}
.promo-card h2 { color: #fff; }
.promo-card .text-muted { color: #cbd5e1 !important; }
.promo-image-wrap {
    height: 220px;
    background:
        radial-gradient(circle at 20% 20%, rgba(251,191,36,.32), transparent 28%),
        linear-gradient(135deg, #1e1b4b, #4f46e5 55%, #111827);
}
.promo-image-wrap img { width: 100%; height: 100%; object-fit: cover; display: block; }
.promo-placeholder { height: 100%; color: white; display: grid; place-items: center; text-align: center; }
.promo-placeholder i { color: #facc15; }
</style>

<section class="container py-5">
    <div class="promo-hero">
        <span class="badge text-bg-warning mb-3">MovieZone Offers</span>
        <h1 class="fw-bold mb-3">Khuyến mãi</h1>
        <p class="fs-5 mb-0 opacity-75">Cập nhật ưu đãi đang diễn ra và sắp ra mắt trước khi đặt vé.</p>
    </div>

    <form method="GET" action="{{ \App\Helpers\TabAuthHelper::route('promotions') }}" class="card promo-filter-card border-0 shadow-sm mb-4">
        <div class="card-body row g-3 align-items-end">
            {{-- Giữ tab_token khi submit GET form --}}
            @if(\App\Helpers\TabAuthHelper::gettoken())
                <input type="hidden" name="tab_token" value="{{ \App\Helpers\TabAuthHelper::gettoken() }}">
            @endif
            <div class="col-md-6">
                <label class="form-label fw-semibold">Tìm kiếm khuyến mãi</label>
                <input type="search" name="search" value="{{ $search }}" class="form-control form-control-lg" placeholder="Nhập tiêu đề hoặc nội dung...">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Trạng thái</label>
                <select name="filter" class="form-select form-select-lg">
                    <option value="available" @selected($filter === 'available')>Đang & sắp diễn ra</option>
                    <option value="ongoing" @selected($filter === 'ongoing')>Đang diễn ra</option>
                    <option value="upcoming" @selected($filter === 'upcoming')>Sắp diễn ra</option>
                    <option value="ended" @selected($filter === 'ended')>Đã kết thúc</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <a href="{{ \App\Helpers\TabAuthHelper::route('promotions') }}" class="btn btn-outline-secondary btn-lg w-100">Reset</a>
                <button type="submit" class="btn btn-primary btn-lg w-100">Lọc</button>
            </div>
        </div>
    </form>

    <div class="row g-4">
        @forelse ($promotions as $promotion)
            @php
                $timeLabel = $promotion->start_date->isFuture()
                    ? 'Sắp diễn ra'
                    : ($promotion->end_date->isPast() ? 'Đã kết thúc' : 'Đang diễn ra');
                $badgeClass = $timeLabel === 'Đang diễn ra'
                    ? 'text-bg-success'
                    : ($timeLabel === 'Sắp diễn ra' ? 'text-bg-primary' : 'text-bg-secondary');
            @endphp
            <div class="col-md-6 col-lg-4">
            <a href="{{ \App\Helpers\TabAuthHelper::route('promotion.show', $promotion) }}" class="text-decoration-none text-body">
                    <article class="card promo-card h-100 shadow-sm border-0">
                        <div class="promo-image-wrap">
                            @if ($promotion->banner_url && \Illuminate\Support\Facades\Storage::disk('public')->exists($promotion->banner_url))
                                <img src="{{ asset('storage/' . $promotion->banner_url) }}" alt="{{ $promotion->title }}">
                            @else
                                <div class="promo-placeholder">
                                    <div>
                                        <i class="fa-solid fa-tags fa-3x mb-3"></i>
                                        <div class="fw-bold">MovieZone Promotion</div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="card-body p-4">
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                <h2 class="h5 fw-bold mb-0">{{ $promotion->title }}</h2>
                                <span class="badge {{ $badgeClass }}">{{ $timeLabel }}</span>
                            </div>
                            <p class="text-muted small mb-3">
                                <i class="fa-regular fa-calendar me-1"></i>
                                {{ $promotion->start_date->format('d/m/Y') }} - {{ $promotion->end_date->format('d/m/Y') }}
                            </p>
                            <p class="mb-0 text-muted">{{ Str::limit(strip_tags($promotion->description), 125) }}</p>
                        </div>
                    </article>
                </a>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm text-center py-5">
                    <div class="card-body">
                        <i class="fa-solid fa-ticket fa-3x text-muted mb-3"></i>
                        <h2 class="h4 fw-bold">Hiện chưa có chương trình khuyến mãi.</h2>
                        <p class="text-muted mb-0">Bạn có thể quay lại sau hoặc xem phim đang chiếu tại MovieZone.</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $promotions->links() }}
    </div>
</section>
@endsection