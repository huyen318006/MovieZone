@extends('layout.admin')

@section('title', 'Thêm ghế mới')

@section('content')

<div class="col-12">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h3 class="mb-1">Thêm ghế mới</h3>
            <p class="text-muted mb-0">Cấu hình ghế cho phòng {{ $room->name }}</p>
        </div>
        <a href="{{ route('admin.seats.index', ['room_id' => $room->id]) }}"
           class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Quay lại
        </a>
    </div>
</div>

@if($errors->any())
    <div class="col-12 mt-3">
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            {{ $errors->first() }}
        </div>
    </div>
@endif

<div class="col-12 mt-3">
    <div class="row g-3">
        <div class="col-md-4">
            <div class="panel panel-sm">
                <small class="text-muted">Phòng</small>
                <div class="fw-semibold">{{ $room->name }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel panel-sm">
                <small class="text-muted">Loại phòng</small>
                <div class="fw-semibold">{{ $room->room_type }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel panel-sm">
                <small class="text-muted">Ghế hiện có</small>
                <div class="fw-semibold">{{ $room->seats()->count() }}</div>
            </div>
        </div>
    </div>
</div>

<div class="col-12 mt-3">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-transparent">
            <h5 class="mb-0">
                <i class="bi bi-plus-circle me-2"></i>Thêm dãy ghế
            </h5>
        </div>

        <div class="card-body">
            <form action="{{ route('admin.seats.store') }}" method="POST">
                @csrf
                <input type="hidden" name="room_id" value="{{ $room->id }}">

                <div class="row g-3">

                    <!-- Hàng ghế -->
                    <div class="col-md-3">
                        <label class="form-label">Hàng ghế</label>
                        <input type="text"
                               name="row_label"
                               class="form-control text-uppercase"
                               value="{{ old('row_label') }}"
                               placeholder="A, B, C..."
                               maxlength="3"
                               required>
                    </div>

                    <!-- Số ghế / hàng -->
                    <div class="col-md-3">
                        <label class="form-label">Số thứ tự ghế</label>
                        <input type="number"
                               name="seat_number"
                               class="form-control"
                               value="{{ old('seat_number') }}"
                               min="1"
                               max="50"
                               required>
                    </div>

                    <!-- Loại ghế -->
                    <div class="col-md-3">
                        <label class="form-label">Loại ghế</label>
                        <select name="seat_type" class="form-select">
                            <option value="STANDARD" {{ old('seat_type') == 'STANDARD' ? 'selected' : '' }}>STANDARD</option>
                            <option value="VIP" {{ old('seat_type') == 'VIP' ? 'selected' : '' }}>VIP</option>
                            <option value="COUPLE" {{ old('seat_type') == 'COUPLE' ? 'selected' : '' }}>COUPLE</option>
                        </select>
                        <div class="form-text">Giá sẽ tự lấy theo `seat_type` trong database.</div>
                    </div>


                    <!-- Trạng thái -->
                    <div class="col-md-3">
                        <label class="form-label">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="ACTIVE" {{ old('status') == 'ACTIVE' ? 'selected' : '' }}>ACTIVE</option>
                            <option value="LOCKED" {{ old('status') == 'LOCKED' ? 'selected' : '' }}>LOCKED</option>
                            <option value="BROKEN" {{ old('status') == 'BROKEN' ? 'selected' : '' }}>BROKEN</option>
                        </select>
                    </div>



                </div>

                <hr>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Lưu ghế
                    </button>

                    <a href="{{ route('admin.seats.index', ['room_id' => $room->id]) }}"
                       class="btn btn-outline-secondary">
                        Hủy
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

<style>
.panel-sm {
    background: #f8f9fa;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px;
}
</style>
