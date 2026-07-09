@extends('layout.app')

@section('content')
<section class="container py-5">
    <a href="{{ route('promotions') }}" class="btn btn-outline-secondary mb-4">
        ← Quay lại khuyến mãi
    </a>

    <article class="card border-0 shadow-sm overflow-hidden">
        @if ($promotion->banner_url)
            <img src="{{ asset('storage/' . $promotion->banner_url) }}" alt="{{ $promotion->title }}" style="width: 100%; max-height: 420px; object-fit: cover;">
        @endif
        <div class="card-body p-4 p-lg-5">
            <h1 class="fw-bold mb-3">{{ $promotion->title }}</h1>
            <div class="text-muted mb-4">
                Thời gian áp dụng: {{ $promotion->start_date->format('d/m/Y H:i') }} - {{ $promotion->end_date->format('d/m/Y H:i') }}
            </div>
            <div class="fs-5 lh-lg">
                {!! nl2br(e($promotion->description)) !!}
            </div>
        </div>
    </article>
</section>
@endsection