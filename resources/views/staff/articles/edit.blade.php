@extends('layout.staff')

@section('title', 'Chỉnh sửa Bài viết')
@section('page-title', 'Chỉnh sửa Bài viết')

@push('styles')
.article-edit-wrapper {
    max-width: 960px;
    margin: 0 auto;
}

.article-edit-card {
    background: var(--staff-surface);
    border: 1px solid var(--staff-border);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(2, 6, 23, 0.2);
}

.article-edit-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--staff-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.article-edit-header h5 {
    font-size: 16px;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--staff-text);
}

.article-edit-header h5 i {
    color: var(--staff-primary);
}

.article-edit-body {
    padding: 24px;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--staff-text);
    margin-bottom: 6px;
}

.form-label .required {
    color: var(--staff-danger);
    margin-left: 2px;
}

.form-control {
    width: 100%;
    background: var(--staff-bg);
    border: 1px solid var(--staff-border);
    border-radius: 8px;
    padding: 10px 14px;
    color: var(--staff-text);
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-control:focus {
    border-color: var(--staff-primary);
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.15);
}

.form-control::placeholder {
    color: var(--staff-text-muted);
}

.form-control.is-invalid {
    border-color: var(--staff-danger);
}

.invalid-feedback {
    display: block;
    font-size: 12px;
    color: var(--staff-danger);
    margin-top: 4px;
}

.form-hint {
    font-size: 12px;
    color: var(--staff-text-muted);
    margin-top: 4px;
}

select.form-control {
    cursor: pointer;
}

textarea.form-control {
    resize: vertical;
    min-height: 100px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}

.thumbnail-section {
    margin-top: 20px;
}

.thumbnail-current {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 12px 16px;
    background: var(--staff-bg);
    border: 1px solid var(--staff-border);
    border-radius: 8px;
    margin-top: 8px;
}

.thumbnail-current img {
    width: 80px;
    height: 60px;
    object-fit: cover;
    border-radius: 6px;
}

.thumbnail-current .thumb-info {
    font-size: 12px;
    color: var(--staff-text-muted);
}

.thumbnail-preview {
    display: none;
    margin-top: 8px;
}

.thumbnail-preview.show {
    display: block;
}

.thumbnail-preview img {
    max-height: 150px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid var(--staff-border);
}

.file-input-wrap {
    position: relative;
}

.file-input-wrap input[type="file"] {
    padding: 8px 12px;
    font-size: 13px;
}

.form-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding: 16px 24px;
    border-top: 1px solid var(--staff-border);
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    text-decoration: none;
}

.btn-primary {
    background: var(--staff-primary);
    color: #fff;
}

.btn-primary:hover {
    background: var(--staff-primary-hover);
    transform: translateY(-1px);
}

.btn-secondary {
    background: transparent;
    color: var(--staff-text-muted);
    border: 1px solid var(--staff-border);
}

.btn-secondary:hover {
    background: var(--staff-surface-hover);
    color: var(--staff-text);
    border-color: var(--staff-primary);
}
@endpush

