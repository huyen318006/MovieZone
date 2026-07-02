@extends('layout.admin')

@section('title', 'Sửa Khuyến mãi')

@section('content')

<div class="col-12">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h3 class="mb-1">Sửa Khuyến mãi</h3>
            <p class="text-muted mb-0">Cập nhật chương trình ưu đãi.</p>
        </div>
        <div>
            <a href="{{ route('admin.promotions.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>
</div>

<div class="col-12 mt-3">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.promotions.update', $promotion->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="title" class="form-label fw-semibold">Tiêu đề chương trình <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $promotion->title) }}" placeholder="Nhập tiêu đề chương trình...">
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="start_date" class="form-label fw-semibold">Ngày bắt đầu <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="start_date" id="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', \Carbon\Carbon::parse($promotion->start_date)->format('Y-m-d\TH:i')) }}">
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="end_date" class="form-label fw-semibold">Ngày kết thúc <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="end_date" id="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', \Carbon\Carbon::parse($promotion->end_date)->format('Y-m-d\TH:i')) }}">
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="status" class="form-label fw-semibold">Trạng thái <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="ACTIVE" {{ old('status', $promotion->status) == 'ACTIVE' ? 'selected' : '' }}>Hoạt động</option>
                            <option value="INACTIVE" {{ old('status', $promotion->status) == 'INACTIVE' ? 'selected' : '' }}>Đã ẩn</option>
                            <option value="EXPIRED" {{ old('status', $promotion->status) == 'EXPIRED' ? 'selected' : '' }}>Hết hạn</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="banner" class="form-label fw-semibold">Ảnh Banner</label>
                        <input type="file" name="banner" id="banner" class="form-control @error('banner') is-invalid @enderror">
                        <small class="text-muted">Định dạng hỗ trợ: jpeg, png, jpg, webp. Kích thước tối đa 4MB. Để trống nếu giữ nguyên.</small>
                        @error('banner')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        @if($promotion->banner_url)
                            <div class="mt-2">
                                <p class="mb-1 small fw-semibold">Banner hiện tại:</p>
                                <img src="{{ Str::startsWith($promotion->banner_url, 'http') ? $promotion->banner_url : asset('storage/' . $promotion->banner_url) }}" alt="{{ $promotion->title }}" class="img-thumbnail" style="width: 240px; max-height: 120px; object-fit: cover;">
                            </div>
                        @endif
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label fw-semibold">Mô tả chương trình</label>
                        <textarea name="description" id="description" class="form-control" rows="6" placeholder="Nội dung chi tiết chương trình khuyến mãi...">{{ old('description', $promotion->description) }}</textarea>
                    </div>

                    <div class="col-12 text-end mt-4">
                        <button type="submit" class="btn btn-primary">Cập nhật chương trình</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
