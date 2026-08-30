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
            <a href="{{ \App\Helpers\TabAuthHelper::route('admin.rooms.create') }}" class="btn btn-primary">
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
            <form method="GET" action="{{ \App\Helpers\TabAuthHelper::route('admin.rooms.index') }}" class="d-flex gap-2 flex-wrap align-items-center">
                <input type="hidden" name="tab_token" value="{{ \App\Helpers\TabAuthHelper::gettoken() }}">

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

                <a href="{{ \App\Helpers\TabAuthHelper::route('admin.rooms.index') }}" class="btn btn-outline-secondary btn-sm">
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
                                    <a href="{{ \App\Helpers\TabAuthHelper::route('admin.seats.index', ['room_id' => $room->id]) }}" class="fw-semibold text-decoration-none">
                                        {{ $room->name }}
                                    </a>
                                    {{-- Badge trạng thái hoạt động chi tiết --}}
                                    @if($room->is_currently_showing)
                                        <span class="badge text-bg-danger ms-1" title="Đang có suất chiếu diễn ra">
                                            <i class="bi bi-broadcast me-1"></i>Đang chiếu
                                        </span>
                                    @elseif($room->is_about_to_show)
                                        <span class="badge text-bg-warning ms-1" title="Có suất chiếu sắp bắt đầu trong 30 phút">
                                            <i class="bi bi-clock-history me-1"></i>Sắp chiếu
                                        </span>
                                    @endif
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

                                    {{-- Badge giữ đã ẩn theo yêu cầu --}}
                                    @if($room->sold_seats_count > 0)
                                        <span class="badge text-bg-success ms-1" title="Ghế đã bán">
                                            <i class="bi bi-ticket-perforated me-1"></i>{{ $room->sold_seats_count }} bán
                                        </span>
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
                                        <a href="{{ \App\Helpers\TabAuthHelper::route('admin.seats.index', ['room_id' => $room->id]) }}" class="btn btn-outline-info btn-sm">
                                            <i class="bi bi-grid-3x3-gap"></i> Sơ đồ ghế
                                        </a>
                                        <a href="{{ \App\Helpers\TabAuthHelper::route('admin.rooms.edit', ['room' => $room]) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-pencil"></i> Sửa
                                        </a>

                                        @if($room->status === 'INACTIVE')
                                            <form method="POST" action="{{ \App\Helpers\TabAuthHelper::route('admin.rooms.restore', ['room' => $room]) }}" class="d-inline">
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
                                                    data-held-seats="{{ $room->held_seats_count }}"
                                                    data-sold-seats="{{ $room->sold_seats_count }}"
                                                    data-active-bookings="{{ $room->active_bookings_count }}"
                                                    data-currently-showing="{{ $room->is_currently_showing ? '1' : '0' }}"
                                                    data-about-to-show="{{ $room->is_about_to_show ? '1' : '0' }}"
                                                    data-can-hide="{{ $room->can_hide ? '1' : '0' }}"
                                                    data-block-reasons="{{ json_encode($room->block_reasons) }}"
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
                                    <a href="{{ \App\Helpers\TabAuthHelper::route('admin.rooms.create') }}">Thêm phòng chiếu</a>
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

                {{-- Container hiển thị danh sách lý do chặn --}}
                <div id="hideRoomBlockReasons" class="d-none">
                    <div class="alert alert-danger mb-3">
                        <i class="bi bi-shield-exclamation me-1"></i>
                        <strong>Không thể ẩn phòng này!</strong> Vui lòng xử lý các vấn đề sau:
                    </div>
                    <ul id="hideRoomBlockReasonsList" class="list-group mb-3">
                        {{-- Populated by JavaScript --}}
                    </ul>
                </div>

                {{-- Cảnh báo riêng cho từng loại ràng buộc --}}
                <div id="hideRoomWarningCurrentlyShowing" class="alert alert-danger d-none mb-2">
                    <i class="bi bi-broadcast me-1"></i>
                    <strong>Đang chiếu:</strong> Phòng đang có suất chiếu đang diễn ra. Không thể thao tác cho đến khi suất chiếu kết thúc.
                </div>

                <div id="hideRoomWarningAboutToShow" class="alert alert-warning d-none mb-2">
                    <i class="bi bi-clock-history me-1"></i>
                    <strong>Sắp chiếu:</strong> Phòng có suất chiếu sắp bắt đầu trong 30 phút tới.
                </div>

                <div id="hideRoomWarningHeldSeats" class="alert alert-info d-none mb-2">
                    <i class="bi bi-hand-index me-1"></i>
                    <strong>Ghế đang giữ:</strong> Có <strong id="hideRoomHeldCount"></strong> ghế đang được khách hàng giữ.
                </div>

                <div id="hideRoomWarningSoldSeats" class="alert alert-success d-none mb-2">
                    <i class="bi bi-ticket-perforated me-1"></i>
                    <strong>Vé đã bán:</strong> Có <strong id="hideRoomSoldCount"></strong> vé đã bán cho suất chiếu chưa diễn ra.
                </div>

                <div id="hideRoomWarningActiveBookings" class="alert alert-primary d-none mb-2">
                    <i class="bi bi-cart-check me-1"></i>
                    <strong>Đơn đặt vé:</strong> Có <strong id="hideRoomBookingCount"></strong> đơn đặt vé chưa hoàn tất.
                </div>

                <div id="hideRoomWarningUpcoming" class="alert alert-warning d-none mb-2">
                    <i class="bi bi-calendar-event me-1"></i>
                    <strong>Suất chiếu tương lai:</strong> Phòng đang có <strong id="hideRoomShowtimeCount"></strong> suất chiếu chưa diễn ra.
                    Hệ thống sẽ chặn thao tác ẩn cho đến khi xử lý suất chiếu liên quan.
                </div>

                {{-- Thông báo khi không có vấn đề --}}
                <div id="hideRoomNoIssues" class="d-none">
                    <p class="text-muted mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Phòng bị ẩn sẽ không được dùng để tạo suất chiếu mới.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ bỏ</button>
                <form id="hideRoomForm" method="POST" action="">
                    @csrf
                    <button type="submit" id="hideRoomSubmitBtn" class="btn btn-warning">
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
    // Auto-dismiss flash alerts
    ['success-alert', 'error-alert'].forEach(function (id) {
        const alertEl = document.getElementById(id);
        if (alertEl) {
            setTimeout(() => {
                alertEl.classList.remove('show');
                setTimeout(() => alertEl.remove(), 150);
            }, 3000);
        }
    });

    // Handle hide room modal
    document.querySelectorAll('.btn-hide-room').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const roomId = this.dataset.roomId;
            const roomName = this.dataset.roomName;
            const upcomingShowtimes = parseInt(this.dataset.upcomingShowtimes);
            const heldSeats = parseInt(this.dataset.heldSeats);
            const soldSeats = parseInt(this.dataset.soldSeats);
            const activeBookings = parseInt(this.dataset.activeBookings);
            const isCurrentlyShowing = this.dataset.currentlyShowing === '1';
            const isAboutToShow = this.dataset.aboutToShow === '1';
            const canHide = this.dataset.canHide === '1';
            const blockReasons = JSON.parse(this.dataset.blockReasons || '[]');

            // Set room name and form action
            document.getElementById('hideRoomName').textContent = roomName;
            document.getElementById('hideRoomForm').action = '/admin/rooms/' + roomId + '/hide';

            // Reset all warnings
            const warningIds = [
                'hideRoomWarningCurrentlyShowing',
                'hideRoomWarningAboutToShow',
                'hideRoomWarningHeldSeats',
                'hideRoomWarningSoldSeats',
                'hideRoomWarningActiveBookings',
                'hideRoomWarningUpcoming',
                'hideRoomBlockReasons',
                'hideRoomNoIssues'
            ];
            warningIds.forEach(id => document.getElementById(id).classList.add('d-none'));

            // Show relevant warnings
            if (blockReasons.length > 0) {
                // Show block reasons summary
                document.getElementById('hideRoomBlockReasons').classList.remove('d-none');
                const reasonsList = document.getElementById('hideRoomBlockReasonsList');
                reasonsList.innerHTML = '';
                blockReasons.forEach(function(reason) {
                    const li = document.createElement('li');
                    li.className = 'list-group-item list-group-item-danger d-flex align-items-center';
                    li.innerHTML = '<i class="bi bi-x-circle text-danger me-2"></i>' + reason;
                    reasonsList.appendChild(li);
                });

                // Show individual detail warnings
                if (isCurrentlyShowing) {
                    document.getElementById('hideRoomWarningCurrentlyShowing').classList.remove('d-none');
                }
                if (isAboutToShow) {
                    document.getElementById('hideRoomWarningAboutToShow').classList.remove('d-none');
                }
                if (heldSeats > 0) {
                    document.getElementById('hideRoomHeldCount').textContent = heldSeats;
                    document.getElementById('hideRoomWarningHeldSeats').classList.remove('d-none');
                }
                if (soldSeats > 0) {
                    document.getElementById('hideRoomSoldCount').textContent = soldSeats;
                    document.getElementById('hideRoomWarningSoldSeats').classList.remove('d-none');
                }
                if (activeBookings > 0) {
                    document.getElementById('hideRoomBookingCount').textContent = activeBookings;
                    document.getElementById('hideRoomWarningActiveBookings').classList.remove('d-none');
                }
                if (upcomingShowtimes > 0) {
                    document.getElementById('hideRoomShowtimeCount').textContent = upcomingShowtimes;
                    document.getElementById('hideRoomWarningUpcoming').classList.remove('d-none');
                }
            } else {
                document.getElementById('hideRoomNoIssues').classList.remove('d-none');
            }

            // Enable/disable submit button
            const submitBtn = document.getElementById('hideRoomSubmitBtn');
            if (canHide) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('btn-secondary');
                submitBtn.classList.add('btn-warning');
                submitBtn.innerHTML = '<i class="bi bi-eye-slash me-1"></i>Xác nhận ẩn phòng';
            } else {
                submitBtn.disabled = true;
                submitBtn.classList.remove('btn-warning');
                submitBtn.classList.add('btn-secondary');
                submitBtn.innerHTML = '<i class="bi bi-lock me-1"></i>Không thể ẩn phòng';
            }
        });
    });
});
</script>
@endpush
