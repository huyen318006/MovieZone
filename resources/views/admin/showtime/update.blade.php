@extends('layout.admin')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">Cập nhật suất chiếu</h3>
            <p class="text-muted mb-0">
                Chỉnh sửa thông tin suất chiếu hiện tại.
            </p>
        </div>
        <a href="{{ route('admin.showtime') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
            Quay lại
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <strong>Thông tin suất chiếu</strong>
        </div>

        <div class="card-body">
            <form action="{{ route('update.showtime', $showtime->id) }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label">
                        Phim <span class="text-danger">*</span>
                    </label>

                    <select name="movie_id" class="form-select">
                        <option value="">Chọn phim</option>
                        @foreach($movies as $movie)
                            <option value="{{ $movie->id }}" {{ old('movie_id', $showtime->movie_id) == $movie->id ? 'selected' : '' }}>
                                {{ $movie->title }}
                            </option>
                        @endforeach
                    </select>

                    @error('movie_id')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        Phòng chiếu <span class="text-danger">*</span>
                    </label>

                    <select name="room_id" class="form-select">
                        <option value="">Chọn phòng chiếu</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}" {{ old('room_id', $showtime->room_id) == $room->id ? 'selected' : '' }}>
                                {{ $room->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('room_id')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        Thời gian bắt đầu <span class="text-danger">*</span>
                    </label>

                    <input
                        type="datetime-local"
                        name="start_time"
                        class="form-control"
                        value="{{ old('start_time', optional($showtime->start_time)->format('Y-m-d\TH:i')) }}"
                    >

                    @error('start_time')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Định dạng <span class="text-danger">*</span></label>
                    <select name="format" class="form-select">
                        <option value="2D" {{ old('format', $showtime->format) == '2D' ? 'selected' : '' }}>2D</option>
                        <option value="3D" {{ old('format', $showtime->format) == '3D' ? 'selected' : '' }}>3D</option>
                        <option value="IMAX" {{ old('format', $showtime->format) == 'IMAX' ? 'selected' : '' }}>IMAX</option>
                    </select>

                    @error('format')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label">Ngôn ngữ <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        name="language_type"
                        class="form-control"
                        value="{{ old('language_type', $showtime->language_type) }}"
                        placeholder="Ví dụ: Phụ đề, Lồng tiếng"
                    >

                    @error('language_type')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i>
                        Cập nhật suất chiếu
                    </button>

                    <a href="{{ route('admin.showtime') }}" class="btn btn-secondary">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
