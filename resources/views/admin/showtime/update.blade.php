@extends('layout.admin')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1"><i class="bi bi-pencil-square me-2 text-primary"></i>Cập nhật suất chiếu</h3>
            <p class="text-muted mb-0">
                Chỉnh sửa thông tin suất chiếu hiện tại. Tự động kiểm tra và lọc phòng trống thông minh.
            </p>
        </div>
        <a href="{{ route('admin.showtime') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
            Quay lại
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-transparent border-bottom py-3">
            <h5 class="card-title mb-0 fw-semibold">Thông tin suất chiếu</h5>
        </div>

        <div class="card-body p-4">
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show mb-4 shadow-sm border-0">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4 shadow-sm border-0">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger mb-4 shadow-sm border-0">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            <form action="{{ route('update.showtime', $showtime->id) }}" method="POST" id="updateShowtimeForm">
                @csrf

                <div class="row g-4 mb-4">
                    <!-- Chọn Phim -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Phim <span class="text-danger">*</span>
                        </label>
                        <select name="movie_id" id="selectMovie" class="form-select @error('movie_id') is-invalid @enderror" required>
                            <option value="">Chọn phim</option>
                            @foreach($movies as $movie)
                                <option value="{{ $movie->id }}" {{ old('movie_id', $showtime->movie_id) == $movie->id ? 'selected' : '' }}>
                                    {{ $movie->title }} ({{ $movie->duration_minutes }} phút)
                                </option>
                            @endforeach
                        </select>
                        @error('movie_id')
                            <div class="text-danger mt-1 small">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Thời gian bắt đầu -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Thời gian bắt đầu <span class="text-danger">*</span>
                        </label>
                        <input
                            type="datetime-local"
                            name="start_time"
                            id="inputStartTime"
                            class="form-control @error('start_time') is-invalid @enderror"
                            value="{{ old('start_time', optional($showtime->start_time)->format('Y-m-d\TH:i')) }}"
                            required
                        >
                        @error('start_time')
                            <div class="text-danger mt-1 small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Lưới chọn phòng chiếu thông minh -->
                <div class="mb-4">
                    <label class="form-label fw-semibold d-flex align-items-center">
                        Phòng chiếu <span class="text-danger ms-1">*</span>
                    </label>

                    <!-- Lưu giá trị room_id -->
                    <input type="hidden" name="room_id" id="selectedRoomId" value="{{ old('room_id', $showtime->room_id) }}">

                    <!-- Loading / Placeholder hiển thị khi kiểm tra -->
                    <div id="roomLoading" style="display:none;" class="text-muted my-3 py-2 small">
                        <div class="spinner-border spinner-border-sm me-2 text-primary" role="status"></div>
                        Đang phân tích trạng thái khả dụng của các phòng chiếu...
                    </div>
                    
                    <div id="roomPlaceholder" class="text-muted my-3 py-2 small" style="display:none;">
                        <i class="bi bi-info-circle me-1"></i>
                        Vui lòng nhập Phim và Thời gian bắt đầu hợp lệ để tìm phòng trống.
                    </div>

                    <!-- Container danh sách phòng chiếu -->
                    <div id="roomContainer" class="row g-3">
                        <!-- Danh sách phòng sẽ được render tự động qua AJAX -->
                    </div>

                    @error('room_id')
                        <div class="text-danger mt-2 small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row g-4 mb-4">
                    <!-- Định dạng -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Định dạng <span class="text-danger">*</span></label>
                        <select name="format" class="form-select @error('format') is-invalid @enderror" required>
                            <option value="2D" {{ old('format', $showtime->format) == '2D' ? 'selected' : '' }}>2D</option>
                            <option value="3D" {{ old('format', $showtime->format) == '3D' ? 'selected' : '' }}>3D</option>
                            <option value="IMAX" {{ old('format', $showtime->format) == 'IMAX' ? 'selected' : '' }}>IMAX</option>
                        </select>
                        @error('format')
                            <div class="text-danger mt-1 small">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Ngôn ngữ -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Ngôn ngữ <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            name="language_type"
                            class="form-control @error('language_type') is-invalid @enderror"
                            value="{{ old('language_type', $showtime->language_type) }}"
                            placeholder="Ví dụ: Phụ đề, Lồng tiếng"
                            required
                        >
                        @error('language_type')
                            <div class="text-danger mt-1 small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex gap-2 justify-content-end">
                    <a href="{{ route('admin.showtime') }}" class="btn btn-outline-secondary px-4">Hủy bỏ</a>
                    <button type="submit" class="btn btn-primary px-4" id="btnSubmit">
                        <i class="bi bi-save me-1"></i>
                        Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== PHẦN CSS CUSTOM CHO PHÒNG CHIẾU ===== --}}
