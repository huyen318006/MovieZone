@extends('layout.admin')

@section('title', 'Thêm phòng chiếu')

@section('content')

<div class="col-12">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h3 class="mb-1">Thêm phòng chiếu</h3>
            <p class="text-muted mb-0">Tạo phòng chiếu mới.</p>
        </div>
        <a href="{{ \App\Helpers\TabAuthHelper::route('admin.rooms.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Quay lại danh sách
        </a>
    </div>
</div>

@if($errors->any())
    <div class="col-12 mt-3">
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            Vui lòng kiểm tra lại thông tin phòng chiếu.
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
            <form method="POST" action="{{ \App\Helpers\TabAuthHelper::route('admin.rooms.store') }}">
                @csrf

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="name" class="form-label">Tên phòng <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="VD: Phòng 01" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="room_type" class="form-label">Loại phòng <span class="text-danger">*</span></label>
                        <select id="room_type" name="room_type" class="form-select @error('room_type') is-invalid @enderror" required onchange="onRoomTypeChange(this.value)">
                            <option value="" disabled {{ old('room_type') ? '' : 'selected' }}>-- Chọn loại phòng --</option>
                            <option value="2D" {{ old('room_type') == '2D' ? 'selected' : '' }}>2D</option>
                            <option value="3D" {{ old('room_type') == '3D' ? 'selected' : '' }}>3D</option>
                            <option value="IMAX" {{ old('room_type') == 'IMAX' ? 'selected' : '' }}>IMAX</option>
                            <option value="4DX" {{ old('room_type') == '4DX' ? 'selected' : '' }}>4DX</option>
                        </select>
                        @error('room_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text text-muted mt-1" id="capacityHint" style="display:none;">
                            <i class="bi bi-info-circle me-1"></i><span id="capacityHintText"></span>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="total_seats" class="form-label">Sức chứa <span class="text-danger">*</span></label>
                        <input type="number" id="total_seats" name="total_seats" class="form-control @error('total_seats') is-invalid @enderror" value="{{ old('total_seats') }}" min="1" placeholder="VD: 120" required>
                        @error('total_seats')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="ACTIVE" {{ old('status', 'ACTIVE') == 'ACTIVE' ? 'selected' : '' }}>Hoạt động</option>
                            <option value="MAINTENANCE" {{ old('status') == 'MAINTENANCE' ? 'selected' : '' }}>Bảo trì</option>
                            <option value="INACTIVE" {{ old('status') == 'INACTIVE' ? 'selected' : '' }}>Đã ẩn</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Lưu phòng chiếu
                    </button>
                    <a href="{{ \App\Helpers\TabAuthHelper::route('admin.rooms.index') }}" class="btn btn-outline-secondary">
                        Huỷ bỏ
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
const roomCapacities = {
    // Mapping sức chứa mặc định theo yêu cầu:
    '2D': 120,
    '3D': 140,
    'IMAX': 160,
    '4DX': 100
};

function onRoomTypeChange(type) {
    const capacity = roomCapacities[type];
    const input = document.getElementById('total_seats');
    const hint = document.getElementById('capacityHint');
    const hintText = document.getElementById('capacityHintText');
    if (capacity) {
        input.value = capacity;
        hintText.textContent = `Sức chứa mặc định của phòng ${type}: ${capacity} ghế (có thể chỉnh sửa)`;
        hint.style.display = 'block';
    } else {
        hint.style.display = 'none';
    }
}
</script>
@endpush

@endsection
