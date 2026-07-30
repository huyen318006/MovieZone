@extends('layout.staff')

@section('title', $article->title)
@section('page-title', 'Chi tiết Bài viết')

@push('styles')
.article-detail-wrapper {
    max-width: 960px;
    margin: 0 auto;
}

.article-detail-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: var(--staff-surface);
    color: var(--staff-text-muted);
    border: 1px solid var(--staff-border);
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
    margin-bottom: 20px;
}

.article-detail-back:hover {
    background: var(--staff-surface-hover);
    color: var(--staff-text);
    border-color: var(--staff-primary);
}

.article-detail-card {
    background: var(--staff-surface);
    border: 1px solid var(--staff-border);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(2, 6, 23, 0.2);
}

.article-detail-img {
    width: 100%;
    max-height: 420px;
    object-fit: cover;
}

.article-detail-body {
    padding: 32px;
}

@media (min-width: 768px) {
    .article-detail-body {
        padding: 48px 56px;
    }
}

.article-detail-meta-top {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}

.article-detail-category {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    background: rgba(139, 92, 246, 0.12);
    color: #c4b5fd;
    font-size: 12px;
    font-weight: 600;
}

.article-detail-status {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.article-detail-title {
    font-size: 28px;
    font-weight: 800;
    color: var(--staff-text);
    line-height: 1.3;
    margin: 0 0 16px;
}

@media (min-width: 768px) {
    .article-detail-title {
        font-size: 32px;
    }
}

.article-detail-info {
    display: flex;
    align-items: center;
    gap: 20px;
    color: var(--staff-text-muted);
    font-size: 13px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.article-detail-info span {
    display: flex;
    align-items: center;
    gap: 6px;
}

.article-detail-info i {
    color: var(--staff-primary);
}

.article-detail-summary {
    padding: 18px 22px;
    margin-bottom: 24px;
    border-radius: 12px;
    background: rgba(139, 92, 246, 0.06);
    border-left: 4px solid var(--staff-primary);
}

.article-detail-summary p {
    margin: 0;
    color: var(--staff-text-muted);
    font-style: italic;
    font-size: 15px;
    line-height: 1.7;
}

.article-detail-divider {
    border: none;
    border-top: 1px solid var(--staff-border);
    margin: 0 0 24px;
}

.article-detail-content {
    font-size: 15px;
    line-height: 1.9;
    color: var(--staff-text);
}

.article-detail-content p {
    margin-bottom: 16px;
}
@endpush

@section('content')
<div class="article-detail-wrapper">
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
        <a href="{{ \App\Helpers\TabAuthHelper::route('staff.articles.index') }}" class="article-detail-back" style="margin-bottom: 0;">
            <i class="bi bi-arrow-left"></i> Quay lại danh sách
        </a>
        <a href="{{ \App\Helpers\TabAuthHelper::route('staff.articles.edit', $article->id) }}"
           style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px;
                  background: var(--staff-primary); color: #fff; border: none; border-radius: 8px;
                  font-size: 13px; font-weight: 600; text-decoration: none; transition: all 0.2s;">
            <i class="bi bi-pencil-square"></i> Chỉnh sửa
        </a>
    </div>

    <div class="article-detail-card">
        @if($article->thumbnail_url)
            <img src="{{ asset($article->thumbnail_url) }}" alt="{{ $article->title }}" class="article-detail-img">
        @endif

        <div class="article-detail-body">
            {{-- Meta: Category & Status --}}
            <div class="article-detail-meta-top">
                <span class="article-detail-category">{{ $article->category }}</span>
                @if($article->status === 'PUBLISHED')
                    <span class="article-detail-status" style="background: rgba(16,185,129,0.12); color: #6ee7b7;">Đã xuất bản</span>
                @elseif($article->status === 'DRAFT')
                    <span class="article-detail-status" style="background: rgba(245,158,11,0.12); color: #fbbf24;">Bản nháp</span>
                @else
                    <span class="article-detail-status" style="background: rgba(148,163,184,0.12); color: #94a3b8;">Ẩn</span>
                @endif
            </div>

            {{-- Title --}}
            <h1 class="article-detail-title">{{ $article->title }}</h1>

            {{-- Info: Date --}}
            <div class="article-detail-info">
                <span>
                    <i class="bi bi-calendar3"></i>
                    @if($article->published_at)
                        {{ \Carbon\Carbon::parse($article->published_at)->format('H:i d/m/Y') }}
                    @else
                        Chưa xuất bản
                    @endif
                </span>
                <span>
                    <i class="bi bi-clock"></i>
                    Cập nhật: {{ $article->updated_at->format('d/m/Y') }}
                </span>
            </div>

            {{-- Summary --}}
            @if($article->summary)
                <div class="article-detail-summary">
                    <p>{{ $article->summary }}</p>
                </div>
            @endif

            {{-- Divider --}}
            <hr class="article-detail-divider">

            {{-- Content --}}
            <div class="article-detail-content">
                {!! nl2br(e($article->content)) !!}
            </div>
        </div>
    </div>
</div>
@endsection