<style>
.room-option-card {
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 10px;
    cursor: pointer;
}
.room-option-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.08) !important;
}
.room-option-card.selected-room {
    border-color: #0d6efd !important;
    background-color: rgba(13, 110, 253, 0.06) !important;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15) !important;
}
.room-option-card.conflicting-room {
    opacity: 0.6;
    background-color: #f8f9fa !important;
    border-color: #dee2e6 !important;
}
.room-option-card.conflicting-room:hover {
    transform: none;
    box-shadow: none !important;
}
</style>

{{-- ===== JAVASCRIPT ĐIỀU PHỐI AJAX ===== --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const API_CHECK_ROOMS = '{{ route("admin.showtime.api.check_rooms_availability") }}';
    const CSRF = '{{ csrf_token() }}';
    const showtimeId = '{{ $showtime->id }}';

    const selectMovie = document.getElementById('selectMovie');
    const inputStartTime = document.getElementById('inputStartTime');
    const selectedRoomInput = document.getElementById('selectedRoomId');
    const roomContainer = document.getElementById('roomContainer');
    const roomLoading = document.getElementById('roomLoading');
    const roomPlaceholder = document.getElementById('roomPlaceholder');
    const btnSubmit = document.getElementById('btnSubmit');

    function updateRoomsList() {
        const movieId = selectMovie.value;
        const startTime = inputStartTime.value;

        // Reset nếu thiếu thông tin
        if (!movieId || !startTime) {
            roomContainer.innerHTML = '';
            roomPlaceholder.style.display = 'block';
            return;
        }

        // Kiểm tra sơ bộ ngày bắt đầu
        const startDateTime = new Date(startTime);
        const now = new Date();
        if (startDateTime < now) {
            roomContainer.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-warning border-0 shadow-sm mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>
                        Thời gian bắt đầu phải lớn hơn thời điểm hiện tại.
                    </div>
                </div>
            `;
            roomPlaceholder.style.display = 'none';
            btnSubmit.disabled = true;
            return;
        }

        roomPlaceholder.style.display = 'none';
        roomLoading.style.display = 'block';
        roomContainer.style.opacity = '0.5';

        // Gọi AJAX lên Backend để kiểm tra lịch trống của phòng chiếu
        fetch(API_CHECK_ROOMS, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF
            },
            body: JSON.stringify({
                movie_id: movieId,
                start_time: startTime,
                showtime_id: showtimeId
            })
        })
        .then(res => res.json())
        .then(data => {
            roomLoading.style.display = 'none';
            roomContainer.style.opacity = '1';
            
            let html = '';
            const currentSelectedId = selectedRoomInput.value;
            let selectedRoomStillAvailable = false;

            if (!data.rooms || data.rooms.length === 0) {
                roomContainer.innerHTML = '<div class="col-12 text-muted">Không tìm thấy phòng chiếu hoạt động.</div>';
                return;
            }

            data.rooms.forEach(room => {
                const isSelected = currentSelectedId == room.id;
                if (isSelected && room.is_available) {
                    selectedRoomStillAvailable = true;
                }
                
                // Xác định style hiển thị theo trạng thái khả dụng
                const cardClass = room.is_available 
                    ? (isSelected ? 'border-primary selected-room' : 'border-success bg-success bg-opacity-5') 
                    : 'border-danger conflicting-room';

                const badgeText = room.is_available 
                    ? (isSelected ? '<span class="badge bg-primary"><i class="bi bi-check-circle-fill me-1"></i>Đang chọn</span>' : '<span class="badge bg-success"><i class="bi bi-unlock-fill me-1"></i>Khả dụng</span>') 
                    : '<span class="badge bg-danger"><i class="bi bi-lock-fill me-1"></i>Trùng lịch</span>';

                const cursorStyle = room.is_available ? 'cursor: pointer;' : 'cursor: not-allowed; pointer-events: none;';

                html += `
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 room-option-card border-2 ${cardClass}" 
                             data-room-id="${room.id}" 
                             data-room-name="${room.name}"
                             style="${cursorStyle}"
                             ${room.is_available ? '' : 'title="' + room.conflict_reason + '"'}>
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0 fw-bold ${room.is_available ? 'text-dark' : 'text-muted'}">${room.name}</h6>
                                    ${badgeText}
                                </div>
                                <div class="small mb-1 text-muted">
                                    <i class="bi bi-display me-1"></i>
                                    Loại: <span class="fw-semibold text-dark">${room.room_type || 'Thường'}</span>
                                </div>
                                <div class="small mb-2 text-muted">
                                    <i class="bi bi-grid-3x3 me-1"></i>
                                    Sức chứa: <span class="fw-semibold text-dark">${room.total_seats} ghế</span>
                                </div>
                                ${!room.is_available ? `
                                    <div class="mt-2 text-danger small border-top border-danger border-opacity-25 pt-2">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                        ${room.conflict_reason}
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });

            roomContainer.innerHTML = html;

            // Nếu phòng hiện tại đã chọn bị trùng lịch ở khung giờ mới, bỏ chọn và yêu cầu chọn lại
            if (currentSelectedId && !selectedRoomStillAvailable) {
                const matchingRoom = data.rooms.find(r => r.id == currentSelectedId);
                if (matchingRoom && !matchingRoom.is_available) {
                    selectedRoomInput.value = '';
                    btnSubmit.disabled = true;
                }
            } else if (selectedRoomStillAvailable) {
                btnSubmit.disabled = false;
            }

            // Đăng ký sự kiện click cho các card phòng khả dụng
            document.querySelectorAll('.room-option-card').forEach(card => {
                if (card.classList.contains('conflicting-room')) return;

                card.addEventListener('click', function() {
                    // Bỏ chọn tất cả các card phòng khác
                    document.querySelectorAll('.room-option-card').forEach(c => {
                        if (c.classList.contains('conflicting-room')) return;
                        c.className = 'card h-100 room-option-card border-2 border-success bg-success bg-opacity-5';
                        const badge = c.querySelector('.badge');
                        if (badge) {
                            badge.className = 'badge bg-success';
                            badge.innerHTML = '<i class="bi bi-unlock-fill me-1"></i>Khả dụng';
                        }
                    });

                    // Chọn card này
                    this.className = 'card h-100 room-option-card border-2 border-primary selected-room';
                    const badge = this.querySelector('.badge');
                    if (badge) {
                        badge.className = 'badge bg-primary';
                        badge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Đang chọn';
                    }

                    selectedRoomInput.value = this.dataset.roomId;
                    btnSubmit.disabled = false;
                });
            });
        })
        .catch(err => {
            roomLoading.style.display = 'none';
            roomContainer.style.opacity = '1';
            roomContainer.innerHTML = '<div class="col-12 text-danger">Có lỗi khi tải danh sách phòng. Vui lòng thử lại.</div>';
            console.error(err);
        });
    }

    // Gắn sự kiện thay đổi
    selectMovie.addEventListener('change', updateRoomsList);
    inputStartTime.addEventListener('change', updateRoomsList);

    // Kích hoạt kiểm tra phòng trống khi load trang lần đầu
    updateRoomsList();
});
</script>
@endpush

@endsection
