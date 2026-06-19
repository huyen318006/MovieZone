@extends('layout.admin')

@section('title', 'Tạo suất chiếu')

@section('content')
@if(session('success'))
    <div id="success-alert" class="alert alert-success alert-dismissible fade show shadow-sm">
        <i class="bi bi-check-circle-fill me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div id="error-alert" class="alert alert-danger alert-dismissible fade show shadow-sm">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h3 class="mb-1">Tạo suất chiếu</h3>
        <p class="text-muted mb-0">Tạo suất chiếu mới cho rạp duy nhất của hệ thống.</p>
    </div>

    <a href="{{ route('admin.showtime') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>
        Quay lại
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <div class="fw-semibold">Thông tin suất chiếu</div>
            <small class="text-muted">Chọn phim, phòng chiếu và thời gian bắt đầu.</small>
        </div>
        <div class="text-muted small">
            Các giá trị hợp lệ sẽ được kiểm tra trước khi lưu.
        </div>
    </div>

    <div class="card-body">
        <form action="{{ route('admin.store.showtime') }}" method="POST" class="row g-3">
            @csrf

            <div class="col-12 col-lg-6">
                <label class="form-label">Phim <span class="text-danger">*</span></label>
                <select name="movie_id" class="form-select @error('movie_id') is-invalid @enderror" required>
                    <option value="">Chọn phim</option>
                    @foreach($movies as $movie)
                        <option value="{{ $movie->id }}" {{ old('movie_id') == $movie->id ? 'selected' : '' }}>
                            {{ $movie->title }}
                        </option>
                    @endforeach
                </select>
                @error('movie_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-lg-6">
                <label class="form-label">Phòng chiếu <span class="text-danger">*</span></label>
                <select name="room_id" class="form-select @error('room_id') is-invalid @enderror" required>
                    <option value="">Chọn phòng chiếu</option>
                    @foreach($rooms as $room)
                        <option value="{{ $room->id }}" {{ old('room_id') == $room->id ? 'selected' : '' }}>
                            {{ $room->name }}
                        </option>
                    @endforeach
                </select>
                @error('room_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-lg-6">
                <label class="form-label">Thời gian bắt đầu <span class="text-danger">*</span></label>
                <input
                    type="datetime-local"
                    name="start_time"
                    class="form-control @error('start_time') is-invalid @enderror"
                    value="{{ old('start_time') }}"
                    required
                >
                @error('start_time')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-lg-3">
                <label class="form-label">Định dạng <span class="text-danger">*</span></label>
                <select name="format" class="form-select @error('format') is-invalid @enderror" required>
                    <option value="2D" {{ old('format', '2D') === '2D' ? 'selected' : '' }}>2D</option>
                    <option value="3D" {{ old('format') === '3D' ? 'selected' : '' }}>3D</option>
                    <option value="IMAX" {{ old('format') === 'IMAX' ? 'selected' : '' }}>IMAX</option>
                </select>
                @error('format')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-lg-3">
                <label class="form-label">Ngôn ngữ <span class="text-danger">*</span></label>
                <input
                    type="text"
                    name="language_type"
                    class="form-control @error('language_type') is-invalid @enderror"
                    value="{{ old('language_type') }}"
                    placeholder="Ví dụ: Phụ đề, Lồng tiếng"
                    required
                >
                @error('language_type')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Hệ thống sẽ tự tính giờ kết thúc từ thời lượng phim, kiểm tra trùng lịch, và sinh ghế theo phòng chiếu.
                </div>
            </div>

            <div class="col-12 d-flex gap-2 flex-wrap pt-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>
                    Tạo suất chiếu
                </button>
                <a href="{{ route('admin.showtime') }}" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>
@endsection