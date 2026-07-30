@extends('layout.staff')

@section('title', $article->title)
@section('page-title', 'Chi tiết Bài viết')

@section('content')

<div class="mb-3">
    <a href="{{ \App\Helpers\TabAuthHelper::route('staff.articles.index') }}" class="btn btn-sm" style="background: var(--staff-surface); color: var(--staff-text); border: 1px solid var(--staff-border);">
        <i class="bi bi-arrow-left me-1"></i> Quay lại danh sách
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card border-0" style="background: var(--staff-surface); border-radius: 20px; overflow: hidden;">
            @if($article->thumbnail_url)
                <img src="{{ asset($article->thumbnail_url) }}" alt="{{ $article->title }}" class="card-img-top" style="max-height: 400px; object-fit: cover;">
            @endif
            
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge" style="background: rgba(139, 92, 246, 0.15); color: #c4b5fd; font-size: 12px;">
                        {{ $article->category }}
                    </span>
                    @if($article->status === 'PUBLISHED')
                        <span class="badge bg-success" style="font-size: 11px;">Đã xuất bản</span>
                    @elseif($article->status === 'DRAFT')
                        <span class="badge bg-warning text-dark" style="font-size: 11px;">Bản nháp</span>
                    @else
                        <span class="badge bg-secondary" style="font-size: 11px;">Ẩn</span>
                    @endif
                </div>

                <h1 class="mb-3" style="font-size: 28px; font-weight: 800; color: var(--staff-text); line-height: 1.3;">
                    {{ $article->title }}
                </h1>

                <div class="d-flex align-items-center gap-3 text-muted mb-4" style="font-size: 13px;">
                    <span><i class="bi bi-calendar3 me-1"></i> 
                        @if($article->published_at)
                            {{ \Carbon\Carbon::parse($article->published_at)->format('H:i d/m/Y') }}
                        @else
                            Chưa xuất bản
                        @endif
                    </span>
                    <span><i class="bi bi-clock me-1"></i> Cập nhật: {{ $article->updated_at->format('d/m/Y') }}</span>
                </div>

                @if($article->summary)
                    <div class="p-3 mb-4 rounded" style="background: rgba(139, 92, 246, 0.08); border-left: 4px solid var(--staff-primary);">
                        <p class="mb-0" style="color: var(--staff-text-muted); font-style: italic; font-size: 15px;">
                            {{ $article->summary }}
                        </p>
                    </div>
                @endif

                <hr style="border-color: var(--staff-border);">

                <div style="font-size: 15px; line-height: 1.8; color: var(--staff-text);">
                    {!! nl2br(e($article->content)) !!}
                </div>
        </div>
</div>

@endsection
