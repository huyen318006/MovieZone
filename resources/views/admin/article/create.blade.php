@extends('layout.admin')

@section('title', 'Thêm Bài viết mới')

@section('content')

<div class="col-12">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h3 class="mb-1">Thêm Bài viết mới</h3>
            <p class="text-muted mb-0">Tạo bài viết tin tức, sự kiện hoặc nội dung truyền thông mới.</p>
        </div>
        <div>
            <a href="{{ \App\Helpers\TabAuthHelper::route('admin.articles.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>
</div>

<div class="col-12 mt-3">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ \App\Helpers\TabAuthHelper::route('admin.articles.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row g-3">

                    <div class="col-md-8">
                        <label for="title" class="form-label fw-semibold">Tiêu đề bài viết <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" placeholder="Nhập tiêu đề bài viết">
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="slug" class="form-label fw-semibold">Slug (URL thân thiện)</label>
                        <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug') }}" placeholder="Để trống để tự động tạo">
                        <small class="text-muted">Để trống sẽ tự động tạo từ tiêu đề.</small>
                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="category" class="form-label fw-semibold">Danh mục <span class="text-danger">*</span></label>
                        <select name="category" id="category" class="form-select @error('category') is-invalid @enderror">
                            <option value="">— Chọn danh mục —</option>
                            <option value="Tin tức" {{ old('category') == 'Tin tức' ? 'selected' : '' }}>Tin tức</option>
                            <option value="Sự kiện" {{ old('category') == 'Sự kiện' ? 'selected' : '' }}>Sự kiện</option>
                            <option value="Khuyến mãi" {{ old('category') == 'Khuyến mãi' ? 'selected' : '' }}>Khuyến mãi</option>
                            <option value="Giải trí" {{ old('category') == 'Giải trí' ? 'selected' : '' }}>Giải trí</option>
                            <option value="Phim mới" {{ old('category') == 'Phim mới' ? 'selected' : '' }}>Phim mới</option>
                            <option value="Đánh giá" {{ old('category') == 'Đánh giá' ? 'selected' : '' }}>Đánh giá</option>
                        </select>
                        @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="status" class="form-label fw-semibold">Trạng thái <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="DRAFT" {{ old('status') == 'DRAFT' ? 'selected' : '' }}>Bản nháp (DRAFT)</option>
                            <option value="PUBLISHED" {{ old('status') == 'PUBLISHED' ? 'selected' : '' }}>Xuất bản (PUBLISHED)</option>
                            <option value="HIDDEN" {{ old('status') == 'HIDDEN' ? 'selected' : '' }}>Ẩn (HIDDEN)</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="published_at" class="form-label fw-semibold">Ngày xuất bản</label>
                        <input type="datetime-local" name="published_at" id="published_at" class="form-control @error('published_at') is-invalid @enderror" value="{{ old('published_at') }}">
                        <small class="text-muted">Để trống sẽ lấy thời điểm hiện tại nếu chọn Xuất bản.</small>
                        @error('published_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

<div class="col-md-6">
                        <label for="thumbnail" class="form-label fw-semibold">Ảnh đại diện (thumbnail)</label>
                        <input type="file" name="thumbnail" id="thumbnail" class="form-control @error('thumbnail') is-invalid @enderror" onchange="previewThumbnail(event)">
                        <small class="text-muted">Định dạng: jpg, jpeg, png, webp. Kích thước tối đa 4MB.</small>
                        @error('thumbnail')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div id="thumbnailPreview" class="mt-2" style="display: none;">
                            <label class="d-block form-label text-muted small">Ảnh xem trước:</label>
                            <img id="thumbnailPreviewImg" src="#" alt="Preview" class="img-thumbnail" style="max-height: 150px; object-fit: cover;">
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label for="summary" class="form-label fw-semibold">Tóm tắt (mô tả ngắn)</label>
                        <textarea name="summary" id="summary" rows="3" class="form-control @error('summary') is-invalid @enderror" placeholder="Nhập tóm tắt ngắn về bài viết (tối đa 500 ký tự)">{{ old('summary') }}</textarea>
                        @error('summary')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-12">
                        <label for="content" class="form-label fw-semibold">Nội dung bài viết <span class="text-danger">*</span></label>
                        <textarea name="content" id="content" rows="15" class="form-control @error('content') is-invalid @enderror" placeholder="Nhập nội dung bài viết...">{{ old('content') }}</textarea>
                        @error('content')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 text-end mt-4">
                        <button type="reset" class="btn btn-secondary me-2">Nhập lại</button>
                        <button type="submit" class="btn btn-primary">Lưu Bài viết</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function previewThumbnail(event) {
        const preview = document.getElementById('thumbnailPreview');
        const previewImg = document.getElementById('thumbnailPreviewImg');
        if (event.target.files && event.target.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(event.target.files[0]);
        }
    }

    document.getElementById('title').addEventListener('blur', function() {
        const slugField = document.getElementById('slug');
        if (slugField.value.trim() === '') {
            slugField.value = this.value
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[đĐ]/g, 'd')
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/[\s_]+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
        }
    });
</script>
@endpush
