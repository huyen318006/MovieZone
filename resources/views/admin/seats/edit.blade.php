@extends('layout.admin')

@section('title', 'Sửa ghế')

@section('content')

<div class="col-12">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h3 class="mb-1">Sửa ghế {{ $seat->seat_code }}</h3>
            <p class="text-muted mb-0">Phòng {{ $seat->room->name }}</p>
        </div>
        <a href="{{ route('admin.seats.index', ['room_id' => $seat->room_id]) }}"
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
                <small class="text-muted">Mã ghế</small>
                <div class="fw-semibold">{{ $seat->seat_code }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel panel-sm">
                <small class="text-muted">Phòng</small>
                <div class="fw-semibold">{{ $seat->room->name }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel panel-sm">
                <small class="text-muted">Trạng thái đang dùng</small>
                @if($seat->status == 'ACTIVE')
                    <span class="badge text-bg-success">ACTIVE</span>
                @elseif($seat->status == 'BLOCKED')
                    <span class="badge text-bg-secondary">BLOCKED</span>
                @else
                    <span class="badge text-bg-danger">BROKEN</span>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="col-12 mt-3">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-transparent">
            <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Cập nhật ghế</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.seats.update', $seat->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Hàng ghế</label>
                        <input type="text" name="row_label" class="form-control" value="{{ old('row_label', $seat->row_label) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Số ghế</label>
                        <input type="number" name="seat_number" class="form-control" value="{{ old('seat_number', $seat->seat_number) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Loại ghế</label>
                        <select name="seat_type" class="form-select">
                            <option value="STANDARD" {{ old('seat_type', $seat->seat_type) == 'STANDARD' ? 'selected' : '' }}>STANDARD</option>
                            <option value="VIP" {{ old('seat_type', $seat->seat_type) == 'VIP' ? 'selected' : '' }}>VIP</option>
                            <option value="COUPLE" {{ old('seat_type', $seat->seat_type) == 'COUPLE' ? 'selected' : '' }}>COUPLE</option>
                            <option value="DEMO" {{ old('seat_type', $seat->seat_type) == 'DEMO' ? 'selected' : '' }}>DEMO (10.000đ)</option>
                        </select>
                        <div class="form-text">Giá sẽ tự cập nhật theo database.</div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="ACTIVE" {{ old('status', $seat->status) == 'ACTIVE' ? 'selected' : '' }}>ACTIVE</option>
                            <option value="LOCKED" {{ old('status', $seat->status) == 'LOCKED' ? 'selected' : '' }}>LOCKED</option>
                            <option value="BROKEN" {{ old('status', $seat->status) == 'BROKEN' ? 'selected' : '' }}>BROKEN</option>
                        </select>
                    </div>

                </div>

                <hr>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Cập nhật ghế
                    </button>
                    <a href="{{ route('admin.seats.index', ['room_id' => $seat->room_id]) }}"
                       class="btn btn-outline-secondary">Hủy</a>
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