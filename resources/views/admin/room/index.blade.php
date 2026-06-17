@extends('layout.admin')

@section('title', 'Quản lý phòng chiếu')

@section('content')

{{-- Flash messages --}}
@if(session('success'))
    <div id="success-alert" class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle-fill me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div id="error-alert" class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

{{-- Header --}}
<div class="col-12">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h3 class="mb-1">Quản lý phòng chiếu</h3>
            <p class="text-muted mb-0">Chọn rạp để quản lý phòng chiếu, trạng thái và sơ đồ ghế hiện tại.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.rooms.create', ['cinema' => $selectedCinema?->id]) }}" class="btn btn-primary {{ $selectedCinema && $selectedCinema->status === 'ACTIVE' ? '' : 'disabled' }}">
                <i class="bi bi-plus-lg"></i>
                Thêm phòng chiếu
            </a>
        </div>
    </div>
</div>

{{-- Cinema selector --}}
<div class="col-12 mt-3">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <div class="fw-semibold">Danh sách rạp</div>
                <small class="text-muted">UC-ADM-03 tách riêng quản lý phòng khỏi quản lý rạp.</small>
            </div>
            <form method="GET" action="{{ route('admin.rooms.index') }}" class="d-flex gap-2 flex-wrap align-items-center">
                <select name="cinema" class="form-select form-select-sm" style="min-width: 260px;" onchange="this.form.submit()">
                    <option value="">Chọn rạp để xem phòng</option>
                    @foreach($cinemas as $cinema)
                        <option value="{{ $cinema->id }}" {{ request('cinema') == $cinema->id ? 'selected' : '' }}>
                            {{ $cinema->name }} - {{ $cinema->city }} ({{ $cinema->rooms_count }} phòng)
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-building"></i> Chọn rạp
                </button>
                <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-clockwise"></i> Làm mới
                </a>
            </form>
        </div>

        @if($selectedCinema)
            <div class="card-body">
                <div class="row g-3 align-items-stretch">
                    <div class="col-md-4">
                        <div class="p-3 rounded bg-light h-100">
                            <div class="text-muted small">Rạp đang chọn</div>
                            <div class="fw-bold fs-5">{{ $selectedCinema->name }}</div>
                            <div class="text-muted">{{ $selectedCinema->address }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 rounded bg-light h-100">
                            <div class="text-muted small">Khu vực</div>
                            <div class="fw-semibold">{{ $selectedCinema->district ?? '—' }}</div>
                            <div class="text-muted">{{ $selectedCinema->city }}</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="p-3 rounded bg-light h-100">
                            <div class="text-muted small">Trạng thái</div>
                            @if($selectedCinema->status === 'ACTIVE')
                                <span class="badge text-bg-success">Hoạt động</span>
                            @else
                                <span class="badge text-bg-secondary">Đã ẩn</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 rounded bg-light h-100">
                            <div class="text-muted small">Hotline</div>
                            <div class="fw-semibold">{{ $selectedCinema->hotline ?? 'Chưa cập nhật' }}</div>
                        </div>
                    </div>
                </div>

                @if($selectedCinema->status !== 'ACTIVE')
                    <div class="alert alert-warning mt-3 mb-0">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Rạp này đang bị ẩn nên không thể thêm phòng chiếu mới vào rạp.
                    </div>
                @endif
            </div>
        @else
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-building fs-1 d-block mb-2"></i>
                Vui lòng chọn một rạp để xem danh sách phòng chiếu.
            </div>
        @endif
    </div>
</div>

@if($selectedCinema)
    {{-- Room filters and table --}}
    <div class="col-12 mt-3">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <div class="fw-semibold">Phòng chiếu thuộc {{ $selectedCinema->name }}</div>
                    <small class="text-muted">Quản lý thêm, sửa, ẩn, khôi phục và xem sơ đồ ghế.</small>
                </div>
                <form method="GET" action="{{ route('admin.rooms.index') }}" class="d-flex gap-2 flex-wrap align-items-center">
                    <input type="hidden" name="cinema" value="{{ $selectedCinema->id }}">

                    <input type="text"
                           name="search"
                           class="form-control form-control-sm"
                           style="width: 210px;"
                           placeholder="Tên phòng, loại phòng..."
                           value="{{ request('search') }}">

                    <select name="status" class="form-select form-select-sm" style="width: 180px;" onchange="this.form.submit()">
                        <option value="">Tất cả trạng thái</option>
                        <option value="ACTIVE" {{ request('status') == 'ACTIVE' ? 'selected' : '' }}>Hoạt động</option>
                        <option value="INACTIVE" {{ request('status') == 'INACTIVE' ? 'selected' : '' }}>Đã ẩn</option>
                        <option value="MAINTENANCE" {{ request('status') == 'MAINTENANCE' ? 'selected' : '' }}>Bảo trì</option>
                    </select>

                    <button type="submit" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-search"></i>
                    </button>

                    <a href="{{ route('admin.rooms.index', ['cinema' => $selectedCinema->id]) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-clockwise"></i> Làm mới
                    </a>
                </form>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" style="min-width: 1000px;">
                        <thead>
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th>Tên phòng</th>
                                <th>Loại phòng</th>
                                <th>Sức chứa</th>
                                <th>Ghế đã cấu hình</th>
                                <th>Suất chiếu tương lai</th>
                                <th>Trạng thái</th>
                                <th style="width: 330px;">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rooms as $i => $room)
                                <tr>
                                    <td>{{ method_exists($rooms, 'firstItem') ? $rooms->firstItem() + $i : $i + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $room->name }}</div>
                                        <small class="text-muted">{{ $room->cinema?->name }}</small>
                                    </td>
                                    <td>
                                        <span class="badge text-bg-primary">{{ $room->room_type }}</span>
                                    </td>
                                    <td>{{ $room->total_seats }} ghế</td>
                                    <td>
                                        <span class="badge text-bg-info">
                                            <i class="bi bi-grid-3x3-gap me-1"></i>{{ $room->seats_count }} ghế
                                        </span>
                                        @if($room->seats_count != $room->total_seats)
                                            <div class="small text-warning mt-1">
                                                <i class="bi bi-exclamation-triangle me-1"></i>Chưa khớp sức chứa
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($room->upcoming_showtimes_count > 0)
                                            <span class="badge text-bg-warning">
                                                {{ $room->upcoming_showtimes_count }} suất
                                            </span>
                                        @else
                                            <span class="text-muted">Không có</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($room->status === 'ACTIVE')
                                            <span class="badge text-bg-success">Hoạt động</span>
                                        @elseif($room->status === 'MAINTENANCE')
                                            <span class="badge text-bg-warning">Bảo trì</span>
                                        @else
                                            <span class="badge text-bg-secondary">Đã ẩn</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <a href="{{ route('admin.rooms.seats', $room) }}" class="btn btn-outline-info btn-sm">
                                                <i class="bi bi-grid-3x3-gap"></i> Sơ đồ ghế
                                            </a>
                                            <a href="{{ route('admin.rooms.edit', $room) }}" class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-pencil"></i> Sửa
                                            </a>

                                            @if($room->status === 'INACTIVE')
                                                <form method="POST" action="{{ route('admin.rooms.restore', $room) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-success btn-sm">
                                                        <i class="bi bi-arrow-counterclockwise"></i> Khôi phục
                                                    </button>
                                                </form>
                                            @else
                                                <button type="button"
                                                        class="btn btn-outline-warning btn-sm btn-hide-room"
                                                        data-room-id="{{ $room->id }}"
                                                        data-room-name="{{ $room->name }}"
                                                        data-upcoming-showtimes="{{ $room->upcoming_showtimes_count }}"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#hideRoomModal">
                                                    <i class="bi bi-eye-slash"></i> Ẩn phòng
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="bi bi-door-open fs-1 d-block mb-2"></i>
                                        Rạp này chưa có phòng chiếu phù hợp.
                                        @if($selectedCinema->status === 'ACTIVE')
                                            <a href="{{ route('admin.rooms.create', ['cinema' => $selectedCinema->id]) }}">Thêm phòng chiếu</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(method_exists($rooms, 'links'))
                    <div class="d-flex align-items-center justify-content-between mt-3">
                        <div class="text-muted">
                            Tổng số phòng: {{ $rooms->total() }}
                        </div>
                        {{ $rooms->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif

{{-- Modal xác nhận ẩn phòng --}}
<div class="modal fade" id="hideRoomModal" tabindex="-1" aria-labelledby="hideRoomModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="hideRoomModalLabel">
                    <i class="bi bi-eye-slash text-warning me-2"></i>Xác nhận ẩn phòng
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn ẩn phòng <strong id="hideRoomName"></strong>?</p>

                <div id="hideRoomShowtimeWarning" class="alert alert-warning d-none">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <strong>Cảnh báo:</strong> Phòng này đang có <strong id="hideRoomShowtimeCount"></strong> suất chiếu chưa diễn ra.
                    Hệ thống sẽ chặn thao tác ẩn cho đến khi xử lý suất chiếu liên quan.
                </div>

                <p class="text-muted mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Phòng bị ẩn sẽ không được dùng để tạo suất chiếu mới.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ bỏ</button>
                <form id="hideRoomForm" method="POST" action="">
                    @csrf
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-eye-slash me-1"></i>Xác nhận ẩn phòng
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

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

    document.querySelectorAll('.btn-hide-room').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const roomId = this.dataset.roomId;
            const roomName = this.dataset.roomName;
            const upcomingShowtimes = parseInt(this.dataset.upcomingShowtimes);

            document.getElementById('hideRoomName').textContent = roomName;
            document.getElementById('hideRoomForm').action = '/admin/rooms/' + roomId + '/hide';

            const warningEl = document.getElementById('hideRoomShowtimeWarning');
            if (upcomingShowtimes > 0) {
                document.getElementById('hideRoomShowtimeCount').textContent = upcomingShowtimes;
                warningEl.classList.remove('d-none');
            } else {
                warningEl.classList.add('d-none');
            }
        });
    });
});
</script>
@endpush
