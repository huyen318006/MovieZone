@extends('layout.admin')

@section('title', 'Quản lý Combo bắp nước')

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
            <h3 class="mb-1">Quản lý Combo bắp nước</h3>
            <p class="text-muted mb-0">Quản lý gói sản phẩm (combo đôi, combo gia đình...) bán kèm vé.</p>
        </div>
        <div>
            <a href="{{ route('admin.combos.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Thêm Combo mới
            </a>
        </div>
    </div>
</div>

<div class="col-12 mt-3">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="fw-semibold">Danh sách combo bắp nước</div>
            <form method="GET" action="{{ route('admin.combos.index') }}" class="d-flex gap-2 align-items-center">
                <input type="text" name="search" class="form-control form-control-sm" style="width: 200px;" placeholder="Tìm tên combo..." value="{{ request('search') }}">
                <select name="status" class="form-select form-select-sm" style="width: 150px;" onchange="this.form.submit()">
                    <option value="">Tất cả trạng thái</option>
                    <option value="ACTIVE" {{ request('status') == 'ACTIVE' ? 'selected' : '' }}>Hoạt động</option>
                    <option value="INACTIVE" {{ request('status') == 'INACTIVE' ? 'selected' : '' }}>Đã ẩn</option>
                </select>
                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-search"></i></button>
                <a href="{{ route('admin.combos.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-clockwise"></i></a>
            </form>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th style="width: 80px;">Hình ảnh</th>
                            <th>Tên combo</th>
                            <th>Thành phần sản phẩm</th>
                            <th>Giá bán</th>
                            <th>Mô tả</th>
                            <th>Trạng thái</th>
                            <th style="width: 180px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($combos as $combo)
                            <tr>
                                <td>{{ $combo->id }}</td>
                                <td>
                                    @if($combo->image_url)
                                        <img src="{{ Str::startsWith($combo->image_url, 'http') ? $combo->image_url : asset('storage/' . $combo->image_url) }}" alt="{{ $combo->name }}" class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;">
                                    @else
                                        <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <i class="bi bi-image fs-4"></i>
                                        </div>
                                    @endif
                                </td>
                                <td class="fw-semibold">{{ $combo->name }}</td>
                                <td>
                                    <ul class="list-unstyled mb-0 small">
                                        @foreach($combo->products as $prod)
                                            <li><i class="bi bi-check2 text-success me-1"></i>{{ $prod->name }} <strong class="text-primary">(x{{ $prod->pivot->quantity }})</strong></li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td class="text-danger fw-semibold">{{ number_format($combo->price, 0, ',', '.') }} đ</td>
                                <td><small class="text-muted">{{ Str::limit($combo->description, 50) }}</small></td>
                                <td>
                                    @if($combo->status === 'ACTIVE')
                                        <span class="badge text-bg-success">Hoạt động</span>
                                    @else
                                        <span class="badge text-bg-secondary">Đã ẩn</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.combos.edit', $combo->id) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-pencil"></i> Sửa
                                        </a>
                                        <form method="POST" action="{{ route('admin.combos.destroy', $combo->id) }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa combo này không?');" class="d-inline">
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
                                    Không tìm thấy combo nào.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex align-items-center justify-content-between mt-3">
                <div class="text-muted">Tổng số: {{ $combos->total() }}</div>
                {{ $combos->links() }}
            </div>
        </div>
    </div>
</div>

@endsection
