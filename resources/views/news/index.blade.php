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

        {{-- Articles Grid --}}
        @if($articles->isEmpty())
            <div class="text-center py-5" data-aos="fade-up">
                <div class="display-1 text-muted mb-4"><i class="bi bi-newspaper"></i></div>
                <h3 class="text-white">Chưa có bài viết nào</h3>
                <p class="text-muted">Vui lòng quay lại sau để cập nhật tin tức mới nhất.</p>
            </div>
        @else
            <div class="row g-4 mb-5">
                @foreach($articles as $article)
                    <div class="col-md-6 col-lg-4 d-flex" data-aos="fade-up">
                        <article class="card border-0 flex-fill overflow-hidden" style="background: var(--card); border-radius: var(--radius); transition: all 0.35s ease; box-shadow: 0 10px 30px rgba(0,0,0,0.2);" onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 12px 35px rgba(126, 166, 255, 0.2)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 30px rgba(0,0,0,0.2)';">
                            
                            {{-- Image Container --}}
                            <div class="position-relative" style="height: 220px; overflow: hidden;">
                                <img src="{{ $article->thumbnail_url ? asset($article->thumbnail_url) : asset('assets/news/batman.jpg') }}" class="w-100 h-100 object-fit-cover" alt="{{ $article->title }}">
                                
                                {{-- Category Badge --}}
                                <span class="position-absolute top-0 end-0 m-3 badge bg-opacity-75" style="background-color: #2ec4b6 !important; font-size: 0.75rem; letter-spacing: 0.5px; color: #ffffff !important; font-weight: bold;">
                                    @if($article->category === 'NEWS')
                                        TIN TỨC
                                    @elseif($article->category === 'PROMOTION')
                                        KHUYẾN MÃI
                                    @elseif($article->category === 'EVENT')
                                        SỰ KIỆN
                                    @else
                                        {{ $article->category }}
                                    @endif
                                </span>
                            </div>

                            {{-- Card Body --}}
                            <div class="card-body p-4 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="text-muted mb-2" style="font-size: 0.85rem;">
                                        <i class="bi bi-calendar3 me-2"></i>{{ \Carbon\Carbon::parse($article->published_at)->format('d/m/Y') }}
                                    </div>
                                    <h4 class="card-title text-white fw-bold mb-3" style="line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; font-size: 1.25rem;">
                                        {{ $article->title }}
                                    </h4>
                                    <p class="card-text text-soft mb-4" style="font-size: 0.95rem; line-height: 1.6; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; color:aliceblue !important">
                                        {{ $article->summary }}
                                    </p>
                                </div>

                                <a href="{{ \App\Helpers\TabAuthHelper::route('news.detail', $article->slug) }}" class="fw-bold d-inline-flex align-items-center mt-auto" style="color: var(--primary); text-decoration: none; transition: 0.3s;" onmouseover="this.style.color='#5f8ff7'; this.querySelector('i').style.transform='translateX(5px)';" onmouseout="this.style.color='var(--primary)'; this.querySelector('i').style.transform='translateX(0)';">
                                    Đọc tiếp <i class="bi bi-arrow-right ms-2" style="transition: transform 0.2s ease;"></i>
                                </a>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="d-flex justify-content-center mt-5" id="news-pagination">
                {{ $articles->links() }}
            </div>
        @endif
    </div>
</main>
<style>
#news-pagination p.text-muted, 
#news-pagination p.small {
    color: #a0aec0 !important; 
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

