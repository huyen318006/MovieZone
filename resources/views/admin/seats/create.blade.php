@extends('layout.admin')

@section('title', 'Thêm ghế mới')

@section('content')

<div class="col-12">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h3 class="mb-1">Thêm ghế mới</h3>
            <p class="text-muted mb-0">Cấu hình ghế cho phòng {{ $room->name }}</p>
        </div>
        <a href="{{ \App\Helpers\TabAuthHelper::route('admin.seats.index', ['room_id' => $room->id]) }}"
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
            <form action="{{ \App\Helpers\TabAuthHelper::route('admin.seats.store') }}" method="POST">
                @csrf
                <input type="hidden" name="room_id" value="{{ $room->id }}">

                <div class="row g-3">

                    <!-- Loại ghế (đặt lên đầu để trigger JS) -->
                    <div class="col-md-3">
                        <label class="form-label">Loại ghế</label>
                        <select name="seat_type" id="seatTypeSelect" class="form-select">
                            <option value="STANDARD" {{ old('seat_type') == 'STANDARD' ? 'selected' : '' }}>STANDARD</option>
                            <option value="VIP" {{ old('seat_type') == 'VIP' ? 'selected' : '' }}>VIP</option>
                            <option value="COUPLE" {{ old('seat_type') == 'COUPLE' ? 'selected' : '' }}>COUPLE</option>
                            <option value="DEMO" {{ old('seat_type') == 'DEMO' ? 'selected' : '' }}>DEMO (10.000đ)</option>
                        </select>
                        <div class="form-text" id="seatTypeHint">Giá sẽ tự lấy theo `seat_type` trong database.</div>
                    </div>

                    <!-- Hàng ghế -->
                    <div class="col-md-3">
                        <label class="form-label">Hàng ghế</label>
                        <input type="text"
                               name="row_label"
                               id="rowLabelInput"
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
                               id="seatNumberInput"
                               class="form-control"
                               value="{{ old('seat_number') }}"
                               min="1"
                               max="50"
                               required>
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

                    <!-- Demo price hint -->
                    <div class="col-12 d-none" id="demoPriceHint">
                        <div class="alert alert-info mb-0 py-2">
                            <i class="bi bi-info-circle me-1"></i>
                            Ghế DEMO sẽ được tạo ở <strong>hàng Z, số 99</strong> với giá cố định <strong>10.000 VND</strong> (dùng để test đặt vé).
                        </div>
                    </div>

                </div>

                <hr>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Lưu ghế
                    </button>

                    <a href="{{ \App\Helpers\TabAuthHelper::route('admin.seats.index', ['room_id' => $room->id]) }}"
                       class="btn btn-outline-secondary">
                        Hủy
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const seatTypeSelect = document.getElementById('seatTypeSelect');
    const rowLabelInput = document.getElementById('rowLabelInput');
    const seatNumberInput = document.getElementById('seatNumberInput');
    const demoPriceHint = document.getElementById('demoPriceHint');
    const seatTypeHint = document.getElementById('seatTypeHint');

    // Lưu giá trị gốc để khôi phục
    let savedRowLabel = rowLabelInput.value;
    let savedSeatNumber = seatNumberInput.value;

    function toggleDemoMode() {
        const isDemo = seatTypeSelect.value === 'DEMO';

        if (isDemo) {
            // Lưu lại giá trị hiện tại trước khi đổi
            savedRowLabel = rowLabelInput.value;
            savedSeatNumber = seatNumberInput.value;

            rowLabelInput.value = 'Z';
            rowLabelInput.readOnly = true;
            rowLabelInput.classList.add('bg-light');

            seatNumberInput.value = 99;
            seatNumberInput.readOnly = true;
            seatNumberInput.classList.add('bg-light');

            demoPriceHint.classList.remove('d-none');
            seatTypeHint.textContent = 'Giá cố định: 10.000 VND';
        } else {
            // Khôi phục giá trị cũ
            rowLabelInput.value = savedRowLabel;
            rowLabelInput.readOnly = false;
            rowLabelInput.classList.remove('bg-light');

            seatNumberInput.value = savedSeatNumber;
            seatNumberInput.readOnly = false;
            seatNumberInput.classList.remove('bg-light');

            demoPriceHint.classList.add('d-none');
            seatTypeHint.textContent = 'Giá sẽ tự lấy theo `seat_type` trong database.';
        }
    }

    seatTypeSelect.addEventListener('change', toggleDemoMode);

    // Chạy 1 lần khi load (phòng trường hợp old('seat_type') == 'DEMO')
    toggleDemoMode();
});
</script>
@endpush

<style>
.panel-sm {
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 12px;
    padding: 16px;
    color: #f1f5f9;
}

.panel-sm small.text-muted {
    color: #94a3b8 !important;
}

.panel-sm .fw-semibold {
    color: #f1f5f9;
}

/* Fix: input hàng ghế & số thứ tự ghế bị trắng do Bootstrap dark mode conflict */
#rowLabelInput,
#seatNumberInput {
    background-color: var(--bs-body-bg, #212529) !important;
    color: var(--bs-body-color, #dee2e6) !important;
    border-color: #495057 !important;
}

#rowLabelInput:focus,
#seatNumberInput:focus {
    background-color: var(--bs-body-bg, #212529) !important;
    color: var(--bs-body-color, #dee2e6) !important;
    border-color: #86b7fe !important;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Override bg-light trong dark mode (dùng khi DEMO) */
#rowLabelInput.bg-light,
#seatNumberInput.bg-light {
    background-color: #374151 !important;
    color: #9ca3af !important;
    border-color: #4b5563 !important;
}
</style>
