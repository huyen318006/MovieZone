@extends('layout.app')

@section('content')
<section class="container py-5">
    <div class="mb-4">
        <h1 class="fw-bold">Khuyến mãi</h1>
        <p class="text-muted mb-0">Các chương trình ưu đãi đang được MovieZone công bố.</p>
    </div>

    <div class="row g-4">
        @forelse ($promotions as $promotion)
            <div class="col-md-6 col-lg-4">
                <a href="{{ route('promotion.show', $promotion) }}" class="text-decoration-none text-body">
                    <article class="card h-100 shadow-sm border-0">
                        @if ($promotion->banner_url)
                            <img src="{{ asset('storage/' . $promotion->banner_url) }}" class="card-img-top" alt="{{ $promotion->title }}" style="height: 210px; object-fit: cover;">
                        @endif
                        <div class="card-body">
                            <h2 class="h5 fw-bold">{{ $promotion->title }}</h2>
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