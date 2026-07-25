@extends('layout.admin')

@section('content')

<!-- CHỈ giữ lại CSS thẩm mỹ, KHÔNG ép layout bằng !important -->
<style>
/* Thẩm mỹ cho thẻ chọn phòng */
.room-option-card {
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 10px;
    cursor: pointer;
}
.room-option-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15) !important;
}
.room-option-card.selected-room {
    background: linear-gradient(135deg, #0d6efd, #3b82f6) !important;
    border-color: #0d6efd !important;
    color: #fff !important;
    box-shadow: 0 0 0 .2rem rgba(13,110,253,.25);
}
.room-option-card.selected-room * {
    color: #fff !important;
}
.room-option-card.selected-room .badge {
    background: #fff !important;
    color: #0d6efd !important;
}
</style>

<!-- ĐÃ BỎ wrapper <div class="container-fluid px-0"> thừa gây tràn layout.
     layout.admin đã có sẵn container-fluid có padding chuẩn (px-3 px-lg-4),
     nên không cần bọc thêm ở đây (bọc thêm + px-0 làm mất padding bù margin âm của .row). -->

<div class="d-flex justify-content-between align-items-center mb-4 mt-3">
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

<div class="card shadow-sm border-0 mb-5">
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

                <!-- Chọn ngày -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Ngày chiếu <span class="text-danger">*</span>
                    </label>
                    <input
                        type="date"
                        id="selectDate"
                        name="date"
                        class="form-control @error('date') is-invalid @enderror"
                        value="{{ old('date', optional($showtime->start_time)->format('Y-m-d')) }}"
                        required
                    >
                    <div id="dateRangeHint" class="form-text text-muted small">Chọn ngày trong khoảng phát hành phim.</div>
                    @error('date')
                        <div class="text-danger mt-1 small">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Hidden inputs lưu thời gian -->
                <input type="hidden" name="start_time" id="inputStartTime" value="{{ old('start_time', optional($showtime->start_time)->format('Y-m-d\TH:i')) }}">
                <input type="hidden" name="end_time" id="inputEndTime" value="{{ old('end_time', optional($showtime->end_time)->format('Y-m-d\TH:i')) }}">
            </div>

            <!-- Danh sách phòng chiếu -->
            <div class="mb-4">
                <label class="form-label fw-semibold d-flex align-items-center">
                    Phòng chiếu <span class="text-danger ms-1">*</span>
                </label>

                <input type="hidden" name="room_id" id="selectedRoomId" value="{{ old('room_id', $showtime->room_id) }}">

                <div id="roomLoading" style="display:none;" class="text-muted my-3 py-2 small">
                    <div class="spinner-border spinner-border-sm me-2 text-primary" role="status"></div>
                    Đang tải danh sách phòng chiếu...
                </div>

                <div id="roomPlaceholder" class="text-muted my-3 py-2 small" style="display:none;">
                    <i class="bi bi-info-circle me-1"></i>
                    Vui lòng chọn phim và ngày chiếu để hiển thị phòng.
                </div>

                <!-- Khu vực Render Phòng qua JS -->
                <div id="roomContainer"></div>

                @error('room_id')
                    <div class="text-danger mt-2 small">{{ $message }}</div>
                @enderror
            </div>

            <!-- Khung giờ trống -->
            <div class="mb-4">
                <label class="form-label fw-semibold d-flex align-items-center">
                    Khung giờ của phòng đã chọn <span class="text-danger ms-1">*</span>
                </label>

                <input type="hidden" name="movie_duration_minutes" id="movieDurationMinutes" value="{{ (int) optional($showtime->movie)->duration_minutes }}">

                <div id="slotLoading" style="display:none;" class="text-muted my-3 py-2 small">
                    <div class="spinner-border spinner-border-sm me-2 text-primary" role="status"></div>
                    Đang quét lịch trình và kiểm tra các khung giờ trùng...
                </div>

                <div id="slotPlaceholder" class="text-muted my-3 py-2 small" style="display:none;">
                    <i class="bi bi-info-circle me-1"></i>
                    Hãy chọn một phòng chiếu để xem các khung giờ khả dụng.
                </div>

                <div id="slotAlert" class="alert alert-warning border-0 shadow-sm" style="display:none;">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Không tìm thấy khung giờ phù hợp cho phòng này trong ngày đã chọn.
                </div>

                <!-- Khu vực Render Slot qua JS -->
                <div id="slotContainer"></div>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const API_CHECK_ROOMS = '{{ route("admin.showtime.api.check_rooms_availability") }}';
    const API_GET_ROOM_TIMELINE = '{{ route("admin.showtime.api.room_timeline") }}';
    const CSRF = '{{ csrf_token() }}';
    const showtimeId = '{{ $showtime->id }}';
        const API_MOVIE_INFO = '{{ route("admin.showtime.api.movie_info") }}';

    const selectMovie = document.getElementById('selectMovie');
    const selectDate = document.getElementById('selectDate');
    const inputStartTime = document.getElementById('inputStartTime');
    const inputEndTime = document.getElementById('inputEndTime');
    const selectedRoomInput = document.getElementById('selectedRoomId');
    const roomContainer = document.getElementById('roomContainer');
    const roomLoading = document.getElementById('roomLoading');
    const roomPlaceholder = document.getElementById('roomPlaceholder');
    const slotContainer = document.getElementById('slotContainer');
    const slotLoading = document.getElementById('slotLoading');
    const slotPlaceholder = document.getElementById('slotPlaceholder');
    const slotAlert = document.getElementById('slotAlert');
    const btnSubmit = document.getElementById('btnSubmit');
    const movieDurationMinutesInput = document.getElementById('movieDurationMinutes');

    function getSelectedDate() {
        return selectDate ? selectDate.value : null;
    }

    function buildSlotsFromGaps(gaps, durationMinutes) {
        const MIN_GAP = 15;
        const suggested = [];

        gaps.forEach(gap => {
            let currentStart = new Date(gap.start);
            const gapEnd = new Date(gap.end);

            while (true) {
                const currentEnd = new Date(currentStart.getTime() + durationMinutes * 60000);
                if (currentEnd > gapEnd) break;

                const pad2 = n => String(n).padStart(2, '0');
                const formatLocalDateTime = d => {
                    return `${d.getFullYear()}-${pad2(d.getMonth()+1)}-${pad2(d.getDate())}T${pad2(d.getHours())}:${pad2(d.getMinutes())}`;
                };

                suggested.push({
                    start_time: formatLocalDateTime(currentStart),
                    start_label: currentStart.toTimeString().slice(0, 5),
                    end_label: currentEnd.toTimeString().slice(0, 5),
                    end_time: formatLocalDateTime(currentEnd)
                });

                currentStart = new Date(currentEnd.getTime() + MIN_GAP * 60000);
            }
        });

        return suggested;
    }

    function renderSlots(slots) {
        slotContainer.innerHTML = '';

        if (!slots || slots.length === 0) {
            slotAlert.style.display = 'block';
            slotContainer.style.display = 'none';
            btnSubmit.disabled = true;
            return;
        }

        slotAlert.style.display = 'none';
        slotContainer.style.display = 'block';

        let html = '<div class="row g-3">';
        slots.forEach(slot => {
            const disabled = slot.conflict === true;
            html += `
                <div class="col-12 col-md-6 col-lg-4">
                    <button type="button" class="btn w-100 slot-btn ${disabled ? 'btn-light text-muted opacity-50 disabled' : 'btn-outline-primary'}"
                        ${disabled ? 'disabled' : ''}
                        data-start-time="${slot.start_time}"
                        data-end-time="${slot.end_time}"
                        title="${disabled ? 'Khung giờ này đã bị trùng với suất chiếu khác' : 'Chọn suất này'}">
                        <i class="bi bi-clock me-1"></i>
                        ${slot.start_label} - ${slot.end_label}
                    </button>
                </div>
            `;
        });
        html += '</div>';
        slotContainer.innerHTML = html;

        document.querySelectorAll('.slot-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (this.disabled || this.classList.contains('disabled')) return;

                const startTime = this.dataset.startTime;
                const endTime = this.dataset.endTime;

                inputStartTime.value = startTime;
                inputEndTime.value = endTime;
                btnSubmit.disabled = false;

                document.querySelectorAll('.slot-btn').forEach(b => {
                    if (!b.disabled) {
                        b.classList.remove('btn-primary');
                        b.classList.add('btn-outline-primary');
                    }
                });
                this.classList.remove('btn-outline-primary');
                this.classList.add('btn-primary');
            });
        });
    }

    function loadSlotsForSelectedRoom() {
        const roomId = selectedRoomInput.value;
        const movieId = selectMovie.value;
        const dateStr = getSelectedDate();

        if (!roomId || !movieId || !dateStr) {
            slotContainer.innerHTML = '';
            slotAlert.style.display = 'none';
            slotLoading.style.display = 'none';
            slotPlaceholder.style.display = 'block';
            return;
        }

        slotPlaceholder.style.display = 'none';
        slotAlert.style.display = 'none';
        slotLoading.style.display = 'block';
        slotContainer.style.display = 'none';

        fetch(API_GET_ROOM_TIMELINE, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF
            },
            body: JSON.stringify({ room_id: roomId, date: dateStr, showtime_id: showtimeId })
        })
        .then(res => res.json())
        .then(data => {
            slotLoading.style.display = 'none';

            let durationMinutes = parseInt(movieDurationMinutesInput?.value || '0', 10);
            const selectedOption = selectMovie.options[selectMovie.selectedIndex];
            if ((!durationMinutes || durationMinutes <= 0) && selectedOption) {
                const txt = selectedOption.textContent;
                const m = txt.match(/\((\d+)\s*phút\)/i);
                if (m) durationMinutes = parseInt(m[1], 10);
            }

            const day = data.date;
            const gaps = (data.gaps || []).map(g => {
                return {
                    start: new Date(`${day}T${g.start}:00`),
                    end: new Date(`${day}T${g.end}:00`)
                };
            });

            const slots = buildSlotsFromGaps(gaps, durationMinutes || 0);

            const existingShowtimes = (data.showtimes || [])
                .filter(st => String(st.id) !== String(showtimeId))
                .map(st => {
                    const s = new Date(`${day}T${st.start_time}:00`);
                    const e = new Date(`${day}T${st.end_time}:00`);
                    return {
                        start: new Date(s.getTime() - 15 * 60000),
                        end: new Date(e.getTime() + 15 * 60000)
                    };
                });

            slots.forEach(slot => {
                const slotStart = new Date(slot.start_time);
                const slotEnd = new Date(slot.end_time);

                const hasConflict = existingShowtimes.some(st => {
                    return st.start < slotEnd && st.end > slotStart;
                });

                slot.conflict = hasConflict;
            });

            renderSlots(slots);

            const currentSavedStart = inputStartTime.value;
            if (currentSavedStart) {
                const activeBtn = document.querySelector(`.slot-btn[data-start-time="${currentSavedStart}"]`);
                if (activeBtn && !activeBtn.disabled) {
                    activeBtn.click();
                }
            }
        })
        .catch(err => {
            console.error(err);
            slotLoading.style.display = 'none';
            slotAlert.style.display = 'block';
            btnSubmit.disabled = true;
        });
    }

    function updateRoomsList() {
        const movieId = selectMovie.value;
        const dateVal = getSelectedDate();

        if (!movieId || !dateVal) {
            roomContainer.innerHTML = '';
            roomPlaceholder.style.display = 'block';
            return;
        }

            function applyMovieDateWindow(movie) {
                const dateInput = document.getElementById('selectDate');
                const dateRangeHint = document.getElementById('dateRangeHint');
                if (!dateInput) return;

                const minDate = movie && movie.release_date ? movie.release_date : '';
                const maxDate = movie && movie.end_date ? movie.end_date : '';

                dateInput.min = minDate;
                dateInput.max = maxDate;

                if (dateRangeHint && movie) {
                    const label = movie.date_window_label || 'Chọn ngày trong khoảng phát hành phim.';
                    dateRangeHint.textContent = label;
                }

                // If current selected date is outside the window, reset it
                if (dateInput.value) {
                    if ((minDate && dateInput.value < minDate) || (maxDate && dateInput.value > maxDate)) {
                        dateInput.value = '';
                        inputStartTime.value = '';
                        inputEndTime.value = '';
                        slotContainer.innerHTML = '';
                        slotPlaceholder.style.display = 'block';
                        btnSubmit.disabled = true;
                    }
                }
            }

        roomPlaceholder.style.display = 'none';
        roomLoading.style.display = 'block';
        roomContainer.style.opacity = '0.5';

        fetch(API_CHECK_ROOMS, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF
            },
            body: JSON.stringify({
                movie_id: movieId,
                start_time: `${dateVal}T12:00`,
                showtime_id: showtimeId
            })
        })
        .then(res => res.json())
        .then(data => {
            roomLoading.style.display = 'none';
            roomContainer.style.opacity = '1';

            if (!data.rooms || data.rooms.length === 0) {
                roomContainer.innerHTML = '<div class="col-12 text-muted">Không tìm thấy phòng chiếu hoạt động.</div>';
                return;
            }

            let html = '<div class="row g-3">';
            const currentSelectedId = selectedRoomInput.value;

            data.rooms.forEach(room => {
                const isSelected = currentSelectedId == room.id;
                const cardClass = isSelected ? 'border-primary selected-room' : 'border-success bg-success bg-opacity-5';
                const badgeText = isSelected
                    ? '<span class="badge bg-primary"><i class="bi bi-check-circle-fill me-1"></i>Đang chọn</span>'
                    : '<span class="badge bg-success"><i class="bi bi-unlock-fill me-1"></i>Sẵn sàng</span>';

                html += `
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 room-option-card border-2 ${cardClass}" data-room-id="${room.id}">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0 fw-bold text-white">${room.name}</h6>
                                    ${badgeText}
                                </div>
                                <div class="small mb-1 text-muted">
                                    <i class="bi bi-display me-1"></i> Loại: <span class="fw-semibold text-light">${room.room_type || 'Thường'}</span>
                                </div>
                                <div class="small mb-0 text-muted">
                                    <i class="bi bi-grid-3x3 me-1"></i> Sức chứa: <span class="fw-semibold text-light">${room.total_seats} ghế</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            roomContainer.innerHTML = html;

            document.querySelectorAll('.room-option-card').forEach(card => {
                card.addEventListener('click', function() {
                    document.querySelectorAll('.room-option-card').forEach(c => {
                        c.className = 'card h-100 room-option-card border-2 border-success bg-success bg-opacity-5';
                        const badge = c.querySelector('.badge');
                        if (badge) {
                            badge.className = 'badge bg-success';
                            badge.innerHTML = '<i class="bi bi-unlock-fill me-1"></i>Sẵn sàng';
                        }
                    });

                    this.className = 'card h-100 room-option-card border-2 selected-room';
                    const badge = this.querySelector('.badge');
                    if (badge) {
                        badge.className = 'badge bg-primary';
                        badge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Đang chọn';
                    }

                    selectedRoomInput.value = this.dataset.roomId;

                    slotContainer.innerHTML = '';
                    slotAlert.style.display = 'none';
                    slotPlaceholder.style.display = 'none';
                    loadSlotsForSelectedRoom();
                });
            });

            if (currentSelectedId) {
                loadSlotsForSelectedRoom();
            }
        })
        .catch(err => {
            roomLoading.style.display = 'none';
            roomContainer.style.opacity = '1';
            roomContainer.innerHTML = '<div class="col-12 text-danger">Có lỗi khi tải danh sách phòng. Vui lòng thử lại.</div>';
            console.error(err);
        });
    }

    selectDate.addEventListener('change', function() {
        inputStartTime.value = '';
        inputEndTime.value = '';
        slotContainer.innerHTML = '';
        slotAlert.style.display = 'none';
        slotPlaceholder.style.display = 'block';
        btnSubmit.disabled = true;

        updateRoomsList();
    });

    selectMovie.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption) {
            const txt = selectedOption.textContent;
            const m = txt.match(/\((\d+)\s*phút\)/i);
            if (m) movieDurationMinutesInput.value = m[1];
        }

        inputStartTime.value = '';
        inputEndTime.value = '';
        slotContainer.innerHTML = '';
        slotAlert.style.display = 'none';
        slotPlaceholder.style.display = 'block';
        btnSubmit.disabled = true;

        updateRoomsList();
    });

    // When movie is selected (or on load), fetch its release/end dates to apply date window
    function fetchAndApplyMovieWindow(movieId) {
        if (!movieId) {
            applyMovieDateWindow(null);
            return;
        }

        fetch(API_MOVIE_INFO, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ movie_id: movieId })
        })
        .then(r => r.json())
        .then(data => {
            applyMovieDateWindow(data.movie);
        })
        .catch(err => {
            console.error('Lỗi lấy khoảng phát hành phim:', err);
        });
    }

    // Apply window on initial load for the currently selected movie
    document.addEventListener('DOMContentLoaded', function() {
        const initialMovieId = selectMovie ? selectMovie.value : null;
        if (initialMovieId) fetchAndApplyMovieWindow(initialMovieId);
    });

    slotPlaceholder.style.display = 'block';
    updateRoomsList();
});
</script>
@endpush
@endsection