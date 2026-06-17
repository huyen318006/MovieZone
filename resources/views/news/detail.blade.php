@extends('layout.app')

@section('content')
<main class="movie-list-page" style="padding-top: 110px;">
    {{-- Decorative Glows --}}
    <div class="movie-list-hero__glow" style="top: 150px; right: 10%;"></div>

    <div class="container py-5">
        {{-- Breadcrumb --}}
        <nav aria-label="breadcrumb" class="mb-4" data-aos="fade-up">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-soft" style="text-decoration: none;">Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="{{ route('news') }}" class="text-soft" style="text-decoration: none;">Tin tức</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">{{ Str::limit($article->title, 30) }}</li>
            </ol>
        </nav>

        <div class="row g-5">
            {{-- Left column: Article detail --}}
            <div class="col-lg-8" data-aos="fade-up">
                <article>
                    {{-- Category & Date --}}
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="badge" style="background-color: #2ec4b6 !important; font-size: 0.75rem; letter-spacing: 0.5px; color: #ffffff !important; font-weight: bold;">
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
                        <span class="text-muted" style="font-size: 0.9rem;">
                            <i class="bi bi-calendar3 me-2"></i>{{ \Carbon\Carbon::parse($article->published_at)->format('d/m/Y') }}
                        </span>
                    </div>

                    {{-- Title --}}
                    <h1 class="text-white fw-bold mb-4 display-5" style="line-height: 1.3;">
                        {{ $article->title }}
                    </h1>

                    {{-- Big Thumbnail --}}
                    @if($article->thumbnail_url)
                        <div class="mb-5 overflow-hidden" style="border-radius: var(--radius); max-height: 450px;">
                            <img src="{{ asset($article->thumbnail_url) }}" class="w-100 h-100 object-fit-cover" alt="{{ $article->title }}" style="box-shadow: 0 15px 35px rgba(0,0,0,0.3);">
                        </div>
                    @endif

                    {{-- Summary Box --}}
                    @if($article->summary)
                        <div class="p-4 mb-4 border-start border-4 text-soft fs-5 style-summary" style="background: var(--card); border-color: var(--primary) !important; border-radius: 0 var(--radius) var(--radius) 0; font-style: italic; line-height: 1.6;">
                            {{ $article->summary }}
                        </div>
                    @endif

                    {{-- Content --}}
                    <div class="text-soft fs-5 article-body" style="line-height: 1.8;">
                        {!! $article->content !!}
                    </div>
                </article>
            </div>

            {{-- Right column: Sidebar --}}
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
                <aside class="p-4" style="background: var(--card); border-radius: var(--radius); border: 1px solid rgba(255, 255, 255, 0.05); position: sticky; top: 110px; box-shadow: 0 15px 40px rgba(0,0,0,0.15);">
                    <h3 class="text-white fw-bold mb-4 pb-2 border-bottom border-secondary border-opacity-25" style="font-size: 1.4rem;">
                        <i class="bi bi-fire me-2 text-warning"></i>Bài viết khác
                    </h3>

                    @if($otherArticles->isEmpty())
                        <p class="text-muted">Không có bài viết nào khác.</p>
                    @else
                        <div class="d-flex flex-column gap-4">
                            @foreach($otherArticles as $other)
                                <a href="{{ route('news.detail', $other->slug) }}" class="d-flex gap-3 text-decoration-none group-article" style="transition: all 0.3s ease;">
                                    {{-- Mini Thumbnail --}}
                                    <div class="flex-shrink-0 overflow-hidden" style="width: 90px; height: 90px; border-radius: 12px;">
                                        <img src="{{ $other->thumbnail_url ? asset($other->thumbnail_url) : asset('assets/news/batman.jpg') }}" class="w-100 h-100 object-fit-cover img-thumbnail-mini" alt="{{ $other->title }}" style="transition: transform 0.3s ease;">
                                    </div>
                                    
                                    {{-- Info --}}
                                    <div class="d-flex flex-column justify-content-center">
                                        <span class="text-muted mb-1" style="font-size: 0.75rem;">
                                            <i class="bi bi-calendar3 me-1"></i>{{ \Carbon\Carbon::parse($other->published_at)->format('d/m/Y') }}
                                        </span>
                                        <h5 class="text-white fw-semibold title-mini mb-0" style="font-size: 0.95rem; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; transition: color 0.2s ease;">
                                            {{ $other->title }}
                                        </h5>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </div>
</main>

<style>
    .article-body h2, .article-body h3, .article-body h4 {
        color: #fff;
        font-weight: 700;
        margin-top: 2rem;
        margin-bottom: 1rem;
    }
    .article-body p {
        margin-bottom: 1.5rem;
    }
    .article-body strong {
        color: #fff;
    }
    
    .group-article:hover .title-mini {
        color: var(--primary) !important;
    }
    .group-article:hover .img-thumbnail-mini {
        transform: scale(1.1);
    }
    
    .breadcrumb-item a:hover {
        color: var(--primary) !important;
    }
    
    .breadcrumb-item::before {
        color: var(--text-soft) !important;
    }
    .movie-list-page {
    padding-bottom: 0px;
}
</style>
@endsection
