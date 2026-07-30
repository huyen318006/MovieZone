@extends('layout.staff')

@section('title', 'Bài viết')
@section('page-title', 'Bài viết')

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle-fill me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <p class="text-muted mb-0">Danh sách các bài viết tin tức, sự kiện và khuyến mãi của rạp.</p>
    </div>
    <form method="GET" action="{{ \App\Helpers\TabAuthHelper::route('staff.articles.index') }}" class="d-flex gap-2 align-items-center flex-wrap">
        <input type="hidden" name="tab_token" value="{{ \App\Helpers\TabAuthHelper::gettoken() }}">
        
        <input type="text" name="keyword" class="form-control form-control-sm" style="width: 180px; background: var(--staff-surface); border-color: var(--staff-border); color: var(--staff-text);" placeholder="Tìm kiếm..." value="{{ request('keyword') }}">
        
        <select name="category" class="form-select form-select-sm" style="width: 150px; background: var(--staff-surface); border-color: var(--staff-border); color: var(--staff-text);" onchange="this.form.submit()">
            <option value="">Tất cả danh mục</option>
            @foreach($categories as $cat)
                <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
            @endforeach
        </select>
        
        <button type="submit" class="btn btn-sm" style="background: var(--staff-primary); color: #fff; border: none;">
            <i class="bi bi-search"></i>
        </button>
        <a href="{{ \App\Helpers\TabAuthHelper::route('staff.articles.index') }}" class="btn btn-sm" style="background: var(--staff-surface-hover); color: var(--staff-text); border: 1px solid var(--staff-border);">
            <i class="bi bi-arrow-clockwise"></i>
        </a>
    </form>
</div>

<div class="row g-3">
    @forelse($articles as $article)
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0" style="background: var(--staff-surface); border-radius: 16px; overflow: hidden;">
                @if($article->thumbnail_url)
                    <img src="{{ asset($article->thumbnail_url) }}" alt="{{ $article->title }}" class="card-img-top" style="height: 200px; object-fit: cover;">
                @else
                    <div style="height: 200px; background: linear-gradient(135deg, var(--staff-primary), #2563eb); display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-newspaper" style="font-size: 48px; color: rgba(255,255,255,0.3);"></i>
                    </div>
                @endif
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge" style="background: rgba(139, 92, 246, 0.15); color: #c4b5fd; font-size: 11px;">
                            {{ $article->category }}
                        </span>
                        <small class="text-muted">
                            @if($article->published_at)
                                {{ \Carbon\Carbon::parse($article->published_at)->format('d/m/Y') }}
                            @endif
                        </small>
                    </div>
                    <h5 class="card-title" style="font-size: 16px; font-weight: 700; color: var(--staff-text);">
                        {{ $article->title }}
                    </h5>
                    @if($article->summary)
                        <p class="card-text text-muted" style="font-size: 13px; flex: 1;">
                            {{ Str::limit($article->summary, 120) }}
                        </p>
                    @endif
                    <a href="{{ \App\Helpers\TabAuthHelper::route('staff.articles.show', $article->id) }}" class="btn btn-sm mt-2" style="background: rgba(139, 92, 246, 0.1); color: #c4b5fd; border: 1px solid rgba(139, 92, 246, 0.2); align-self: flex-start;">
                        <i class="bi bi-eye me-1"></i> Xem chi tiết
                    </a>
                </div>
        </div>
    @empty
        <div class="col-12">
            <div class="text-center py-5 text-muted">
                <i class="bi bi-newspaper" style="font-size: 48px;"></i>
                <p class="mt-2">Không tìm thấy bài viết nào.</p>
            </div>
    @endforelse
</div>

<div class="d-flex justify-content-center mt-4">
    {{ $articles->links() }}
</div>

@endsection
