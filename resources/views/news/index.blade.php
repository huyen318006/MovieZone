@extends('layout.app')

@section('content')
<main class="movie-list-page">
    {{-- Decorative Glows --}}
    <div class="movie-list-hero__glow" style="top: 80px; left: 10%;"></div>

    <div class="container py-5" styl="padding-bootom: -20px !important">
        {{-- Section Title --}}
        <div class="section-title mb-5" data-aos="fade-up">
            <div>
                <span class="badge mb-2">TIN TỨC & SỰ KIỆN</span>
                <h1 class="display-4 fw-bold text-white">Tin Tức Điện Ảnh</h1>
                <p class="text-muted fs-5" style="color:aliceblue !important">Cập nhật những thông tin nóng hổi nhất, các chương trình khuyến mãi và sự kiện đặc sắc từ MovieZone.</p>
            </div>
        </div>

        {{-- Articles Carousel --}}
        @if($articles->isEmpty())
            <div class="text-center py-5" data-aos="fade-up">
                <div class="display-1 text-muted mb-4"><i class="bi bi-newspaper"></i></div>
                <h3 class="text-white">Chưa có bài viết nào</h3>
                <p class="text-muted">Vui lòng quay lại sau để cập nhật tin tức mới nhất.</p>
            </div>
        @else
            <div id="newsCarousel" class="carousel slide mb-5" data-bs-ride="carousel" data-aos="fade-up">
                <div class="carousel-indicators">
                    @foreach($articles as $article)
                        <button type="button" data-bs-target="#newsCarousel" data-bs-slide-to="{{ $loop->index }}" class="{{ $loop->first ? 'active' : '' }}" aria-current="{{ $loop->first ? 'true' : 'false' }}" aria-label="Slide {{ $loop->iteration }}"></button>
                    @endforeach
                </div>
                <div class="carousel-inner rounded-4 overflow-hidden shadow-lg" style="background: rgba(255,255,255,0.05);">
                    @foreach($articles as $article)
                        <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                            <a href="{{ \App\Helpers\TabAuthHelper::route('news.detail', $article->slug) }}" class="d-block position-relative text-decoration-none">
                                <img src="{{ $article->thumbnail }}" class="d-block w-100" style="height: 520px; object-fit: cover;" alt="{{ $article->title }}">
                                <div class="carousel-caption d-none d-md-flex flex-column align-items-start justify-content-end p-4" style="background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(0,0,0,0.65) 100%); left: 0; right: 0; bottom: 0; top: 0;">
                                    <span class="badge bg-primary mb-2 text-uppercase" style="letter-spacing: 1px;">{{ $article->category === 'NEWS' ? 'Tin tức' : ($article->category === 'PROMOTION' ? 'Khuyến mãi' : ($article->category === 'EVENT' ? 'Sự kiện' : $article->category)) }}</span>
                                    <h2 class="text-white fw-bold mb-2" style="font-size: clamp(1.75rem, 2.1vw, 2.75rem); line-height: 1.05;">{{ $article->title }}</h2>
                                    <p class="text-white-50 mb-0" style="max-width: 55%;">{{ Str::limit($article->summary, 120) }}</p>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>

                @if($articles->count() > 1)
                    <button class="carousel-control-prev" type="button" data-bs-target="#newsCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#newsCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                @endif
            </div>
        @endif
    </div>
</main>
<style>
#newsCarousel .carousel-indicators [data-bs-target] {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background-color: rgba(255,255,255,0.5);
}
#newsCarousel .carousel-indicators .active {
    background-color: #ffffff;
}
#newsCarousel .carousel-control-prev-icon,
#newsCarousel .carousel-control-next-icon {
    filter: invert(1);
}
#newsCarousel .carousel-item img {
    min-height: 520px;
    cursor: pointer;
}
.movie-list-page {
    padding-bottom: 0px;
}

#news-pagination .fw-semibold {
    color: #3a86ff !important; 
}

#news-pagination .page-link {
    background-color: #121212 !important; 
    color: #4ea8de !important;        
    border-color: #0a3d62 !important;    
    border-radius: 6px !important;       
    margin: 0 3px !important;           
    transition: all 0.3s ease;
}

#news-pagination .page-link:hover {
    background-color: #0a3d62 !important; 
    color: #ffffff !important;          
    border-color: #4ea8de !important;    
}

#news-pagination .page-item.active .page-link {
    background-color: #0056b3 !important; 
    color: #ffffff !important;          
    border-color: #4ea8de !important;    
}

#news-pagination .page-item.disabled .page-link {
    background-color: #1a1a1a !important; 
    color: #4a4a4a !important;           
    border-color: #222222 !important;   
}
.movie-list-page {
    padding-bottom: 0px;
}
</style>
@endsection

