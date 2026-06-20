@extends('layout.admin')

@section('title', 'Sửa phòng chiếu')

@section('content')

<div class="col-12">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h3 class="mb-1">Sửa phòng chiếu</h3>
            <p class="text-muted mb-0">Cập nhật thông tin phòng chiếu.</p>
        </div>
        <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Quay lại danh sách
        </a>
    </div>
</div>

@if(session('error'))
    <div class="col-12 mt-3">
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            {{ session('error') }}
        </div>
    </div>
@endif

@if($errors->any())
    <div class="col-12 mt-3">
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            Vui lòng kiểm tra lại thông tin phòng chiếu.
        </div>
    </div>
@endif

<div class="col-12 mt-3">
    <div class="row g-3">
        <div class="col-auto">
            <div class="card card-body py-2 px-3">
                <small class="text-muted">Ghế đã cấu hình</small>
                <span class="fw-semibold"><i class="bi bi-grid-3x3-gap me-1"></i>{{ $room->seats_count }} ghế</span>
            </div>
        </div>
        <div class="col-auto">
            <div class="card card-body py-2 px-3">
                <small class="text-muted">Suất chiếu tương lai</small>
                <span class="fw-semibold"><i class="bi bi-calendar-event me-1"></i>{{ $room->upcoming_showtimes_count }} suất</span>
            </div>
        </div>
        <div class="col-auto">
            <div class="card card-body py-2 px-3">
                <small class="text-muted">Cập nhật lần cuối</small>
                <span class="fw-semibold">{{ $room->updated_at?->format('d/m/Y H:i') }}</span>
            </div>
        </div>
    </div>
</div>

@if($room->upcoming_showtimes_count > 0)
    <div class="col-12 mt-3">
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Phòng này đang có {{ $room->upcoming_showtimes_count }} suất chiếu chưa diễn ra. Hệ thống sẽ chặn thay đổi sức chứa hoặc chuyển phòng sang trạng thái đã ẩn.
        </div>
    </div>
@endif

<div class="col-12 mt-3">
    <div class="card">
        <div class="card-header bg-transparent">
            <div class="fw-semibold">
                <i class="bi bi-door-open me-2"></i>Thông tin phòng chiếu
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.rooms.update', $room) }}">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="name" class="form-label">Tên phòng <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $room->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="room_type" class="form-label">Loại phòng <span class="text-danger">*</span></label>
                        <input type="text" id="room_type" name="room_type" class="form-control @error('room_type') is-invalid @enderror" value="{{ old('room_type', $room->room_type) }}" required>
                        @error('room_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="total_seats" class="form-label">Sức chứa <span class="text-danger">*</span></label>
                        <input type="number" id="total_seats" name="total_seats" class="form-control @error('total_seats') is-invalid @enderror" value="{{ old('total_seats', $room->total_seats) }}" min="1" required>
                        @error('total_seats')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @if($room->seats_count != $room->total_seats)
                            <div class="form-text text-warning">
                                <i class="bi bi-exclamation-triangle me-1"></i>Số ghế đã cấu hình chưa khớp sức chứa hiện tại.
                            </div>
                        @endif
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="ACTIVE" {{ old('status', $room->status) == 'ACTIVE' ? 'selected' : '' }}>Hoạt động</option>
                            <option value="MAINTENANCE" {{ old('status', $room->status) == 'MAINTENANCE' ? 'selected' : '' }}>Bảo trì</option>
                            <option value="INACTIVE" {{ old('status', $room->status) == 'INACTIVE' ? 'selected' : '' }}>Đã ẩn</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Cập nhật phòng chiếu
                    </button>
                    <a href="{{ route('admin.rooms.seats', $room) }}" class="btn btn-outline-info">
                        <i class="bi bi-grid-3x3-gap me-1"></i> Xem sơ đồ ghế
                    </a>
                    <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline-secondary">
                        Huỷ bỏ
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
