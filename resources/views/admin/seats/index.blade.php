@extends('layout.admin')

@section('title', 'Quản lý ghế ngồi')

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm" id="success-alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" id="error-alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" id="validation-alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        {{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="col-12">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h3 class="mb-1">Quản lý ghế ngồi</h3>
            <p class="text-muted mb-0">Chọn rạp và phòng để xem sơ đồ ghế, khóa/mở ghế hoặc cập nhật thông tin ghế.</p>
        </div>
        @if(request('room_id'))
            <a href="{{ route('admin.seats.create', ['room_id' => request('room_id')]) }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Thêm ghế mới
            </a>
        @endif
    </div>
</div>

<div class="col-12 mt-3">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form action="{{ route('admin.seats.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Tên rạp chiếu</label>
                    <select name="cinema_id" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Chọn rạp chiếu --</option>
                        @foreach($cinemas as $cinema)
                            <option value="{{ $cinema->id }}" {{ request('cinema_id') == $cinema->id ? 'selected' : '' }}>
                                {{ $cinema->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Phòng chiếu</label>
                    <select name="room_id" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Chọn phòng --</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}" {{ request('room_id') == $room->id ? 'selected' : '' }}>
                                {{ $room->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.seats.index') }}" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-clockwise me-1"></i> Làm mới
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

@if(request('room_id'))
    <div class="col-12 mt-3">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="panel panel-sm">
                    <div class="text-muted small">Rạp</div>
                    <div class="fw-bold">{{ $rooms->firstWhere('id', request('room_id'))?->cinema?->name ?? '—' }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-sm">
                    <div class="text-muted small">Phòng</div>
                    <div class="fw-bold">{{ $rooms->firstWhere('id', request('room_id'))?->name ?? '—' }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-sm">
                    <div class="text-muted small">Tổng ghế</div>
                    <div class="fw-bold">{{ count($seatsGrouped->flatten()) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-sm">
                    <div class="text-muted small">Tình trạng</div>
                    <div class="fw-bold text-success">Đã cấu hình</div>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-md-3">
                <div class="summary-card summary-standard">
                    <div class="summary-label">STANDARD</div>
                    <div class="summary-value">{{ $seatsGrouped->flatten()->where('seat_type', 'STANDARD')->count() }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card summary-vip">
                    <div class="summary-label">VIP</div>
                    <div class="summary-value">{{ $seatsGrouped->flatten()->where('seat_type', 'VIP')->count() }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card summary-couple">
                    <div class="summary-label">COUPLE</div>
                    <div class="summary-value">{{ $seatsGrouped->flatten()->where('seat_type', 'COUPLE')->count() }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card summary-blocked">
                    <div class="summary-label">LOCKED</div>
                    <div class="summary-value">{{ $seatsGrouped->flatten()->whereIn('status', ['LOCKED', 'BLOCKED'])->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 mt-3">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">Sơ đồ phòng chiếu</h5>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#bulkDeleteModal">
                        <i class="bi bi-trash3 me-1"></i> Xóa nhiều
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#bulkCreateModal">
                        <i class="bi bi-plus-square me-1"></i> Tạo nhiều ghế
                    </button>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="legend-wrap mb-3">
                    <span class="legend-item"><span class="dot" style="background:#3b82f6"></span> STANDARD</span>
                    <span class="legend-item"><span class="dot" style="background:#eab308"></span> VIP</span>
                    <span class="legend-item"><span class="dot" style="background:#ec4899"></span> COUPLE</span>
                    <span class="legend-item"><span class="dot" style="background:#475569"></span> LOCKED</span>
                    <span class="legend-item"><span class="dot" style="background:#ef4444"></span> BROKEN</span>
                </div>
                <div class="cinema-screen">MÀN HÌNH</div>
                <div class="map-wrapper">
                    @foreach($seatsGrouped as $row => $seats)
                        <div class="seat-row">
                            <div class="row-label">{{ $row }}</div>
                            @foreach($seats as $seat)
                                <div class="seat-wrapper" tabindex="0">
                                    <div class="seat-select">
                                        <input type="checkbox"
                                               class="seat-checkbox form-check-input"
                                               name="seat_ids[]"
                                               value="{{ $seat->id }}"
                                               form="bulkDeleteForm"
                                               aria-label="Chọn ghế {{ $seat->seat_code }}">
                                    </div>
                                    <div class="seat seat-{{ $seat->status === 'ACTIVE' ? $seat->seat_type : $seat->status }}"
                                         title="{{ $seat->seat_code }} · {{ number_format($seat->price) }}đ · {{ $seat->status }}">
                                        {{ $seat->seat_number }}
                                    </div>
                                    <div class="seat-actions">
                                        <a href="{{ route('admin.seats.edit', $seat->id) }}" title="Sửa ghế">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <form action="{{ route('admin.seats.toggle_lock', $seat->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" title="{{ in_array($seat->status, ['LOCKED', 'BLOCKED']) ? 'Mở khóa' : 'Khóa ghế' }}">
                                                <i class="bi {{ in_array($seat->status, ['LOCKED', 'BLOCKED']) ? 'bi-unlock-fill' : 'bi-lock-fill' }}"></i>
                                            </button>
                                        </form>
                                        <button type="button" title="Xóa ghế" data-bs-toggle="modal" data-bs-target="#deleteSeatModal{{ $seat->id }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="modal fade" id="deleteSeatModal{{ $seat->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Xác nhận xóa ghế</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                Bạn có chắc chắn muốn xóa mềm ghế <strong>{{ $seat->seat_code }}</strong> không?
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                                <form action="{{ route('admin.seats.destroy', $seat->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger">Xác nhận</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="bulkDeleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Xóa nhiều ghế</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-muted small mb-2">Bạn đã chọn <strong id="bulkDeleteCount">0</strong> ghế.</div>
                    <div>Bạn có chắc chắn muốn xóa mềm các ghế đã chọn không?</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <form id="bulkDeleteForm" action="{{ route('admin.seats.destroy_many') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-danger" id="bulkDeleteSubmit" disabled>Xác nhận xóa</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="bulkCreateModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tạo nhiều ghế theo hàng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('admin.seats.store_batch') }}" method="POST">
                        @csrf
                        <input type="hidden" name="room_id" value="{{ request('room_id') }}">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Hàng</label>
                                <input type="text" name="row_label" class="form-control" maxlength="1" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Từ số</label>
                                <input type="number" name="start" class="form-control" min="1" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Đến số</label>
                                <input type="number" name="end" class="form-control" min="1" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Loại ghế</label>
                                <select name="seat_type" class="form-select">
                                    <option value="STANDARD">STANDARD</option>
                                    <option value="VIP">VIP</option>
                                    <option value="COUPLE">COUPLE</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Giá</label>
                                <input type="number" name="price" class="form-control" min="0" value="90000" required>
                            </div>
                        </div>
                        <div class="mt-3 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                            <button type="submit" class="btn btn-primary">Tạo ghế</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    ['success-alert', 'error-alert'].forEach(function (id) {
        const alertEl = document.getElementById(id);
        if (alertEl) {
            setTimeout(() => {
                alertEl.classList.remove('show');
                setTimeout(() => alertEl.remove(), 150);
            }, 3000);
        }
    });

    const bulkDeleteCount = document.getElementById('bulkDeleteCount');
    const bulkDeleteSubmit = document.getElementById('bulkDeleteSubmit');
    const seatCheckboxes = document.querySelectorAll('.seat-checkbox');

    function updateBulkDeleteState() {
        const selectedCount = document.querySelectorAll('.seat-checkbox:checked').length;
        if (bulkDeleteCount) bulkDeleteCount.textContent = selectedCount;
        if (bulkDeleteSubmit) bulkDeleteSubmit.disabled = selectedCount === 0;
    }

    seatCheckboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', updateBulkDeleteState);
    });

    document.querySelectorAll('.seat-wrapper').forEach(function (wrapper) {
        wrapper.addEventListener('click', function (event) {
            if (event.target.closest('button, a, input, form')) return;
            wrapper.classList.toggle('show-actions');
        });
    });

    updateBulkDeleteState();
});
</script>
@endpush

<style>
    .panel-sm {
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 16px;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.45);
    }
    .summary-card {
        border-radius: 14px;
        padding: 14px 16px;
        color: #0f172a;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.22), 0 8px 18px rgba(15,23,42,.06);
    }
    .summary-standard { background: linear-gradient(90deg, #eef6ff 0%, #dbeafe 100%); }
    .summary-vip { background: linear-gradient(90deg, #fff7db 0%, #fde68a 100%); }
    .summary-couple { background: linear-gradient(90deg, #fdf2f8 0%, #fce7f3 100%); }
    .summary-blocked { background: linear-gradient(90deg, #f8fafc 0%, #e2e8f0 100%); }
    .summary-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #64748b; }
    .summary-value { font-size: 1.6rem; font-weight: 800; }
    .cinema-screen {
        background: linear-gradient(180deg, #eef4ff 0%, #f8fafc 100%);
        height: 42px;
        width: 68%;
        margin: 0 auto 46px;
        border-radius: 0 0 999px 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 6px;
        box-shadow: inset 0 -1px 0 rgba(148, 163, 184, .18);
    }
    .map-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 14px;
        overflow-x: auto;
        padding: 12px 8px 18px;
    }
    .seat-row {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .row-label {
        width: 28px;
        font-weight: 800;
        color: #0f172a;
        text-align: center;
        font-size: 12px;
    }
    .seat-wrapper {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding-top: 14px;
    }
    .seat-select {
        position: absolute;
        top: 0;
        right: -5px;
        z-index: 4;
        background: #fff;
        border: 1px solid #cbd5e1;
        width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 5px;
        box-shadow: 0 3px 10px rgba(15,23,42,.08);
    }
    .seat {
        width: 44px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 800;
        cursor: pointer;
        color: #fff;
        transition: transform .12s ease, box-shadow .12s ease, filter .12s ease;
        box-shadow: inset 0 -3px 0 rgba(0,0,0,.16), 0 4px 10px rgba(15,23,42,.08);
        border: 1px solid rgba(255,255,255,.22);
    }
    .seat:hover {
        transform: translateY(-1px);
        filter: brightness(1.04);
    }
    .seat-STANDARD {
        background: linear-gradient(180deg, #dbeafe 0%, #60a5fa 100%);
        color: #0f172a;
        border-color: #93c5fd;
    }
    .seat-VIP {
        background: linear-gradient(180deg, #fde68a 0%, #f59e0b 100%);
        color: #111827;
        border-color: #fbbf24;
    }
    .seat-COUPLE {
        background: linear-gradient(180deg, #fbcfe8 0%, #ec4899 100%);
        width: 78px;
        border-color: #f9a8d4;
    }
    .seat-LOCKED, .seat-BLOCKED {
        background: linear-gradient(180deg, #94a3b8 0%, #475569 100%);
        color: #eef2ff;
    }
    .seat-BROKEN {
        background: linear-gradient(180deg, #fecaca 0%, #ef4444 100%);
        color: #fff7ed;
    }
    .seat-actions {
        display: flex;
        gap: 4px;
        background: #fff;
        padding: 4px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .1);
        opacity: 0;
        visibility: hidden;
        transition: opacity .15s ease;
        margin-top: 6px;
    }
    .seat-wrapper:hover .seat-actions,
    .seat-wrapper:focus-within .seat-actions,
    .seat-wrapper.show-actions .seat-actions {
        opacity: 1;
        visibility: visible;
    }
    .seat-actions button, .seat-actions a {
        background: none;
        border: none;
        color: #334155;
        width: 30px;
        height: 30px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background-color .15s ease;
    }
    .seat-actions button:hover, .seat-actions a:hover { background: #eef6ff; }
    .legend-wrap {
        display:flex;
        gap: 12px;
        flex-wrap: wrap;
        justify-content: center;
        margin-bottom: 10px;
    }
    .legend-item {
        font-size: 11px;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 6px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        padding: 5px 9px;
    }
    .dot { width: 9px; height: 9px; border-radius: 50%; }
    .seat-checkbox {
        width: 14px;
        height: 14px;
        accent-color: #2563eb;
        cursor: pointer;
    }
</style>