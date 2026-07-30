@extends('layout.staff')

@section('title', 'Bài viết')
@section('page-title', 'Bài viết')

@push('styles')
.articles-header-card {
    background: var(--staff-surface);
    border: 1px solid var(--staff-border);
    border-radius: 14px;
    padding: 20px 24px;
    margin-bottom: 20px;
}

.articles-stat {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 16px;
    background: var(--staff-bg);
    border-radius: 10px;
    border: 1px solid var(--staff-border);
}

.articles-stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, rgba(139,92,246,0.2), rgba(37,99,235,0.2));
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--staff-primary);
    font-size: 18px;
}

.articles-stat-value {
    font-size: 20px;
    font-weight: 800;
    color: var(--staff-text);
    line-height: 1;
}

.articles-stat-label {
    font-size: 11px;
    color: var(--staff-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.articles-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
}

.articles-card {
    background: var(--staff-surface);
    border: 1px solid var(--staff-border);
    border-radius: 16px;
    overflow: hidden;
    transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s;
    display: flex;
    flex-direction: column;
}

.articles-card:hover {
    transform: translateY(-3px);
    border-color: rgba(139, 92, 246, 0.3);
    box-shadow: 0 12px 40px rgba(2, 6, 23, 0.3);
}

.articles-card-img {
    height: 200px;
    object-fit: cover;
    width: 100%;
}

.articles-card-img-placeholder {
    height: 200px;
    background: linear-gradient(135deg, var(--staff-primary), #2563eb);
    display: flex;
    align-items: center;
    justify-content: center;
}

.articles-card-body {
    padding: 18px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.articles-card-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.articles-card-category {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    background: rgba(139, 92, 246, 0.12);
    color: #c4b5fd;
    font-size: 11px;
    font-weight: 600;
}

.articles-card-date {
    font-size: 11px;
    color: var(--staff-text-muted);
}

.articles-card-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--staff-text);
    margin: 0 0 8px;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.articles-card-summary {
    font-size: 13px;
    color: var(--staff-text-muted);
    line-height: 1.6;
    flex: 1;
    margin-bottom: 12px;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.articles-card-action {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 16px;
    border-radius: 8px;
    background: rgba(139, 92, 246, 0.08);
    color: #c4b5fd;
    border: 1px solid rgba(139, 92, 246, 0.15);
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    align-self: flex-start;
    transition: all 0.2s;
}

.articles-card-action:hover {
    background: var(--staff-primary);
    color: #fff;
    border-color: var(--staff-primary);
}

.articles-empty {
    grid-column: 1 / -1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    text-align: center;
    background: var(--staff-surface);
    border: 1px solid var(--staff-border);
    border-radius: 14px;
}

.articles-empty i {
    font-size: 48px;
    color: var(--staff-text-muted);
    opacity: 0.4;
    margin-bottom: 12px;
}

.articles-empty p {
    color: var(--staff-text-muted);
    font-size: 14px;
    margin: 0;
}

.articles-pagination {
    display: flex;
    justify-content: center;
    margin-top: 28px;
}

@media (max-width: 1100px) {
    .articles-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 640px) {
    .articles-grid { grid-template-columns: 1fr; }
}
@endpush

@section('content')
@php
    $totalArticles = $articles->total();
    $publishedCount = $articles->where('status', 'PUBLISHED')->count();
@endphp

{{-- Success Alert --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.25); color: #6ee7b7; border-radius: 10px;">
        <i class="bi bi-check-circle-fill me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="filter: invert(0.8);"></button>
    </div>
@endif

{{-- Header with search --}}
<div class="articles-header-card">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
        <div>
            <h5 style="font-size: 18px; font-weight: 700; color: var(--staff-text); margin: 0 0 4px;">
                <i class="bi bi-newspaper" style="color: var(--staff-primary); margin-right: 8px;"></i>
                Bài viết
            </h5>
            <p class="text-muted mb-0" style="font-size: 13px;">
                Danh sách các bài viết tin tức, sự kiện và khuyến mãi của rạp.
            </p>
        </div>
        <form method="GET" action="{{ \App\Helpers\TabAuthHelper::route('staff.articles.index') }}" class="d-flex gap-2 align-items-center flex-wrap">
            <input type="hidden" name="tab_token" value="{{ \App\Helpers\TabAuthHelper::gettoken() }}">
            
            <div style="position: relative;">
                <i class="bi bi-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--staff-text-muted); font-size: 13px;"></i>
                <input type="text" name="keyword" style="width: 180px; background: var(--staff-bg); border: 1px solid var(--staff-border); border-radius: 8px; color: var(--staff-text); padding: 8px 12px 8px 34px; font-size: 13px; outline: none;" placeholder="Tìm kiếm..." value="{{ request('keyword') }}">
            </div>
            
            <select name="category" style="width: 150px; background: var(--staff-bg); border: 1px solid var(--staff-border); border-radius: 8px; color: var(--staff-text); padding: 8px 12px; font-size: 13px; outline: none; cursor: pointer;" onchange="this.form.submit()">
                <option value="">Tất cả danh mục</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select>
            
            <button type="submit" style="background: var(--staff-primary); color: #fff; border: none; border-radius: 8px; padding: 8px 14px; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                <i class="bi bi-search"></i>
            </button>
            <a href="{{ \App\Helpers\TabAuthHelper::route('staff.articles.index') }}" style="background: var(--staff-bg); color: var(--staff-text-muted); border: 1px solid var(--staff-border); border-radius: 8px; padding: 8px 14px; font-size: 13px; text-decoration: none; display: flex; align-items: center; gap: 4px;">
                <i class="bi bi-arrow-clockwise"></i>
            </a>
        </form>
    </div>

    {{-- Stats --}}
    <div class="d-flex gap-3 flex-wrap">
        <div class="articles-stat">
            <div class="articles-stat-icon"><i class="bi bi-collection"></i></div>
            <div>
                <div class="articles-stat-value">{{ $totalArticles }}</div>
                <div class="articles-stat-label">Tổng bài viết</div>
            </div>
        </div>
        <div class="articles-stat">
            <div class="articles-stat-icon" style="background: linear-gradient(135deg, rgba(16,185,129,0.2), rgba(5,150,105,0.2)); color: #10b981;"><i class="bi bi-check-lg"></i></div>
            <div>
                <div class="articles-stat-value">{{ $publishedCount }}</div>
                <div class="articles-stat-label">Đã xuất bản</div>
            </div>
        </div>
    </div>
</div>

{{-- Articles Grid --}}
<div class="articles-grid">
    @forelse($articles as $article)
        <div class="articles-card">
            @if($article->thumbnail_url)
                <img src="{{ asset($article->thumbnail_url) }}" alt="{{ $article->title }}" class="articles-card-img">
            @else
                <div class="articles-card-img-placeholder">
                    <i class="bi bi-newspaper" style="font-size: 48px; color: rgba(255,255,255,0.3);"></i>
                </div>
            @endif
            <div class="articles-card-body">
                <div class="articles-card-meta">
                    <span class="articles-card-category">{{ $article->category }}</span>
                    <span class="articles-card-date">
                        @if($article->published_at)
                            <i class="bi bi-calendar3 me-1"></i>{{ \Carbon\Carbon::parse($article->published_at)->format('d/m/Y') }}
                        @endif
                    </span>
                </div>
                <h5 class="articles-card-title">{{ $article->title }}</h5>
                @if($article->summary)
                    <p class="articles-card-summary">{{ Str::limit($article->summary, 150) }}</p>
                @endif
<div style="display: flex; align-items: center; gap: 8px; margin-top: auto;">
                    <a href="{{ \App\Helpers\TabAuthHelper::route('staff.articles.show', $article->id) }}" class="articles-card-action" style="flex: 1;">
                        <i class="bi bi-eye"></i> Xem chi tiết
                    </a>
                    <a href="{{ \App\Helpers\TabAuthHelper::route('staff.articles.edit', $article->id) }}"
                       style="display: inline-flex; align-items: center; gap: 4px; padding: 7px 12px; border-radius: 8px;
                              background: rgba(139, 92, 246, 0.08); color: #c4b5fd; border: 1px solid rgba(139, 92, 246, 0.15);
                              font-size: 13px; font-weight: 600; text-decoration: none; transition: all 0.2s;">
                        <i class="bi bi-pencil-square"></i>
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="articles-empty">
            <i class="bi bi-newspaper"></i>
            <p>Không tìm thấy bài viết nào.</p>
        </div>
    @endforelse
</div>

{{-- Pagination --}}
<div class="articles-pagination">
    {{ $articles->links() }}
</div>

@endsection
