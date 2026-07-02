@extends('layout.admin')

@section('title', 'Thêm Sản phẩm lẻ')

@section('content')

<div class="col-12">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h3 class="mb-1">Thêm Sản phẩm lẻ</h3>
            <p class="text-muted mb-0">Tạo bắp, nước, snack mới trong hệ thống.</p>
        </div>
        <div>
            <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>
</div>

<div class="col-12 mt-3">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label fw-semibold">Tên sản phẩm <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Nhập tên sản phẩm, ví dụ: Pepsi Lon, Bắp rang ngọt...">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="price" class="form-label fw-semibold">Giá bán (VNĐ) <span class="text-danger">*</span></label>
                        <input type="number" name="price" id="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price') }}" min="0" placeholder="Ví dụ: 30000">
                        @error('price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="status" class="form-label fw-semibold">Trạng thái <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="ACTIVE" {{ old('status') == 'ACTIVE' ? 'selected' : '' }}>Hoạt động</option>
                            <option value="INACTIVE" {{ old('status') == 'INACTIVE' ? 'selected' : '' }}>Đã ẩn (Không bán)</option>
                            <option value="OUT_OF_STOCK" {{ old('status') == 'OUT_OF_STOCK' ? 'selected' : '' }}>Hết hàng</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="image" class="form-label fw-semibold">Ảnh sản phẩm</label>
                        <input type="file" name="image" id="image" class="form-control @error('image') is-invalid @enderror">
                        <small class="text-muted">Định dạng hỗ trợ: jpeg, png, jpg, webp. Tối đa 2MB.</small>
                        @error('image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label fw-semibold">Mô tả sản phẩm</label>
                        <textarea name="description" id="description" class="form-control" rows="4" placeholder="Mô tả chi tiết về sản phẩm...">{{ old('description') }}</textarea>
                    </div>

                    <div class="col-12 text-end mt-4">
                        <button type="reset" class="btn btn-secondary me-2">Nhập lại</button>
                        <button type="submit" class="btn btn-primary">Lưu sản phẩm</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
