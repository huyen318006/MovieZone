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
            <p class="text-muted mb-0">Quản lý danh sách phòng chiếu, trạng thái và sơ đồ ghế.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.rooms.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i>
                Thêm phòng chiếu
            </a>
        </div>
    </div>
</div>

{{-- Room table --}}
<div class="col-12 mt-3">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <div class="fw-semibold">Danh sách phòng chiếu</div>
                <small class="text-muted">Click vào tên phòng để xem sơ đồ ghế.</small>
            </div>
            <form method="GET" action="{{ route('admin.rooms.index') }}" class="d-flex gap-2 flex-wrap align-items-center">

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

                <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-clockwise"></i> Làm mới
                </a>
            </form>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" style="min-width: 900px;">
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>Tên phòng</th>
                            <th>Loại phòng</th>
                            <th>Sức chứa</th>
                            <th>Số ghế</th>
                            <th>Lịch chiếu sắp tới</th>
                            <th>Trạng thái</th>
                            <th style="width: 280px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rooms as $i => $room)
                            <tr>
                                <td>{{ $rooms->firstItem() + $i }}</td>
                                <td>
                                    <a href="{{ route('admin.rooms.seats', $room) }}" class="fw-semibold text-decoration-none">
                                        {{ $room->name }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge text-bg-primary">{{ $room->room_type }}</span>
                                </td>
                                <td>{{ $room->total_seats }} ghế</td>
                                <td>
                                    <span class="badge text-bg-info">
                                        <i class="bi bi-grid-3x3-gap me-1"></i>{{ $room->seats_count }} ghế
                                    </span>
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
                                    Chưa có phòng chiếu nào.
                                    <a href="{{ route('admin.rooms.create') }}">Thêm phòng chiếu</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex align-items-center justify-content-between mt-3">
                <div class="text-muted">
                    Tổng số phòng: {{ $rooms->total() }}
                </div>
                {{ $rooms->links() }}
            </div>
        </div>
    </div>
</div>

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
