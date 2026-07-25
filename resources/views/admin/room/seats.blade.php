@extends('layout.admin')

@section('title', 'Sơ đồ ghế phòng chiếu')

@section('content')

<div class="col-12">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h3 class="mb-1">Sơ đồ ghế - {{ $room->name }}</h3>
            <p class="text-muted mb-0">Xem sơ đồ ghế và trạng thái thực tế của phòng {{ $room->name }}.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.rooms.edit', $room) }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil"></i> Sửa phòng
            </a>
            <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Quay lại danh sách
            </a>
        </div>
    </div>
</div>

{{-- Thông tin cơ bản phòng --}}
<div class="col-12 mt-3">
    <div class="row g-3 align-items-end">
        <div class="col-auto">
            <div class="card card-body py-2 px-3">
                <small class="text-muted">Loại phòng</small>
                <span class="fw-semibold">{{ $room->room_type }}</span>
            </div>
        </div>
        <div class="col-auto">
            <div class="card card-body py-2 px-3">
                <small class="text-muted">Sức chứa khai báo</small>
                <span class="fw-semibold">{{ $room->total_seats }} ghế</span>
            </div>
        </div>
        <div class="col-auto">
            <div class="card card-body py-2 px-3">
                <small class="text-muted">Ghế đã cấu hình</small>
                <span class="fw-semibold">{{ $room->seats->count() }} ghế</span>
            </div>
        </div>
        <div class="col-auto">
            <div class="card card-body py-2 px-3">
                <small class="text-muted">Trạng thái phòng</small>
                <span class="fw-semibold">
                    @if($room->status === 'ACTIVE')
                        <span class="badge text-bg-success">Hoạt động</span>
                    @elseif($room->status === 'MAINTENANCE')
                        <span class="badge text-bg-warning">Bảo trì</span>
                    @else
                        <span class="badge text-bg-secondary">Đã ẩn</span>
                    @endif
                </span>
            </div>
        </div>

        {{-- Dropdown chọn suất chiếu --}}
        <div class="col-auto ms-auto">
            <form method="GET" action="{{ route('admin.rooms.seats', $room) }}" id="showtimeForm">
                <div class="d-flex align-items-center gap-2">
                    <label for="showtimeSelect" class="text-muted small">Suất chiếu:</label>
                    <select name="showtime_id" id="showtimeSelect" class="form-select form-select-sm" style="min-width: 280px;" onchange="this.form.submit()">
                        <option value="">-- Không chọn suất --</option>
                        @foreach($showtimes as $st)
                            <option value="{{ $st->id }}" {{ $selectedShowtime && $selectedShowtime->id == $st->id ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::parse($st->start_time)->format('H:i - d/m/Y') }} |
                                {{ $st->movie->title ?? 'N/A' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Thông tin suất chiếu đang chọn --}}
@if($selectedShowtime)
<div class="col-12 mt-2">
    <div class="card border-0 shadow-sm bg-primary bg-opacity-10">
        <div class="card-body py-2 px-3 d-flex align-items-center gap-3 flex-wrap">
            <span class="badge text-bg-primary px-3 py-2">
                <i class="bi bi-film me-1"></i>{{ $selectedShowtime->movie->title ?? 'N/A' }}
            </span>
            <span class="badge text-bg-info px-3 py-2">
                <i class="bi bi-clock me-1"></i>
                {{ \Carbon\Carbon::parse($selectedShowtime->start_time)->format('H:i') }}
                -
                {{ \Carbon\Carbon::parse($selectedShowtime->end_time)->format('H:i') }}
            </span>
            <span class="badge text-bg-secondary px-3 py-2">
                <i class="bi bi-calendar me-1"></i>
                {{ \Carbon\Carbon::parse($selectedShowtime->start_time)->format('d/m/Y') }}
            </span>
        </div>
    </div>
</div>
@endif

