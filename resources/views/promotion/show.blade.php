@extends('layout.app')

@section('content')
@php
    $timeLabel = $promotion->start_date->isFuture() ? 'Sắp diễn ra' : 'Đang diễn ra';
    $badgeClass = $timeLabel === 'Đang diễn ra' ? 'text-bg-success' : 'text-bg-primary';
@endphp

<style>
.promo-detail-shell { padding-block: 48px; }
.promo-detail-card { border-radius: 30px; overflow: hidden; }
.promo-detail-banner {
    min-height: 380px;
    background: linear-gradient(135deg, #111827, #4f46e5 55%, #be123c);
    position: relative;
}
.promo-detail-banner img { width: 100%; height: 100%; max-height: 480px; object-fit: cover; display: block; }
.promo-detail-placeholder { min-height: 380px; color: white; display: grid; place-items: center; text-align: center; }
.promo-info-box { border-radius: 18px; background: #f8fafc; }
</style>

<section class="container promo-detail-shell">
    <a href="{{ route('promotions') }}" class="btn btn-outline-secondary mb-4">
        <i class="fa-solid fa-arrow-left me-1"></i> Quay lại khuyến mãi
    </a>

    <article class="card promo-detail-card border-0 shadow-sm">
        <div class="promo-detail-banner">
            @if ($promotion->banner_url)
                <img src="{{ asset('storage/' . $promotion->banner_url) }}" alt="{{ $promotion->title }}">
            @else
                <div class="promo-detail-placeholder">
                    <div>
                        <i class="fa-solid fa-tags fa-4x mb-3"></i>
                        <div class="fs-3 fw-bold">MovieZone Promotion</div>
                    </div>
                </div>
            @endif
        </div>

        <div class="card-body p-4 p-lg-5">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <span class="badge {{ $badgeClass }}">{{ $timeLabel }}</span>
                <span class="badge text-bg-dark">Khuyến mãi MovieZone</span>
            </div>

            <h1 class="display-6 fw-bold mb-4">{{ $promotion->title }}</h1>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="promo-info-box p-3 h-100">
                        <div class="text-muted small">Ngày bắt đầu</div>
                        <div class="fw-bold">{{ $promotion->start_date->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="promo-info-box p-3 h-100">
                        <div class="text-muted small">Ngày kết thúc</div>
                        <div class="fw-bold">{{ $promotion->end_date->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <h2 class="h4 fw-bold mb-3">Nội dung chương trình</h2>
                <div class="fs-5 lh-lg text-muted">
                    {!! nl2br(e($promotion->description ?: 'Thông tin chi tiết đang được cập nhật.')) !!}
                </div>
            </div>

            <div class="alert alert-info border-0 shadow-sm mb-4">
                <i class="fa-solid fa-circle-info me-1"></i>
                Khuyến mãi dùng để cung cấp thông tin ưu đãi. Nếu chương trình có mã giảm giá, khách hàng áp dụng voucher ở bước đặt vé.
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('movies') }}" class="btn btn-primary btn-lg">
                    Xem phim đang chiếu
                </a>
                <a href="{{ route('promotions') }}" class="btn btn-outline-secondary btn-lg">
                    Xem khuyến mãi khác
                </a>
            </div>
        </div>
    </article>
</section>
@endsection