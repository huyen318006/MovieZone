@extends('layout.admin')

@section('title', 'Quản lý Sản phẩm lẻ')

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
            <h3 class="mb-1">Quản lý Sản phẩm lẻ</h3>
            <p class="text-muted mb-0">Quản lý bắp, nước, snack bán kèm vé hoặc bán lẻ.</p>
        </div>
        <div>
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Thêm sản phẩm
            </a>
        </div>
    </div>
</div>

<div class="col-12 mt-3">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="fw-semibold">Danh sách sản phẩm lẻ</div>
            <form method="GET" action="{{ route('admin.products.index') }}" class="d-flex gap-2 align-items-center">
                <input type="text" name="search" class="form-control form-control-sm" style="width: 200px;" placeholder="Tìm tên sản phẩm..." value="{{ request('search') }}">
                <select name="status" class="form-select form-select-sm" style="width: 150px;" onchange="this.form.submit()">
                    <option value="">Tất cả trạng thái</option>
                    <option value="ACTIVE" {{ request('status') == 'ACTIVE' ? 'selected' : '' }}>Hoạt động</option>
                    <option value="INACTIVE" {{ request('status') == 'INACTIVE' ? 'selected' : '' }}>Đã ẩn</option>
                    <option value="OUT_OF_STOCK" {{ request('status') == 'OUT_OF_STOCK' ? 'selected' : '' }}>Hết hàng</option>
                </select>
                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-search"></i></button>
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-clockwise"></i></a>
            </form>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th style="width: 80px;">Hình ảnh</th>
                            <th>Tên sản phẩm</th>
                            <th>Giá bán</th>
                            <th>Mô tả</th>
                            <th>Trạng thái</th>
                            <th style="width: 180px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td>{{ $product->id }}</td>
                                <td>
                                    @if($product->image_url)
                                        <img src="{{ Str::startsWith($product->image_url, 'http') ? $product->image_url : asset('storage/' . $product->image_url) }}" alt="{{ $product->name }}" class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;">
                                    @else
                                        <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <i class="bi bi-image fs-4"></i>
                                        </div>
                                    @endif
                                </td>
                                <td class="fw-semibold">{{ $product->name }}</td>
                                <td class="text-danger fw-semibold">{{ number_format($product->price, 0, ',', '.') }} đ</td>
                                <td><small class="text-muted">{{ Str::limit($product->description, 50) }}</small></td>
                                <td>
                                    @if($product->status === 'ACTIVE')
                                        <span class="badge text-bg-success">Hoạt động</span>
                                    @elseif($product->status === 'INACTIVE')
                                        <span class="badge text-bg-secondary">Đã ẩn</span>
                                    @else
                                        <span class="badge text-bg-warning">Hết hàng</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-pencil"></i> Sửa
                                        </a>
                                        <form method="POST" action="{{ route('admin.products.destroy', $product->id) }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa sản phẩm lẻ này không?');" class="d-inline">
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
                                    Không tìm thấy sản phẩm nào.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex align-items-center justify-content-between mt-3">
                <div class="text-muted">Tổng số: {{ $products->total() }}</div>
                {{ $products->links() }}
            </div>
        </div>
    </div>
</div>

@endsection