@section('content')
<div class="article-edit-wrapper">
    <div class="article-edit-card">
        <div class="article-edit-header">
            <h5>
                <i class="bi bi-pencil-square"></i>
                Chỉnh sửa Bài viết
            </h5>
            <a href="{{ \App\Helpers\TabAuthHelper::route('staff.articles.show', $article->id) }}" class="btn btn-secondary" style="padding: 8px 14px; font-size: 13px;">
                <i class="bi bi-x-lg"></i> Hủy
            </a>
        </div>

        <form action="{{ \App\Helpers\TabAuthHelper::route('staff.articles.update', $article->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="article-edit-body">
                {{-- Title & Slug --}}
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="title">Tiêu đề bài viết <span class="required">*</span></label>
                        <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $article->title) }}" placeholder="Nhập tiêu đề bài viết">
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="slug">Slug (URL thân thiện)</label>
                        <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror"
                               value="{{ old('slug', $article->slug) }}" placeholder="Để trống để tự động tạo">
                        <div class="form-hint">Để trống sẽ tự động tạo từ tiêu đề.</div>
                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Category & Status --}}
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="category">Danh mục <span class="required">*</span></label>
                        <select name="category" id="category" class="form-control @error('category') is-invalid @enderror">
                            <option value="">— Chọn danh mục —</option>
                            <option value="Tin tức" {{ old('category', $article->category) == 'Tin tức' ? 'selected' : '' }}>Tin tức</option>
                            <option value="Sự kiện" {{ old('category', $article->category) == 'Sự kiện' ? 'selected' : '' }}>Sự kiện</option>
                            <option value="Khuyến mãi" {{ old('category', $article->category) == 'Khuyến mãi' ? 'selected' : '' }}>Khuyến mãi</option>
                            <option value="Giải trí" {{ old('category', $article->category) == 'Giải trí' ? 'selected' : '' }}>Giải trí</option>
                            <option value="Phim mới" {{ old('category', $article->category) == 'Phim mới' ? 'selected' : '' }}>Phim mới</option>
                            <option value="Đánh giá" {{ old('category', $article->category) == 'Đánh giá' ? 'selected' : '' }}>Đánh giá</option>
                        </select>
                        @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="status">Trạng thái <span class="required">*</span></label>
                        <select name="status" id="status" class="form-control @error('status') is-invalid @enderror">
                            <option value="DRAFT" {{ old('status', $article->status) == 'DRAFT' ? 'selected' : '' }}>Bản nháp (DRAFT)</option>
                            <option value="PUBLISHED" {{ old('status', $article->status) == 'PUBLISHED' ? 'selected' : '' }}>Xuất bản (PUBLISHED)</option>
                            <option value="HIDDEN" {{ old('status', $article->status) == 'HIDDEN' ? 'selected' : '' }}>Ẩn (HIDDEN)</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Published At --}}
                <div class="form-group" style="max-width: 300px;">
                    <label class="form-label" for="published_at">Ngày xuất bản</label>
                    <input type="datetime-local" name="published_at" id="published_at"
                           class="form-control @error('published_at') is-invalid @enderror"
                           value="{{ old('published_at', $article->published_at ? date('Y-m-d\TH:i', strtotime($article->published_at)) : '') }}">
                    <div class="form-hint">Để trống sẽ lấy thời điểm hiện tại nếu chọn Xuất bản.</div>
                    @error('published_at')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Thumbnail --}}
                <div class="form-group thumbnail-section">
                    <label class="form-label" for="thumbnail">Ảnh đại diện (thumbnail)</label>
                    <div class="file-input-wrap">
                        <input type="file" name="thumbnail" id="thumbnail" class="form-control @error('thumbnail') is-invalid @enderror" onchange="previewThumbnail(event)">
                    </div>
                    <div class="form-hint">Định dạng: jpg, jpeg, png, webp. Kích thước tối đa 4MB. Chọn ảnh khác nếu muốn thay đổi.</div>
                    @error('thumbnail')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror

                    {{-- Preview new thumbnail --}}
                    <div class="thumbnail-preview" id="thumbnailPreview">
                        <label class="form-label" style="font-size: 12px; color: var(--staff-text-muted);">Ảnh mới xem trước:</label>
                        <img id="thumbnailPreviewImg" src="#" alt="Preview">
                    </div>

                    {{-- Current thumbnail --}}
                    @if($article->thumbnail_url)
                        <div class="thumbnail-current" id="currentThumbnail">
                            <img src="{{ asset($article->thumbnail_url) }}" alt="Current thumbnail">
                            <div class="thumb-info">
                                <div style="font-weight: 600; color: var(--staff-text); margin-bottom: 2px;">Ảnh hiện tại</div>
                                <div>{{ basename($article->thumbnail_url) }}</div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Summary --}}
                <div class="form-group">
                    <label class="form-label" for="summary">Tóm tắt (mô tả ngắn)</label>
                    <textarea name="summary" id="summary" rows="3" class="form-control @error('summary') is-invalid @enderror"
                              placeholder="Nhập tóm tắt ngắn về bài viết (tối đa 500 ký tự)">{{ old('summary', $article->summary) }}</textarea>
                    @error('summary')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Content --}}
                <div class="form-group">
                    <label class="form-label" for="content">Nội dung bài viết <span class="required">*</span></label>
                    <textarea name="content" id="content" rows="16" class="form-control @error('content') is-invalid @enderror"
                              placeholder="Nhập nội dung bài viết...">{{ old('content', $article->content) }}</textarea>
                    @error('content')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="form-actions">
                <a href="{{ \App\Helpers\TabAuthHelper::route('staff.articles.show', $article->id) }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Quay lại
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Lưu thay đổi
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function previewThumbnail(event) {
        const preview = document.getElementById('thumbnailPreview');
        const previewImg = document.getElementById('thumbnailPreviewImg');
        const currentThumbnail = document.getElementById('currentThumbnail');
        if (event.target.files && event.target.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                preview.classList.add('show');
                if (currentThumbnail) currentThumbnail.style.display = 'none';
            }
            reader.readAsDataURL(event.target.files[0]);
        }
    }

    // Auto-generate slug from title
    document.getElementById('title').addEventListener('blur', function() {
        const slugField = document.getElementById('slug');
        const originalSlug = '{{ $article->slug }}';
        if (slugField.value.trim() === '' || slugField.value === originalSlug) {
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

