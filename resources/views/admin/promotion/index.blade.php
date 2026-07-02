@extends('layout.admin')

@section('title', 'Quản lý Chương trình Khuyến mãi')

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle-fill me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="col-12">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h3 class="mb-1">Quản lý Chương trình Khuyến mãi</h3>
            <p class="text-muted mb-0">Thiết lập sự kiện ưu đãi, chương trình truyền thông trên Website.</p>
        </div>
        <div>
            <a href="{{ route('admin.promotions.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Thêm khuyến mãi mới
            </a>
        </div>
    </div>
</div>

<div class="col-12 mt-3">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="fw-semibold">Danh sách chương trình khuyến mãi</div>
            <form method="GET" action="{{ route('admin.promotions.index') }}" class="d-flex gap-2 align-items-center">
                <input type="text" name="search" class="form-control form-control-sm" style="width: 200px;" placeholder="Tìm tiêu đề..." value="{{ request('search') }}">
                <select name="status" class="form-select form-select-sm" style="width: 150px;" onchange="this.form.submit()">
                    <option value="">Tất cả trạng thái</option>
                    <option value="ACTIVE" {{ request('status') == 'ACTIVE' ? 'selected' : '' }}>Hoạt động</option>
                    <option value="INACTIVE" {{ request('status') == 'INACTIVE' ? 'selected' : '' }}>Đã ẩn</option>
                    <option value="EXPIRED" {{ request('status') == 'EXPIRED' ? 'selected' : '' }}>Hết hạn</option>
                </select>
                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-search"></i></button>
                <a href="{{ route('admin.promotions.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-clockwise"></i></a>
            </form>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th style="width: 150px;">Banner</th>
                            <th>Tiêu đề</th>
                            <th>Mô tả ngắn</th>
                            <th>Thời gian bắt đầu</th>
                            <th>Thời gian kết thúc</th>
                            <th>Trạng thái</th>
                            <th style="width: 180px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($promotions as $promo)
                            <tr>
                                <td>{{ $promo->id }}</td>
                                <td>
                                    @if($promo->banner_url)
                                        <img src="{{ Str::startsWith($promo->banner_url, 'http') ? $promo->banner_url : asset('storage/' . $promo->banner_url) }}" alt="{{ $promo->title }}" class="img-thumbnail" style="width: 120px; height: 60px; object-fit: cover;">
                                    @else
                                        <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center" style="width: 120px; height: 60px;">
                                            <i class="bi bi-image fs-4"></i>
                                        </div>
                                    @endif
                                </td>
                                <td class="fw-semibold">{{ $promo->title }}</td>
                                <td><small class="text-muted">{{ Str::limit($promo->description, 60) }}</small></td>
                                <td>{{ \Carbon\Carbon::parse($promo->start_date)->format('d/m/Y H:i') }}</td>
                                <td>{{ \Carbon\Carbon::parse($promo->end_date)->format('d/m/Y H:i') }}</td>
                                <td>
                                    @if($promo->status === 'ACTIVE')
                                        <span class="badge text-bg-success">Hoạt động</span>
                                    @elseif($promo->status === 'INACTIVE')
                                        <span class="badge text-bg-secondary">Đã ẩn</span>
                                    @else
                                        <span class="badge text-bg-danger">Hết hạn</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.promotions.edit', $promo->id) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-pencil"></i> Sửa
                                        </a>
                                        <form method="POST" action="{{ route('admin.promotions.destroy', $promo->id) }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa chương trình khuyến mãi này không?');" class="d-inline">
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
                                <td colspan="8" class="text-center py-4 text-muted">
                                    Không tìm thấy chương trình khuyến mãi nào.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex align-items-center justify-content-between mt-3">
                <div class="text-muted">Tổng số: {{ $promotions->total() }}</div>
                {{ $promotions->links() }}
            </div>
        </div>
    </div>
</div>

@endsection
