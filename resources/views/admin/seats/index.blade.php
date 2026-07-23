@extends('layout.admin')

@section('title', 'Quản lý ghế ngồi')

@section('content')

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" id="success-alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" id="error-alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
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
                <p class="text-muted mb-0">Chọn phòng để xem sơ đồ ghế, khóa/mở ghế hoặc cập nhật thông tin ghế.</p>
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">

                        <div class="fw-bold mb-2">
                            Chú thích hàng ghế (A–Z)
                        </div>

                        <div style="display:flex; flex-wrap:wrap; gap:6px;">
                            @foreach (range('A', 'Z') as $row)
                                <span
                                    style="
                    width:32px;
                    height:32px;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    border:1px solid #8cb0d4;
                    border-radius:6px;
                    font-weight:600;
                    background:#213548;
                    font-size:12px;
                ">
                                    {{ $row }}
                                </span>
                            @endforeach


                        </div>

                    </div>
                </div>
            </div>
            @if (request('room_id'))
                <a href="{{ route('admin.seats.create', ['room_id' => request('room_id')]) }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Thêm ghế mới
                </a>
            @endif
        </div>
    </div>

    @if (request('room_id'))
        <div class="col-12 mt-3">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="panel panel-sm">
                        <div class="text-muted small">Phòng</div>
                        <div class="fw-bold">
                            @php
                                $room = $rooms->firstWhere('id', request('room_id'));
                            @endphp

                            {{ $room ? $room->name . ' (' . $room->room_type . ')' : '—' }}
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="panel panel-sm">
                        <div class="text-muted small">Tổng ghế</div>
                        <div class="fw-bold">{{ count($seatsGrouped->flatten()) }}</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="panel panel-sm">
                        <div class="text-muted small">Tình trạng</div>
                        <div class="fw-bold text-success">Đã cấu hình</div>
                    </div>
                </div>
                <div class="col-md-5">
                    <form method="GET" action="{{ route('admin.seats.index') }}" id="showtimeFilterForm">
                        <input type="hidden" name="room_id" value="{{ request('room_id') }}">
                        <div class="panel panel-sm">
                            <div class="text-muted small">Suất chiếu</div>
                            <div class="fw-bold">
                                <select name="showtime_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">-- Trạng thái tĩnh --</option>
                                    @foreach($showtimes as $st)
                                        <option value="{{ $st->id }}" {{ $selectedShowtime && $selectedShowtime->id == $st->id ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::parse($st->start_time)->format('H:i d/m') }} |
                                            {{ $st->movie->title ?? 'N/A' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col">
                    <div class="summary-card summary-standard">
                        <div class="summary-label">STANDARD</div>
                        <div class="summary-value">{{ $seatsGrouped->flatten()->where('seat_type', 'STANDARD')->count() }}
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="summary-card summary-vip">
                        <div class="summary-label">VIP</div>
                        <div class="summary-value">{{ $seatsGrouped->flatten()->where('seat_type', 'VIP')->count() }}</div>
                    </div>
                </div>
                <div class="col">
                    <div class="summary-card summary-couple">
                        <div class="summary-label">COUPLE</div>
                        <div class="summary-value">{{ $seatsGrouped->flatten()->where('seat_type', 'COUPLE')->count() }}
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="summary-card summary-demo">
                        <div class="summary-label">DEMO</div>
                        <div class="summary-value">{{ $seatsGrouped->flatten()->where('seat_type', 'DEMO')->count() }}
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="summary-card summary-blocked">
                        <div class="summary-label">ĐÃ KHÓA</div>
                        <div class="summary-value">
                            {{ $seatsGrouped->flatten()->whereIn('status', ['LOCKED', 'BLOCKED'])->count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 mt-3">
            <div class="card border-0 shadow-sm seat-map-card">
                <div class="card-header bg-transparent">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="mb-1 fw-bold">Sơ đồ ghế</h5>
                            <small class="text-muted">Chọn ghế trên sơ đồ để xem thông tin và thao tác.</small>
                        </div>
                        <div class="d-flex gap-2 flex-wrap align-items-center">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="bulkSelectModeBtn">
                                <i class="bi bi-ui-checks-grid me-1"></i> Chọn nhiều
                            </button>
                            <button type="button" class="btn btn-outline-warning btn-sm" id="bulkToggleOpenBtn" data-bs-toggle="modal" data-bs-target="#bulkToggleModal" disabled>
                                <i class="bi bi-lock-fill me-1"></i> Toggle khóa/mở nhiều <span class="badge text-bg-warning ms-1" id="bulkToggleCountBadge">0</span>
                            </button>

                            <button type="button" class="btn btn-outline-danger btn-sm" id="bulkDeleteOpenBtn" data-bs-toggle="modal" data-bs-target="#bulkDeleteModal" disabled>
                                <i class="bi bi-trash3 me-1"></i> Xóa nhiều <span class="badge text-bg-danger ms-1" id="bulkDeleteCountBadge">0</span>
                            </button>

                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#bulkCreateModal">
                                <i class="bi bi-plus-square me-1"></i> Tạo nhiều
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#bulkUpdateTypeModal">
                                <i class="bi bi-arrow-left-right me-1"></i> Đổi loại ghế theo hàng
                            </button>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="legend-wrap mb-0">
                            <span class="legend-item"><span class="dot" style="background:#3b82f6"></span> STANDARD</span>
                            <span class="legend-item"><span class="dot" style="background:#eab308"></span> VIP</span>
                            <span class="legend-item"><span class="dot" style="background:#ec4899"></span> COUPLE</span>
                            <span class="legend-item"><span class="dot" style="background:#10b981"></span> DEMO</span>
                            <span class="legend-item"><span class="dot" style="background:#475569"></span> BLOCKED</span>
                            <span class="legend-item"><span class="dot" style="background:#ef4444"></span> BROKEN</span>
                            @if($selectedShowtime)
                            <span class="legend-item"><span class="dot" style="background:#22c55e"></span> Đã chọn suất: {{ \Carbon\Carbon::parse($selectedShowtime->start_time)->format('H:i d/m') }}</span>
                            @endif
                        </div>
                    <div class="bulk-mode-hint d-none" id="bulkModeHint">
                        <i class="bi bi-info-circle me-1"></i>
                        Đang bật chế độ chọn nhiều: click ghế để chọn/bỏ chọn, sau đó bấm Xóa nhiều.
                    </div>
                </div>

                <div class="card-body p-3">
                    <div class="row g-3 align-items-start">
                        <div class="col-12 col-xl-9">
                            <div class="cinema-screen">MÀN HÌNH</div>

                            <div class="map-wrapper seat-grid">
                                @foreach ($seatsGrouped as $row => $seats)
                                    <div class="seat-row">
                                        <div class="row-label">{{ $row }}</div>
                                        <div class="row-seats">
                                            @php $skipNext = false; @endphp
                                            @foreach ($seats as $index => $seat)
                                                @if ($skipNext)
                                                    @php $skipNext = false; @endphp
                                                    @continue
                                                @endif
                                                @php
                                                    $isLocked = in_array($seat->status, ['LOCKED', 'BLOCKED']);
                                                    $isCouple = ($seat->seat_type === 'COUPLE');
                                                    $nextSeat = $seats[$index + 1] ?? null;
                                                    $isPair = $isCouple && $nextSeat && $nextSeat->seat_type === 'COUPLE';
                                                @endphp

                                                @if ($isPair)
                                                    @php
                                                        $skipNext = true;
                                                        $isLocked2 = in_array($nextSeat->status, ['LOCKED', 'BLOCKED']);
                                                        $combinedLocked = $isLocked || $isLocked2;
                                                        $combinedBroken = $seat->status === 'BROKEN' || $nextSeat->status === 'BROKEN';
                                                        $combinedStatus = $combinedBroken ? 'BROKEN' : ($combinedLocked ? 'BLOCKED' : 'ACTIVE');
                                                    @endphp
                                                    <div class="seat-wrapper" data-seat-wrapper="{{ $seat->id }},{{ $nextSeat->id }}">
                                                        <input type="checkbox" class="seat-checkbox visually-hidden" name="seat_ids[]" value="{{ $seat->id }}" form="bulkDeleteForm" aria-label="Chọn ghế {{ $seat->seat_code }}">
                                                        <input type="checkbox" class="seat-checkbox visually-hidden" name="seat_ids[]" value="{{ $nextSeat->id }}" form="bulkDeleteForm" aria-label="Chọn ghế {{ $nextSeat->seat_code }}">

                                                        <button type="button"
                                                                class="seat-trigger"
                                                                data-seat-id="{{ $seat->id }},{{ $nextSeat->id }}"
                                                                data-seat-code="{{ $seat->seat_code }}-{{ $nextSeat->seat_code }}"
                                                                data-seat-type="COUPLE"
                                                                data-seat-status="{{ $combinedStatus }}"
                                                                data-seat-price="{{ number_format($seat->price + $nextSeat->price) }}đ"
                                                                data-edit-url="{{ route('admin.seats.edit', $seat->id) }}"
                                                                data-toggle-url="{{ route('admin.seats.toggle_lock', $seat->id) }}"
                                                                data-delete-url="{{ route('admin.seats.destroy', $seat->id) }}"
                                                                data-is-locked="{{ $combinedLocked ? '1' : '0' }}"
                                                                data-is-broken="{{ $combinedBroken ? '1' : '0' }}"
                                                                title="{{ $seat->seat_code }} & {{ $nextSeat->seat_code }} · {{ number_format($seat->price + $nextSeat->price) }}đ · {{ $combinedStatus }}">
                                                            <span class="seat seat-{{ $combinedStatus === 'ACTIVE' ? 'COUPLE' : $combinedStatus }}" style="width: 88px; padding: 7px 4px;">
                                                                <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                                                                    <strong>{{ $seat->seat_code }}</strong>
                                                                    <i class="bi bi-heart-fill mx-1" style="color: #fbcfe8; font-size: 11px;"></i>
                                                                    <strong>{{ $nextSeat->seat_code }}</strong>
                                                                </div>
                                                                <small>COUPLE</small>
                                                            </span>
                                                        </button>
                                                    </div>
                                                @else
                                                    <div class="seat-wrapper" data-seat-wrapper="{{ $seat->id }}">
                                                        <input type="checkbox" class="seat-checkbox visually-hidden" name="seat_ids[]" value="{{ $seat->id }}" form="bulkDeleteForm" aria-label="Chọn ghế {{ $seat->seat_code }}">

                                                        @php
                                                            $_dyn = $selectedShowtime && isset($seat->dynamic_status) ? $seat->dynamic_status : null;
                                                            $_dynLabel = '';
                                                            $_dynClass = '';
                                                            if ($_dyn === 'AVAILABLE') { $_dynLabel = 'Trống'; $_dynClass = 'dyn-available'; }
                                                            elseif ($_dyn === 'SOLD') { $_dynLabel = 'Đã bán'; $_dynClass = 'dyn-sold'; }
                                                            elseif ($_dyn === 'HELD') { $_dynLabel = 'Đang giữ'; $_dynClass = 'dyn-held'; }
                                                            elseif ($_dyn === 'BROKEN') { $_dynLabel = 'Hỏng'; $_dynClass = 'dyn-broken'; }
                                                            elseif ($_dyn === 'BLOCKED' || $_dyn === 'LOCKED') { $_dynLabel = 'Khóa'; $_dynClass = 'dyn-locked'; }
                                                        @endphp
                                                        <button type="button"
                                                                class="seat-trigger"
                                                                data-seat-id="{{ $seat->id }}"
                                                                data-seat-code="{{ $seat->seat_code }}"
                                                                data-seat-type="{{ $seat->seat_type }}"
                                                                data-seat-status="{{ $seat->status }}"
                                                                data-seat-price="{{ number_format($seat->price) }}đ"
                                                                data-edit-url="{{ route('admin.seats.edit', $seat->id) }}"
                                                                data-toggle-url="{{ route('admin.seats.toggle_lock', $seat->id) }}"
                                                                data-delete-url="{{ route('admin.seats.destroy', $seat->id) }}"
                                                                data-is-locked="{{ $isLocked ? '1' : '0' }}"
                                                                data-is-broken="{{ $seat->status === 'BROKEN' ? '1' : '0' }}"
                                                                title="{{ $seat->seat_code }} · {{ number_format($seat->price) }}đ · {{ $seat->status }}{{ $_dyn ? ' · ' . $_dynLabel : '' }}">
                                                            <span class="seat seat-{{ $seat->status === 'ACTIVE' ? $seat->seat_type : $seat->status }} {{ $_dynClass }}">
                                                                <strong>{{ $seat->seat_code }}</strong>
                                                                <small>{{ $seat->seat_type }}</small>
                                                            </span>
                                                        </button>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="col-12 col-xl-3">
                            <aside class="selected-seat-panel" id="selectedSeatPanel">
                                <div class="selected-seat-empty" id="selectedSeatEmpty">
                                    <i class="bi bi-hand-index-thumb"></i>
                                    <strong>Chọn một ghế</strong>
                                    <span>Thông tin và thao tác ghế sẽ hiển thị tại đây.</span>
                                </div>

                                <div class="selected-seat-detail d-none" id="selectedSeatDetail">
                                    <div class="selected-seat-code" id="selectedSeatCode">—</div>
                                    <div class="selected-seat-meta">
                                        <span id="selectedSeatType">—</span>
                                        <span id="selectedSeatStatus">—</span>
                                    </div>
                                    <div class="selected-seat-price" id="selectedSeatPrice">—</div>

                                    <div class="selected-seat-actions">
                                        <a href="#" class="btn btn-outline-primary btn-sm disabled" id="selectedSeatEditLink">
                                            <i class="bi bi-pencil me-1"></i>Sửa ghế
                                        </a>

                                        <form method="POST" action="" id="selectedSeatToggleForm">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-warning btn-sm w-100" id="selectedSeatToggleBtn" disabled>
                                                <i class="bi bi-lock-fill me-1"></i>Khóa ghế
                                            </button>
                                        </form>

                                        <button type="button" class="btn btn-outline-danger btn-sm" id="selectedSeatDeleteBtn" data-bs-toggle="modal" data-bs-target="#singleDeleteModal" disabled>
                                            <i class="bi bi-trash me-1"></i>Xóa ghế
                                        </button>
                                    </div>
                                </div>
                            </aside>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="modal fade" id="bulkToggleModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Toggle khóa/mở nhiều ghế</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        Bạn đã chọn <strong id="bulkToggleCount">0</strong> ghế.
                        <div class="text-muted small mt-2">
                            Ghế đang <b>ACTIVE/BLOCKED</b> sẽ được <b>toggle</b> trạng thái.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <form id="bulkToggleForm" action="{{ route('admin.seats.toggle_lock_many') }}" method="POST" style="margin:0;">
                            @csrf
                            <div id="bulkToggleSeatIdsContainer"></div>
                            <button type="submit" class="btn btn-warning" id="bulkToggleSubmit" disabled>
                                Xác nhận toggle
                            </button>
                        </form>
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
                            <button type="submit" class="btn btn-danger" id="bulkDeleteSubmit" disabled>Xác nhận
                                xóa</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="singleDeleteModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Xác nhận xóa ghế</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        Bạn có chắc chắn muốn xóa mềm ghế <strong id="singleDeleteSeatCode">—</strong> không?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <form id="singleDeleteForm" action="" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Xác nhận</button>
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

        {{-- Modal: Đổi loại ghế theo hàng --}}
        <div class="modal fade" id="bulkUpdateTypeModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Đổi loại ghế theo hàng</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('admin.seats.bulk_update_type') }}" method="POST" id="bulkUpdateTypeForm">
                            @csrf
                            <input type="hidden" name="room_id" value="{{ request('room_id') }}">

                            <div class="mb-3">
                                <label class="form-label">Hàng ghế cần đổi <span class="text-danger">*</span></label>
                                <input type="text"
                                       name="row_label"
                                       id="bulkUpdateTypeRowInput"
                                       class="form-control text-uppercase"
                                       maxlength="1"
                                       pattern="[A-Za-z]"
                                       placeholder="VD: A, B, C..."
                                       required>
                                <div class="form-text">Nhập 1 chữ cái A–Z (viết hoa).</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Loại ghế mới <span class="text-danger">*</span></label>
                                <select name="new_seat_type" class="form-select" id="bulkUpdateTypeSelect">
                                    <option value="">-- Chọn loại ghế --</option>
                                    <option value="STANDARD">STANDARD</option>
                                    <option value="VIP">VIP</option>
                                    <option value="COUPLE">COUPLE</option>
                                </select>
                                <div class="form-text">Giá ghế sẽ tự cập nhật theo loại ghế mới.</div>
                            </div>

                            <div class="alert alert-info py-2 mb-0" id="bulkUpdateTypeHint">
                                <i class="bi bi-info-circle me-1"></i>
                                Hành động này sẽ đổi loại <strong>tất cả ghế</strong> trong hàng được chọn.
                                Giá ghế sẽ tự động cập nhật theo loại mới. Thao tác này không thể hoàn tác.
                            </div>

                            <hr>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                <button type="submit" class="btn btn-info" id="bulkUpdateTypeSubmit" disabled>
                                    <i class="bi bi-arrow-left-right me-1"></i> Xác nhận đổi loại
                                </button>
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
        document.addEventListener('DOMContentLoaded', function() {
            ['success-alert', 'error-alert'].forEach(function(id) {
                const alertEl = document.getElementById(id);
                if (alertEl) {
                    setTimeout(() => {
                        alertEl.classList.remove('show');
                        setTimeout(() => alertEl.remove(), 150);
                    }, 3000);
                }
            });

            const bulkDeleteCount = document.getElementById('bulkDeleteCount');
            const bulkDeleteCountBadge = document.getElementById('bulkDeleteCountBadge');
            const bulkDeleteSubmit = document.getElementById('bulkDeleteSubmit');
            const bulkDeleteOpenBtn = document.getElementById('bulkDeleteOpenBtn');

            const bulkToggleCount = document.getElementById('bulkToggleCount');
            const bulkToggleCountBadge = document.getElementById('bulkToggleCountBadge');
            const bulkToggleSubmit = document.getElementById('bulkToggleSubmit');
            const bulkToggleOpenBtn = document.getElementById('bulkToggleOpenBtn');
            const bulkToggleSeatIdsContainer = document.getElementById('bulkToggleSeatIdsContainer');

            const bulkSelectModeBtn = document.getElementById('bulkSelectModeBtn');
            const bulkModeHint = document.getElementById('bulkModeHint');

            // Toggle open modal handlers (update count + payload on open)
            if (bulkToggleOpenBtn) {
                bulkToggleOpenBtn.addEventListener('click', function () {
                    updateBulkToggleState();
                });
            }

            const selectedSeatEmpty = document.getElementById('selectedSeatEmpty');
            const selectedSeatDetail = document.getElementById('selectedSeatDetail');
            const selectedSeatCode = document.getElementById('selectedSeatCode');
            const selectedSeatType = document.getElementById('selectedSeatType');
            const selectedSeatStatus = document.getElementById('selectedSeatStatus');
            const selectedSeatPrice = document.getElementById('selectedSeatPrice');
            const selectedSeatEditLink = document.getElementById('selectedSeatEditLink');
            const selectedSeatToggleForm = document.getElementById('selectedSeatToggleForm');
            const selectedSeatToggleBtn = document.getElementById('selectedSeatToggleBtn');
            const selectedSeatDeleteBtn = document.getElementById('selectedSeatDeleteBtn');
            const singleDeleteForm = document.getElementById('singleDeleteForm');
            const singleDeleteSeatCode = document.getElementById('singleDeleteSeatCode');
            let isBulkMode = false;

            function updateBulkDeleteState() {
                const selectedCount = document.querySelectorAll('.seat-checkbox:checked').length;
                if (bulkDeleteCount) bulkDeleteCount.textContent = selectedCount;
                if (bulkDeleteCountBadge) bulkDeleteCountBadge.textContent = selectedCount;
                if (bulkDeleteSubmit) bulkDeleteSubmit.disabled = selectedCount === 0;
                if (bulkDeleteOpenBtn) bulkDeleteOpenBtn.disabled = selectedCount === 0;
            }

            function updateBulkToggleState() {
                const selectedCount = document.querySelectorAll('.seat-checkbox:checked').length;
                if (bulkToggleCount) bulkToggleCount.textContent = selectedCount;
                if (bulkToggleCountBadge) bulkToggleCountBadge.textContent = selectedCount;
                if (bulkToggleSeatIdsContainer) bulkToggleSeatIdsContainer.innerHTML = '';
                if (bulkToggleSubmit) bulkToggleSubmit.disabled = selectedCount === 0;
                if (bulkToggleOpenBtn) bulkToggleOpenBtn.disabled = selectedCount === 0;

                if (!bulkToggleSeatIdsContainer) return;
                // Build hidden inputs for toggle many
                const checked = Array.from(document.querySelectorAll('.seat-checkbox:checked'));
                checked.forEach(function(cb){
                    const id = cb.value;
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'seat_ids[]';
                    input.value = id;
                    bulkToggleSeatIdsContainer.appendChild(input);
                });
            }


            function clearActiveSeat() {
                document.querySelectorAll('.seat-wrapper.is-selected').forEach(function(wrapper) {
                    wrapper.classList.remove('is-selected');
                });
            }

            function setBulkMode(enabled) {
                isBulkMode = enabled;
                document.body.classList.toggle('seat-bulk-mode', enabled);
                if (bulkModeHint) bulkModeHint.classList.toggle('d-none', !enabled);

                if (bulkSelectModeBtn) {
                    bulkSelectModeBtn.classList.toggle('btn-secondary', enabled);
                    bulkSelectModeBtn.classList.toggle('btn-outline-secondary', !enabled);
                    bulkSelectModeBtn.innerHTML = enabled
                        ? '<i class="bi bi-x-circle me-1"></i> Thoát chọn nhiều'
                        : '<i class="bi bi-ui-checks-grid me-1"></i> Chọn nhiều';
                }
            }

            function toggleSeatForBulk(wrapper, forceState = null) {
                const checkboxes = wrapper.querySelectorAll('.seat-checkbox');
                if (checkboxes.length === 0) return;

                const firstCheckbox = checkboxes[0];
                const newState = forceState !== null ? forceState : !firstCheckbox.checked;

                checkboxes.forEach(cb => cb.checked = newState);
                wrapper.classList.toggle('is-bulk-selected', newState);

                updateBulkDeleteState();
                updateBulkToggleState();
            }


            document.querySelectorAll('.seat-trigger').forEach(function(trigger) {
                trigger.addEventListener('click', function() {
                    const wrapper = trigger.closest('.seat-wrapper');
                    const isLocked = trigger.dataset.isLocked === '1';
                    const isBroken = trigger.dataset.isBroken === '1';

                    if (isBulkMode) {
                        toggleSeatForBulk(wrapper);
                        return;
                    }

                    clearActiveSeat();
                    wrapper.classList.add('is-selected');

                    if (selectedSeatEmpty) selectedSeatEmpty.classList.add('d-none');
                    if (selectedSeatDetail) selectedSeatDetail.classList.remove('d-none');

                    if (selectedSeatCode) selectedSeatCode.textContent = trigger.dataset.seatCode;
                    if (selectedSeatType) selectedSeatType.textContent = trigger.dataset.seatType;
                    if (selectedSeatStatus) selectedSeatStatus.textContent = trigger.dataset.seatStatus;
                    if (selectedSeatPrice) selectedSeatPrice.textContent = trigger.dataset.seatPrice;

                    if (selectedSeatEditLink) {
                        selectedSeatEditLink.href = trigger.dataset.editUrl;
                        selectedSeatEditLink.classList.remove('disabled');
                    }

                    if (selectedSeatToggleForm) selectedSeatToggleForm.action = trigger.dataset.toggleUrl;
                    if (selectedSeatToggleBtn) {
                        selectedSeatToggleBtn.disabled = isBroken;
                        selectedSeatToggleBtn.innerHTML = isLocked
                            ? '<i class="bi bi-unlock-fill me-1"></i>Mở khóa'
                            : '<i class="bi bi-lock-fill me-1"></i>Khóa ghế';
                    }

                    if (singleDeleteForm) singleDeleteForm.action = trigger.dataset.deleteUrl;
                    if (singleDeleteSeatCode) singleDeleteSeatCode.textContent = trigger.dataset.seatCode;
                    if (selectedSeatDeleteBtn) selectedSeatDeleteBtn.disabled = false;

                    updateBulkDeleteState();
                });
            });

            if (bulkSelectModeBtn) {
                bulkSelectModeBtn.addEventListener('click', function() {
                    setBulkMode(!isBulkMode);
                });
            }

            setBulkMode(false);
            updateBulkDeleteState();

            // ── Bulk Update Type: enable submit only when both row + type selected ──
            const bulkUpdateTypeRow = document.getElementById('bulkUpdateTypeRowInput');
            const bulkUpdateTypeSelect = document.getElementById('bulkUpdateTypeSelect');
            const bulkUpdateTypeSubmit = document.getElementById('bulkUpdateTypeSubmit');

            function checkBulkUpdateTypeForm() {
                const rowVal = bulkUpdateTypeRow ? bulkUpdateTypeRow.value.trim() : '';
                const typeVal = bulkUpdateTypeSelect ? bulkUpdateTypeSelect.value : '';
                if (bulkUpdateTypeSubmit) {
                    bulkUpdateTypeSubmit.disabled = !(rowVal.length > 0 && typeVal.length > 0);
                }
            }

            if (bulkUpdateTypeRow) {
                bulkUpdateTypeRow.addEventListener('input', checkBulkUpdateTypeForm);
                bulkUpdateTypeRow.addEventListener('change', checkBulkUpdateTypeForm);
            }
            if (bulkUpdateTypeSelect) {
                bulkUpdateTypeSelect.addEventListener('change', checkBulkUpdateTypeForm);
            }

            // Reset form khi modal đóng
            const bulkUpdateTypeModal = document.getElementById('bulkUpdateTypeModal');
            if (bulkUpdateTypeModal) {
                bulkUpdateTypeModal.addEventListener('hidden.bs.modal', function () {
                    const form = document.getElementById('bulkUpdateTypeForm');
                    if (form) form.reset();
                    if (bulkUpdateTypeSubmit) bulkUpdateTypeSubmit.disabled = true;
                });
            }
        });
    </script>
@endpush

<style>
    .panel-sm {
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 16px;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .45);
    }

    .summary-card {
        border-radius: 14px;
        padding: 14px 16px;
        color: #0f172a;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .22), 0 8px 18px rgba(15, 23, 42, .06);
    }

    .summary-standard {
        background: linear-gradient(90deg, #eef6ff 0%, #dbeafe 100%);
    }

    .summary-vip {
        background: linear-gradient(90deg, #fff7db 0%, #fde68a 100%);
    }

    .summary-couple {
        background: linear-gradient(90deg, #fdf2f8 0%, #fce7f3 100%);
    }

    .summary-demo {
        background: linear-gradient(90deg, #ecfdf5 0%, #a7f3d0 100%);
    }

    .summary-blocked {
        background: linear-gradient(90deg, #f8fafc 0%, #e2e8f0 100%);
    }

    .summary-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #64748b;
    }

    .summary-value {
        font-size: 1.6rem;
        font-weight: 800;
    }

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
        box-shadow: 0 3px 10px rgba(15, 23, 42, .08);
    }

    .seat-map-card {
        overflow: hidden;
    }

    .seat-trigger {
        border: 0;
        padding: 0;
        background: transparent;
        display: inline-flex;
        border-radius: 16px;
    }

    .seat {
        position: relative;
        width: 58px;
        min-height: 52px;
        border-radius: 14px;
        display: grid;
        place-items: center;
        padding: 7px 6px;
        font-size: 11px;
        font-weight: 800;
        cursor: pointer;
        color: #fff;
        transition: transform .14s ease, box-shadow .14s ease, filter .14s ease, outline-color .14s ease;
        box-shadow: inset 0 -3px 0 rgba(0, 0, 0, .16), 0 4px 10px rgba(15, 23, 42, .08);
        border: 1px solid rgba(255, 255, 255, .22);
        outline: 0 solid transparent;
    }

    .seat strong,
    .seat small {
        line-height: 1.05;
    }

    .seat small {
        font-size: 9px;
        opacity: .78;
    }

    .seat-trigger:hover .seat {
        transform: translateY(-2px);
        filter: brightness(1.05);
    }

    .seat-wrapper.is-selected .seat {
        outline: 3px solid rgba(96, 165, 250, .95);
        box-shadow: 0 0 0 6px rgba(37, 99, 235, .16), inset 0 -3px 0 rgba(0, 0, 0, .16), 0 12px 24px rgba(15, 23, 42, .18);
    }

    .seat-wrapper.is-bulk-selected .seat::after {
        content: "✓";
        position: absolute;
        top: -8px;
        right: -8px;
        width: 20px;
        height: 20px;
        display: grid;
        place-items: center;
        border-radius: 999px;
        background: #2563eb;
        color: #fff;
        font-size: 12px;
        box-shadow: 0 6px 14px rgba(37, 99, 235, .3);
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
        width: 88px;
        border-color: #f9a8d4;
    }

    .seat-LOCKED,
    .seat-BLOCKED {
        background: linear-gradient(180deg, #94a3b8 0%, #475569 100%);
        color: #eef2ff;
    }

    .seat-BROKEN {
        background: linear-gradient(180deg, #fecaca 0%, #ef4444 100%);
        color: #fff7ed;
    }

    .seat-DEMO {
        background: linear-gradient(180deg, #a7f3d0 0%, #10b981 100%);
        color: #064e3b;
        border-color: #6ee7b7;
    }

    .selected-seat-panel {
        position: sticky;
        top: 92px;
        min-height: 320px;
        border: 1px solid rgba(148, 163, 184, .28);
        border-radius: 18px;
        padding: 18px;
        background: linear-gradient(180deg, rgba(15, 23, 42, .72), rgba(15, 23, 42, .48));
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .06), 0 18px 36px rgba(0, 0, 0, .14);
    }

    .selected-seat-empty {
        min-height: 280px;
        display: grid;
        place-items: center;
        align-content: center;
        gap: 10px;
        text-align: center;
        color: #94a3b8;
    }

    .selected-seat-empty i {
        font-size: 2rem;
        color: #60a5fa;
    }

    .selected-seat-empty strong {
        color: #e5e7eb;
        font-size: 1.05rem;
    }

    .selected-seat-empty span {
        max-width: 210px;
        font-size: .86rem;
    }

    .selected-seat-code {
        width: 86px;
        height: 76px;
        display: grid;
        place-items: center;
        margin-bottom: 14px;
        border-radius: 20px;
        background: linear-gradient(180deg, #dbeafe 0%, #60a5fa 100%);
        color: #0f172a;
        font-size: 1.4rem;
        font-weight: 900;
        box-shadow: 0 14px 30px rgba(37, 99, 235, .22);
    }

    .selected-seat-meta {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 10px;
    }

    .selected-seat-meta span,
    .selected-seat-price {
        border: 1px solid rgba(148, 163, 184, .24);
        border-radius: 999px;
        padding: 6px 10px;
        color: #dbeafe;
        background: rgba(15, 23, 42, .45);
        font-size: .82rem;
        font-weight: 700;
    }

    .selected-seat-price {
        display: inline-flex;
        margin-bottom: 18px;
    }

    .selected-seat-actions {
        display: grid;
        gap: 10px;
    }

    .legend-wrap {
        display: flex;
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

    .bulk-mode-hint {
        margin-top: 12px;
        padding: 10px 12px;
        border-radius: 12px;
        color: #1e3a8a;
        background: linear-gradient(90deg, #eff6ff 0%, #dbeafe 100%);
        border: 1px solid #bfdbfe;
        font-size: .86rem;
        font-weight: 600;
        text-align: center;
    }

    .seat-bulk-mode .seat-trigger:hover .seat {
        outline: 3px solid rgba(37, 99, 235, .35);
    }

    .dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
    }

    .seat-checkbox {
        width: 14px;
        height: 14px;
        accent-color: #2563eb;
        cursor: pointer;
    }

    .row-seats {
        display: flex;
        gap: 10px;
        align-items: flex-start;
    }

    @media (max-width: 1199.98px) {
        .selected-seat-panel {
            position: static;
        }
    }

    /* ===== Dynamic status colors (giống bên customer) ===== */
    .dyn-sold { background: linear-gradient(180deg, #fecaca 0%, #ef4444 100%) !important; }
    .dyn-held { background: linear-gradient(180deg, #fde68a 0%, #f59e0b 100%) !important; }
    .dyn-locked { background: linear-gradient(180deg, #94a3b8 0%, #475569 100%) !important; }
    .dyn-broken { background: linear-gradient(180deg, #fecaca 0%, #ef4444 100%) !important; }

</style>
