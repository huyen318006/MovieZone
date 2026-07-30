@extends('layout.admin')

@section('title', 'Quản lý Bài viết')

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle-fill me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="col-12">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h3 class="mb-1">Quản lý Bài viết</h3>
            <p class="text-muted mb-0">Quản lý tin tức, sự kiện và các nội dung truyền thông của rạp.</p>
        </div>
        <div>
            <a href="{{ \App\Helpers\TabAuthHelper::route('admin.articles.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Thêm Bài viết
            </a>
        </div>
</div>

<div class="col-12 mt-3">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="fw-semibold">Danh sách Bài viết</div>
            <form method="GET" action="{{ \App\Helpers\TabAuthHelper::route('admin.articles.index') }}" class="d-flex gap-2 align-items-center flex-wrap">
                <input type="hidden" name="tab_token" value="{{ \App\Helpers\TabAuthHelper::gettoken() }}">
                
                <input type="text" name="keyword" class="form-control form-control-sm" style="width: 200px;" placeholder="Tìm kiếm..." value="{{ request('keyword') }}">
                
                <select name="category" class="form-select form-select-sm" style="width: 160px;" onchange="this.form.submit()">
                    <option value="">Tất cả danh mục</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
                
                <select name="status" class="form-select form-select-sm" style="width: 140px;" onchange="this.form.submit()">
                    <option value="">Tất cả trạng thái</option>
                    <option value="PUBLISHED" {{ request('status') == 'PUBLISHED' ? 'selected' : '' }}>Đã xuất bản</option>
                    <option value="DRAFT" {{ request('status') == 'DRAFT' ? 'selected' : '' }}>Bản nháp</option>
                    <option value="HIDDEN" {{ request('status') == 'HIDDEN' ? 'selected' : '' }}>Ẩn</option>
                </select>
                
                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-search"></i></button>
                <a href="{{ \App\Helpers\TabAuthHelper::route('admin.articles.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-clockwise"></i></a>
            </form>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th style="width: 80px;">Ảnh</th>
                            <th>Tiêu đề</th>
                            <th style="width: 120px;">Danh mục</th>
                            <th style="width: 120px;">Trạng thái</th>
                            <th style="width: 160px;">Ngày xuất bản</th>
                            <th style="width: 180px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($articles as $article)
                            <tr>
                                <td>{{ $article->id }}</td>
                                <td>
                                    @if($article->thumbnail_url)
                                        <img src="{{ asset($article->thumbnail_url) }}" alt="{{ $article->title }}" class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                    @else
                                        <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <i class="bi bi-newspaper fs-4"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold text-truncate" style="max-width: 300px;">{{ $article->title }}</div>
                                    <small class="text-muted">Slug: {{ $article->slug }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-info text-dark">{{ $article->category }}</span>
                                </td>
                                <td>
                                    @if($article->status === 'PUBLISHED')
                                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Đã xuất bản</span>
                                    @elseif($article->status === 'DRAFT')
                                        <span class="badge bg-warning text-dark"><i class="bi bi-pencil"></i> Bản nháp</span>
                                    @elseif($article->status === 'HIDDEN')
                                        <span class="badge bg-secondary"><i class="bi bi-eye-slash"></i> Ẩn</span>
                                    @endif
                                </td>
                                <td>
                                    @if($article->published_at)
                                        {{ \Carbon\Carbon::parse($article->published_at)->format('H:i d/m/Y') }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ \App\Helpers\TabAuthHelper::route('admin.articles.edit', $article->id) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-pencil"></i> Sửa
                                        </a>
                                        <form method="POST" action="{{ \App\Helpers\TabAuthHelper::route('admin.articles.destroy', $article->id) }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa bài viết \'{{ $article->title }}\' không?');" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                <i class="bi bi-trash"></i> Xóa
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-newspaper fs-1 d-block mb-2"></i>
                                    Không tìm thấy bài viết nào.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-end mt-3">
                {{ $articles->links() }}
            </div>
    </div>

@endsection
