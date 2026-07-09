@extends('layout.app')

@section('content')
@php
    $timeLabel = $promotion->start_date->isFuture() ? 'Sắp diễn ra' : 'Đang diễn ra';
    $badgeClass = $timeLabel === 'Đang diễn ra' ? 'text-bg-success' : 'text-bg-primary';
@endphp

<section class="container py-5">
    <a href="{{ route('promotions') }}" class="btn btn-outline-secondary mb-4">
        <i class="fa-solid fa-arrow-left me-1"></i> Quay lại khuyến mãi
    </a>

    <article class="card border-0 shadow-sm overflow-hidden">
        @if ($promotion->banner_url)
            <img src="{{ asset('storage/' . $promotion->banner_url) }}" alt="{{ $promotion->title }}" style="width: 100%; max-height: 420px; object-fit: cover;">
        @else
            <div class="d-flex align-items-center justify-content-center bg-dark text-white" style="height: 320px;">
                <div class="text-center">
                    <i class="fa-solid fa-tags fa-3x mb-3"></i>
                    <div class="fs-4 fw-bold">MovieZone Promotion</div>
                </div>
            </div>
        @endif

        <div class="card-body p-4 p-lg-5">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <span class="badge {{ $badgeClass }}">{{ $timeLabel }}</span>
                <span class="badge text-bg-dark">Khuyến mãi MovieZone</span>
            </div>

            <h1 class="fw-bold mb-3">{{ $promotion->title }}</h1>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="text-muted small">Ngày bắt đầu</div>
                        <div class="fw-bold">{{ $promotion->start_date->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="text-muted small">Ngày kết thúc</div>
                        <div class="fw-bold">{{ $promotion->end_date->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <h2 class="h4 fw-bold mb-3">Nội dung chương trình</h2>
                <div class="fs-5 lh-lg">
                    {!! nl2br(e($promotion->description ?: 'Thông tin chi tiết đang được cập nhật.')) !!}
                </div>
            </div>

            <div class="alert alert-info border-0 shadow-sm mb-4">
                <i class="fa-solid fa-circle-info me-1"></i>
                Khuyến mãi dùng để cung cấp thông tin ưu đãi. Nếu chương trình có mã giảm giá, khách hàng áp dụng voucher ở bước đặt vé.
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('movies') }}" class="btn btn-primary">
                    Xem phim đang chiếu
                </a>
                <a href="{{ route('promotions') }}" class="btn btn-outline-secondary">
                    Xem khuyến mãi khác
                </a>
            </div>
        </div>
    </article>
</section>
@endsection