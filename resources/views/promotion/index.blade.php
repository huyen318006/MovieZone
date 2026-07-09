@extends('layout.app')

@section('content')
<section class="container py-5">
    <div class="mb-4">
        <h1 class="fw-bold">Khuyến mãi</h1>
        <p class="text-muted mb-0">Các chương trình ưu đãi đang được MovieZone công bố.</p>
    </div>

    <form method="GET" action="{{ route('promotions') }}" class="card border-0 shadow-sm mb-4">
        <div class="card-body row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Tìm kiếm khuyến mãi</label>
                <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Nhập tiêu đề hoặc nội dung...">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Trạng thái</label>
                <select name="filter" class="form-select">
                    <option value="available" @selected($filter === 'available')>Đang & sắp diễn ra</option>
                    <option value="ongoing" @selected($filter === 'ongoing')>Đang diễn ra</option>
                    <option value="upcoming" @selected($filter === 'upcoming')>Sắp diễn ra</option>
                    <option value="ended" @selected($filter === 'ended')>Đã kết thúc</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <a href="{{ route('promotions') }}" class="btn btn-outline-secondary w-100">Reset</a>
                <button type="submit" class="btn btn-primary w-100">Lọc</button>
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
                <a href="{{ route('promotion.show', $promotion) }}" class="text-decoration-none text-body">
                    <article class="card h-100 shadow-sm border-0">
                        @if ($promotion->banner_url)
                            <img src="{{ asset('storage/' . $promotion->banner_url) }}" class="card-img-top" alt="{{ $promotion->title }}" style="height: 210px; object-fit: cover;">
                        @endif
                        <div class="card-body">
                            <div class="d-flex align-items-start justify-content-between gap-2">
                                <h2 class="h5 fw-bold">{{ $promotion->title }}</h2>
                                <span class="badge {{ $badgeClass }}">{{ $timeLabel }}</span>
                            </div>
                            <p class="text-muted small mb-3">
                                {{ $promotion->start_date->format('d/m/Y') }} - {{ $promotion->end_date->format('d/m/Y') }}
                            </p>
                            <p class="mb-0">{{ Str::limit(strip_tags($promotion->description), 120) }}</p>
                        </div>
                    </article>
                </a>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info border-0 shadow-sm">
                    Hiện chưa có chương trình khuyến mãi.
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $promotions->links() }}
    </div>
</section>
@endsection