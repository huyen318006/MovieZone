@extends('layout.admin')

@section('title', 'Sơ đồ ghế phòng chiếu')

@section('content')

<div class="col-12">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h3 class="mb-1">Sơ đồ ghế - {{ $room->name }}</h3>
            <p class="text-muted mb-0">Xem sơ đồ ghế hiện tại của phòng {{ $room->name }}.</p>
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

<div class="col-12 mt-3">
    <div class="row g-3">
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
    </div>
</div>


<div class="col-12 mt-3">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent">
            <div>
                <div class="fw-semibold">
                    <i class="bi bi-grid-3x3-gap me-2"></i>Sơ đồ ghế hiện tại
                </div>
                <small class="text-muted">Màn hình này dùng để xem sơ đồ ghế hiện tại của phòng.</small>
            </div>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-3 mb-4">
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
                                        $statusClass = match ($seat->status) {
                                            'ACTIVE' => 'seat-status-active',
                                            'BROKEN' => 'seat-status-broken',
                                            'LOCKED' => 'seat-status-locked',
                                            default => 'seat-status-default',
                                        };

                                        $typeClass = match ($seat->seat_type) {
                                            'VIP' => 'seat-type-vip',
                                            'COUPLE' => 'seat-type-couple',
                                            default => 'seat-type-standard',
                                        };
                                        
                                        $isCouple = $seat->seat_type === 'COUPLE' ? 'is-couple' : '';
                                    @endphp
                                    <div class="room-seat-cell {{ $statusClass }} {{ $isCouple }}" title="{{ $seat->seat_code }} • {{ $seat->seat_type }} • {{ $seat->status }}">
                                        <strong class="{{ $typeClass }}">{{ $seat->seat_code }}</strong>
                                        <small class="{{ $typeClass }}">{{ $seat->seat_type }}</small>
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
