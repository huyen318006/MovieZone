@extends('layout.admin')

@section('title', 'Chỉnh sửa Banner')

@section('content')

<div class="col-12">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h3 class="mb-1">Chỉnh sửa Banner</h3>
            <p class="text-muted mb-0">Cập nhật thông tin chi tiết và lập lịch hiển thị cho banner.</p>
        </div>
        <div>
            <a href="{{ route('admin.banners.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>
</div>

<div class="col-12 mt-3">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.banners.update', $banner->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="title" class="form-label fw-semibold">Tiêu đề banner <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $banner->title) }}" placeholder="Ví dụ: Banner phim Avatar 2, Khuyến mãi bắp nước...">
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="image" class="form-label fw-semibold">Hình ảnh banner (Chọn ảnh khác nếu muốn thay đổi)</label>
                        <input type="file" name="image" id="image" class="form-control @error('image') is-invalid @enderror">
                        <small class="text-muted">Định dạng hỗ trợ: jpeg, png, jpg, webp. Kích thước tối đa 4MB.</small>
                        @error('image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        @if($banner->image_url)
                            <div class="mt-2">
                                <label class="d-block form-label text-muted small">Ảnh hiện tại:</label>
                                <img src="{{ asset('storage/' . $banner->image_url) }}" alt="Preview" class="img-thumbnail" style="max-height: 120px; object-fit: cover;">
                            </div>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <label for="position" class="form-label fw-semibold">Vị trí hiển thị <span class="text-danger">*</span></label>
                        <select name="position" id="position" class="form-select @error('position') is-invalid @enderror">
                            <option value="HOME_TOP" {{ old('position') == 'HOME_TOP' ? 'selected' : '' }}>Bannner đầu trang chủ</option>
                            <option value="HOME_MIDDLE" {{ old('position') == 'HOME_MIDDLE' ? 'selected' : '' }}>Banner giữa trang chủ </option>
                        </select>
                        @error('position')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="status" class="form-label fw-semibold">Trạng thái cấu hình <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="ACTIVE" {{ old('status', $banner->status) == 'ACTIVE' ? 'selected' : '' }}>Bật (ACTIVE)</option>
                            <option value="INACTIVE" {{ old('status', $banner->status) == 'INACTIVE' ? 'selected' : '' }}>Tắt (INACTIVE)</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-12">
                        <label for="link_url" class="form-label fw-semibold">Đường dẫn liên kết (Link URL khi click banner)</label>
                        <input type="url" name="link_url" id="link_url" class="form-control @error('link_url') is-invalid @enderror" value="{{ old('link_url', $banner->link_url) }}" placeholder="Ví dụ: http://moviezone.test/movies/slug-phim">
                        <small class="text-muted">Nhập URL đầy đủ bắt đầu bằng http:// hoặc https://</small>
                        @error('link_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="start_date" class="form-label fw-semibold">Ngày bắt đầu hiển thị (Lập lịch)</label>
                        <input type="datetime-local" name="start_date" id="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', $banner->start_date ? date('Y-m-d\TH:i', strtotime($banner->start_date)) : '') }}">
                        <small class="text-muted">Bỏ trống nếu muốn hiển thị ngay lập tức.</small>
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="end_date" class="form-label fw-semibold">Ngày kết thúc hiển thị (Lập lịch)</label>
                        <input type="datetime-local" name="end_date" id="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', $banner->end_date ? date('Y-m-d\TH:i', strtotime($banner->end_date)) : '') }}">
                        <small class="text-muted">Bỏ trống nếu muốn hiển thị vô thời hạn.</small>
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 text-end mt-4">
                        <a href="{{ route('admin.banners.index') }}" class="btn btn-secondary me-2">Hủy bỏ</a>
                        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