<div class="col-12 mt-3">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent">
            <div>
                <div class="fw-semibold">
                    <i class="bi bi-grid-3x3-gap me-2"></i>Sơ đồ ghế
                    @if($selectedShowtime)
                        <span class="badge text-bg-info ms-2">Trạng thái theo suất chiếu</span>
                    @else
                        <span class="badge text-bg-secondary ms-2">Trạng thái tĩnh (mặc định)</span>
                    @endif
                </div>
                <small class="text-muted">
                    @if($selectedShowtime)
                        Hiển thị trạng thái thực tế của ghế theo suất chiếu đã chọn.
                        @if($showtimes->isNotEmpty())
                            Chọn suất chiếu khác từ dropdown bên trên để xem.
                        @endif
                    @else
                        Hiển thị trạng thái tĩnh mặc định của ghế (ACTIVE/BROKEN/LOCKED).
                        @if($showtimes->isNotEmpty())
                            Chọn một suất chiếu từ dropdown bên trên để xem trạng thái thực tế.
                        @else
                            Phòng này hiện chưa có suất chiếu sắp tới.
                        @endif
                    @endif
                </small>
            </div>
        </div>
        <div class="card-body">
            {{-- Legend --}}
            <div class="d-flex flex-wrap gap-3 mb-4">
                @if($selectedShowtime)
                    {{-- Dynamic status legend --}}
                    <div class="d-flex align-items-center gap-2">
                        <span class="room-seat-preview seat-dynamic-available"></span>
                        <span class="small text-muted">AVAILABLE</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="room-seat-preview seat-dynamic-sold"></span>
                        <span class="small text-muted">SOLD</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="room-seat-preview seat-dynamic-held"></span>
                        <span class="small text-muted">HELD</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="room-seat-preview seat-dynamic-locked"></span>
                        <span class="small text-muted">LOCKED</span>
                    </div>
                @else
                    {{-- Static status legend (như cũ) --}}
                    <div class="d-flex align-items-center gap-2">
                        <span class="room-seat-preview seat-status-active"></span>
                        <span class="small text-muted">ACTIVE</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="room-seat-preview seat-status-broken"></span>
                        <span class="small text-muted">BROKEN</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="room-seat-preview seat-status-locked"></span>
                        <span class="small text-muted">LOCKED</span>
                    </div>
                @endif
                <div class="vr d-none d-md-block"></div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge text-bg-secondary border">STANDARD</span>
                    <span class="badge text-bg-primary">VIP</span>
                    <span class="badge text-bg-warning">COUPLE</span>
                </div>
            </div>

            <div class="room-screen-panel text-center mb-4">
                <i class="bi bi-display me-2"></i>Màn hình
            </div>

            @if($seatRows->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="bi bi-grid-3x3-gap fs-1 d-block mb-2"></i>
                    Phòng này chưa có ghế nào được cấu hình.
                    <div class="mt-2">Phòng này hiện chưa có sơ đồ ghế.</div>
                </div>
            @else
                <div class="room-seat-map-wrapper">
                    @foreach($seatRows as $rowLabel => $seats)
                        <div class="room-seat-row">
                            <div class="room-seat-row-label">{{ $rowLabel }}</div>
                            <div class="room-seat-row-items">
                                @foreach($seats as $seat)
                                    @php
                                        // Xác định class hiển thị
                                        if ($selectedShowtime && $seat->dynamic_status) {
                                            // Trạng thái động theo suất chiếu
                                            $dStatus = $seat->dynamic_status;
                                            if ($dStatus === 'AVAILABLE') {
                                                $displayClass = 'seat-dynamic-available';
                                                $displayLabel = 'Trống';
                                            } elseif ($dStatus === 'SOLD') {
                                                $displayClass = 'seat-dynamic-sold';
                                                $displayLabel = 'Đã bán';
                                            } elseif ($dStatus === 'HELD') {
                                                $displayClass = 'seat-dynamic-held';
                                                $displayLabel = 'Đang giữ';
                                            } elseif ($dStatus === 'BROKEN') {
                                                $displayClass = 'seat-dynamic-broken';
                                                $displayLabel = 'Hỏng';
                                            } elseif ($dStatus === 'BLOCKED' || $dStatus === 'LOCKED') {
                                                $displayClass = 'seat-dynamic-locked';
                                                $displayLabel = 'Khóa';
                                            } elseif ($dStatus === 'NOT_SYNCED') {
                                                $displayClass = 'seat-status-default';
                                                $displayLabel = '?';
                                            } else {
                                                $displayClass = 'seat-dynamic-' . strtolower($dStatus);
                                                $displayLabel = $dStatus;
                                            }
                                        } else {
                                            // Trạng thái tĩnh mặc định
                                            $displayClass = match ($seat->status) {
                                                'ACTIVE' => 'seat-status-active',
                                                'BROKEN' => 'seat-status-broken',
                                                'LOCKED' => 'seat-status-locked',
                                                default => 'seat-status-default',
                                            };
                                            $displayLabel = $seat->status;
                                        }

                                        $typeClass = match ($seat->seat_type) {
                                            'VIP' => 'seat-type-vip',
                                            'COUPLE' => 'seat-type-couple',
                                            default => 'seat-type-standard',
                                        };

                                        $isCouple = $seat->seat_type === 'COUPLE' ? 'is-couple' : '';

                                        // Title tooltip
                                        $titleInfo = $seat->seat_code . ' • ' . $seat->seat_type;
                                        if ($selectedShowtime && $seat->dynamic_status) {
                                            $titleInfo .= ' • ' . $displayLabel;
                                        } else {
                                            $titleInfo .= ' • ' . $seat->status;
                                        }
                                    @endphp
                                    <div class="room-seat-cell {{ $displayClass }} {{ $isCouple }}" title="{{ $titleInfo }}">
                                        <strong class="{{ $typeClass }}">{{ $seat->seat_code }}</strong>
                                        <small class="{{ $typeClass }}">{{ $seat->seat_type }}</small>
                                        @if($selectedShowtime && $seat->dynamic_status)
                                            <span class="badge seat-badge-dynamic {{ $displayClass }}-badge">{{ $displayLabel }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

@endsection

@push('scripts')
<style>
/* ===== Dynamic status colors for admin seat map ===== */

/* AVAILABLE - xanh lá */
.seat-dynamic-available {
    background: rgba(34, 197, 94, 0.15) !important;
    border-color: #22c55e !important;
}
.seat-dynamic-available strong,
.seat-dynamic-available small {
    color: #22c55e !important;
}
.seat-dynamic-available-badge {
    background: rgba(34, 197, 94, 0.2) !important;
    color: #22c55e !important;
    font-size: 9px !important;
    padding: 1px 5px !important;
    margin-top: 2px;
}

/* SOLD - đỏ */
.seat-dynamic-sold {
    background: rgba(239, 68, 68, 0.2) !important;
    border-color: #ef4444 !important;
    cursor: not-allowed !important;
    opacity: 0.8;
}
.seat-dynamic-sold strong,
.seat-dynamic-sold small {
    color: #ef4444 !important;
}
.seat-dynamic-sold-badge {
    background: rgba(239, 68, 68, 0.25) !important;
    color: #ef4444 !important;
    font-size: 9px !important;
    padding: 1px 5px !important;
    margin-top: 2px;
}

/* HELD - cam */
.seat-dynamic-held {
    background: rgba(245, 158, 11, 0.15) !important;
    border-color: #f59e0b !important;
}
.seat-dynamic-held strong,
.seat-dynamic-held small {
    color: #f59e0b !important;
}
.seat-dynamic-held-badge {
    background: rgba(245, 158, 11, 0.2) !important;
    color: #f59e0b !important;
    font-size: 9px !important;
    padding: 1px 5px !important;
    margin-top: 2px;
}

/* LOCKED/BLOCKED - xám đen */
.seat-dynamic-locked {
    background: rgba(107, 114, 128, 0.2) !important;
    border-color: #6b7280 !important;
    cursor: not-allowed !important;
    opacity: 0.6;
}
.seat-dynamic-locked strong,
.seat-dynamic-locked small {
    color: #9ca3af !important;
}
.seat-dynamic-locked-badge {
    background: rgba(107, 114, 128, 0.25) !important;
    color: #9ca3af !important;
    font-size: 9px !important;
    padding: 1px 5px !important;
    margin-top: 2px;
}

/* BROKEN - đỏ sẫm */
.seat-dynamic-broken {
    background: rgba(220, 38, 38, 0.2) !important;
    border-color: #dc2626 !important;
    cursor: not-allowed !important;
    opacity: 0.6;
}
.seat-dynamic-broken strong,
.seat-dynamic-broken small {
    color: #dc2626 !important;
}
.seat-dynamic-broken-badge {
    background: rgba(220, 38, 38, 0.25) !important;
    color: #dc2626 !important;
    font-size: 9px !important;
    padding: 1px 5px !important;
    margin-top: 2px;
}

/* Preview dots for dynamic legend */
.room-seat-preview {
    display: inline-block;
    width: 18px;
    height: 18px;
    border-radius: 4px;
    border: 2px solid;
    flex-shrink: 0;
}
.room-seat-preview.seat-dynamic-available {
    background: rgba(34, 197, 94, 0.15);
    border-color: #22c55e;
}
.room-seat-preview.seat-dynamic-sold {
    background: rgba(239, 68, 68, 0.2);
    border-color: #ef4444;
}
.room-seat-preview.seat-dynamic-held {
    background: rgba(245, 158, 11, 0.15);
    border-color: #f59e0b;
}
.room-seat-preview.seat-dynamic-locked {
    background: rgba(107, 114, 128, 0.2);
    border-color: #6b7280;
}

/* Badge trên mỗi ghế */
.seat-badge-dynamic {
    display: block;
    width: 100%;
    text-align: center;
    font-weight: 600;
    line-height: 1.1;
    border-radius: 3px;
}

/* Đảm bảo room-seat-cell hỗ trợ flex column */
.room-seat-cell {
    display: inline-flex !important;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-width: 70px;
    min-height: 55px;
    padding: 6px 8px !important;
    border-radius: 8px !important;
    border: 2px solid #374151;
    margin: 3px !important;
    cursor: default !important;
    transition: all 0.2s ease;
}
.room-seat-cell strong {
    font-size: 13px;
    line-height: 1.2;
}
.room-seat-cell small {
    font-size: 9px;
    line-height: 1.1;
    opacity: 0.8;
}
</style>
@endpush
