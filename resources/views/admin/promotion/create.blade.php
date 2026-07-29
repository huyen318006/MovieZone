@extends('layout.admin')

@section('title', 'Thêm Khuyến mãi mới')

@section('content')

<div class="col-12">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h3 class="mb-1">Thêm Khuyến mãi mới</h3>
            <p class="text-muted mb-0">Tạo chương trình ưu đãi hoặc sự kiện quảng cáo mới.</p>
        </div>
        <div>
            <a href="{{ \App\Helpers\TabAuthHelper::route('admin.promotions.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>
</div>

<div class="col-12 mt-3">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ \App\Helpers\TabAuthHelper::route('admin.promotions.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="title" class="form-label fw-semibold">Tiêu đề chương trình <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" placeholder="Ví dụ: Khai trương cụm rạp mới - Đồng giá vé 45k...">
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="start_date" class="form-label fw-semibold">Ngày bắt đầu <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="start_date" id="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date') }}">
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="end_date" class="form-label fw-semibold">Ngày kết thúc <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="end_date" id="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}">
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="status" class="form-label fw-semibold">Trạng thái <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="ACTIVE" {{ old('status') == 'ACTIVE' ? 'selected' : '' }}>Hoạt động</option>
                            <option value="INACTIVE" {{ old('status') == 'INACTIVE' ? 'selected' : '' }}>Đã ẩn</option>
                            <option value="EXPIRED" {{ old('status') == 'EXPIRED' ? 'selected' : '' }}>Hết hạn</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="banner" class="form-label fw-semibold">Ảnh Banner</label>
                        <input type="file" name="banner" id="banner" class="form-control @error('banner') is-invalid @enderror">
                        <small class="text-muted">Định dạng hỗ trợ: jpeg, png, jpg, webp. Kích thước tối đa 4MB.</small>
                        @error('banner')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label fw-semibold">Mô tả chương trình</label>
                        <textarea name="description" id="description" class="form-control" rows="6" placeholder="Nội dung chi tiết chương trình khuyến mãi..."></textarea>
                    </div>

                    <div class="col-12 text-end mt-4">
                        <button type="reset" class="btn btn-secondary me-2">Nhập lại</button>
                        <button type="submit" class="btn btn-primary">Lưu chương trình</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
