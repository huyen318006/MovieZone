@extends('layout.admin')

@section('title', 'Tạo suất chiếu')

@section('content')

{{-- ===== THÔNG BÁO ===== --}}
@if(session('success'))
    <div id="success-alert" class="alert alert-success alert-dismissible fade show shadow-sm">
        <i class="bi bi-check-circle-fill me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div id="error-alert" class="alert alert-danger alert-dismissible fade show shadow-sm">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

{{-- ===== HEADER ===== --}}
<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-calendar-plus me-2"></i>Tạo suất chiếu</h3>
        <p class="text-muted mb-0">Tạo suất chiếu nhanh theo quy trình 3 bước — tự động quét lịch trống phòng chiếu.</p>
    </div>
    <a href="{{ route('admin.showtime') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>
        Quay lại
    </a>
</div>

{{-- ===== THANH TIẾN TRÌNH WIZARD (3 Bước) ===== --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <div class="wizard-progress d-flex align-items-center justify-content-between position-relative">
            <div class="wizard-line"></div>
            <div class="wizard-line-active" id="wizardLineActive"></div>

            {{-- Bước 1 --}}
            <div class="wizard-step active" data-step="1" id="wizardStep1">
                <div class="wizard-circle"><i class="bi bi-film"></i></div>
                <div class="wizard-label">1. Chọn phim & cấu hình</div>
            </div>
            {{-- Bước 2 --}}
            <div class="wizard-step" data-step="2" id="wizardStep2">
                <div class="wizard-circle"><i class="bi bi-calendar3"></i></div>
                <div class="wizard-label">2. Chọn ngày chiếu</div>
            </div>
            {{-- Bước 3 --}}
            <div class="wizard-step" data-step="3" id="wizardStep3">
                <div class="wizard-circle"><i class="bi bi-door-open"></i></div>
                <div class="wizard-label">3. Quét phòng & chọn giờ</div>
            </div>
        </div>
    </div>
</div>

{{-- ===== FORM CHÍNH ===== --}}
<form action="{{ route('admin.store.showtime') }}" method="POST" id="showtimeForm">
    @csrf

    {{-- ============================================================
         BƯỚC 1: CHỌN PHIM & ĐỊNH DẠNG & NGÔN NGỮ
         ============================================================ --}}
    <div class="wizard-panel" id="panel1">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <div class="fw-semibold"><i class="bi bi-film me-2 text-primary"></i>Bước 1: Chọn phim & thông tin đi kèm</div>
                <small class="text-muted">Chọn bộ phim, sau đó kiểm tra/chỉnh sửa định dạng và ngôn ngữ chiếu.</small>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <!-- Chọn phim -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <label class="form-label fw-semibold">Phim <span class="text-danger">*</span></label>
                        <select name="movie_id" id="selectMovie" class="form-select @error('movie_id') is-invalid @enderror" required>
                            <option value="">— Chọn phim —</option>
                            @foreach($movies as $movie)
                                <option value="{{ $movie->id }}" {{ old('movie_id') == $movie->id ? 'selected' : '' }}>
                                    {{ $movie->title }} ({{ $movie->duration_minutes }} phút)
                                </option>
                            @endforeach
                        </select>
                        @error('movie_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- <!-- Ngôn ngữ -->
                    <div class="col-12 col-lg-4">
                        <label class="form-label fw-semibold">Ngôn ngữ <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            name="language_type"
                            id="inputLanguageType"
                            class="form-control @error('language_type') is-invalid @enderror"
                            value="{{ old('language_type') }}"
                            placeholder="Ví dụ: Phụ đề, Lồng tiếng"
                            required
                        >
                        @error('language_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div> --}}
                </div>

                {{-- Card thông tin phim --}}
                <div id="movieInfoCard" class="mt-4" style="display:none;">
                    <div class="card border border-primary border-opacity-25" style="background: var(--bs-body-bg);">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-auto">
                                    <img id="moviePoster" src="" alt="Poster"
                                         class="rounded shadow-sm" style="width:120px; height:170px; object-fit:cover;">
                                </div>
                                <div class="col">
                                    <h5 id="movieTitle" class="mb-1"></h5>
                                    <p id="movieOriginalTitle" class="text-muted small mb-2"></p>
                                    <div class="d-flex flex-wrap gap-2 mb-2">
                                        <span class="badge text-bg-info" id="movieDuration"></span>
                                        <span class="badge text-bg-warning" id="movieAgeRating"></span>
                                        <span class="badge text-bg-secondary" id="movieLanguage"></span>
                                        <span class="badge text-bg-success" id="movieStatus"></span>
                                    </div>
                                    <div class="small mb-1"><i class="bi bi-person me-1"></i>Đạo diễn: <strong id="movieDirector"></strong></div>
                                    <div class="small mb-1"><i class="bi bi-tags me-1"></i>Thể loại: <span id="movieGenres"></span></div>
                                    <div class="small"><i class="bi bi-calendar-range me-1"></i>Phát hành: <span id="movieRelease"></span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-primary px-4" id="btnNext1" disabled>
                    Tiếp theo <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- ============================================================
         BƯỚC 2: CHỌN NGÀY
         ============================================================ --}}
    <div class="wizard-panel" id="panel2" style="display:none;">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <div class="fw-semibold"><i class="bi bi-calendar3 me-2 text-primary"></i>Bước 2: Chọn ngày chiếu</div>
                <small class="text-muted">Chọn ngày bạn muốn chiếu phim này.</small>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-12 col-md-6 col-lg-4">
                        <label class="form-label fw-semibold">Ngày chiếu <span class="text-danger">*</span></label>
                        <input type="date" id="selectDate" class="form-control" required>
                    </div>
                </div>

                {{-- Tổng quan ngày --}}
                <div id="dateSummary" class="mt-4" style="display:none;">
                    <div class="row g-3">
                        <div class="col-sm-6 col-lg-3">
                            <div class="card border border-info border-opacity-25 h-100">
                                <div class="card-body text-center p-3">
                                    <div class="text-muted small">Tổng suất chiếu trong ngày</div>
                                    <div class="fs-3 fw-bold text-info" id="totalShowtimesDay">0</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="card border border-warning border-opacity-25 h-100">
                                <div class="card-body text-center p-3">
                                    <div class="text-muted small">Phim này đã chiếu</div>
                                    <div class="fs-3 fw-bold text-warning" id="movieShowtimesDay">0</div>
                                    <div class="text-muted small">suất trong ngày</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="card border border-success border-opacity-25 h-100">
                                <div class="card-body text-center p-3">
                                    <div class="text-muted small">Phim đã chọn</div>
                                    <div class="fs-6 fw-bold text-success text-truncate" id="selectedMovieName">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="card border border-primary border-opacity-25 h-100">
                                <div class="card-body text-center p-3">
                                    <div class="text-muted small">Thời lượng phim</div>
                                    <div class="fs-3 fw-bold text-primary" id="movieDurationBig">0</div>
                                    <div class="text-muted small">phút</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent d-flex justify-content-between gap-2">
                <button type="button" class="btn btn-outline-secondary px-4" id="btnBack2">
                    <i class="bi bi-arrow-left me-1"></i> Quay lại
                </button>
                <button type="button" class="btn btn-primary px-4" id="btnNext2" disabled>
                    Tiếp theo <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- ============================================================
         BƯỚC 3: QUÉT PHÒNG & CHỌN GIỜ CHUYỂN TẠO NGAY
         ============================================================ --}}
    <div class="wizard-panel" id="panel3" style="display:none;">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <div class="fw-semibold"><i class="bi bi-door-open me-2 text-primary"></i>Bước 3: Chọn phòng & Khung giờ chiếu</div>
                <small class="text-muted">Chọn một khung giờ trống khả dụng dưới đây. Hệ thống sẽ ngay lập tức xếp lịch và tạo suất chiếu.</small>
            </div>
            <div class="card-body p-4">
                <!-- Hidden inputs to submit room_id and start_time -->
                <input type="hidden" name="room_id" id="selectedRoomId">
                <input type="hidden" name="start_time" id="hiddenStartTime">

                <!-- Loading indicator while scanning slots -->
                <div id="slotsLoading" class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <div class="text-muted">Hệ thống đang quét lịch trống khả dụng của tất cả các phòng chiếu trong ngày...</div>
                </div>

                <!-- Alert if no slots are available anywhere -->
                <div id="noSlotsAlert" class="alert alert-warning border-0 shadow-sm" style="display:none;">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Không tìm thấy khung giờ nào trống và đủ dài cho bộ phim này (bao gồm 15 phút dọn dẹp vệ sinh phòng). Vui lòng thử chọn ngày khác hoặc phim khác.
                </div>

                <!-- Slots grid container -->
                <div id="slotsContainer" class="row g-4" style="display:none;">
                    <!-- Renders dynamically -->
                </div>
            </div>
            <div class="card-footer bg-transparent d-flex justify-content-start gap-2">
                <button type="button" class="btn btn-outline-secondary px-4" id="btnBack3">
                    <i class="bi bi-arrow-left me-1"></i> Quay lại
                </button>
            </div>
        </div>
    </div>
</form>

{{-- ===== CSS ===== --}}
<style>
/* ---------- WIZARD PROGRESS BAR ---------- */
.wizard-progress {
    padding: 0 20px;
}
.wizard-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 2;
    cursor: default;
}
.wizard-circle {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    background: var(--bs-tertiary-bg);
    color: var(--bs-secondary-color);
    border: 3px solid var(--bs-border-color);
    transition: all .3s ease;
}
.wizard-step.active .wizard-circle {
    background: #0d6efd;
    color: #fff;
    border-color: #0d6efd;
    box-shadow: 0 0 0 4px rgba(13,110,253,.2);
}
.wizard-step.completed .wizard-circle {
    background: #198754;
    color: #fff;
    border-color: #198754;
}
.wizard-label {
    margin-top: 8px;
    font-size: .78rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
    white-space: nowrap;
}
.wizard-step.active .wizard-label {
    color: #0d6efd;
}
.wizard-step.completed .wizard-label {
    color: #198754;
}
.wizard-line {
    position: absolute;
    top: 24px;
    left: 60px;
    right: 60px;
    height: 3px;
    background: var(--bs-border-color);
    z-index: 1;
}
.wizard-line-active {
    position: absolute;
    top: 24px;
    left: 60px;
    height: 3px;
    background: #0d6efd;
    z-index: 1;
    width: 0%;
    transition: width .4s ease;
}

/* ---------- SLOT SELECTION CARDS ---------- */
.room-card-slot {
    border-radius: 12px;
    transition: all 0.2s ease;
}
.slot-btn {
    transition: all 0.15s ease;
    font-size: 0.85rem;
    font-weight: 600;
    border-radius: 8px;
    padding: 8px 12px;
}
.slot-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(13, 110, 253, 0.15);
}
</style>

{{-- ===== JAVASCRIPT ===== --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== VARIABLES =====
    const CSRF = '{{ csrf_token() }}';
    const API_MOVIE_INFO = '{{ route("admin.showtime.api.movie_info") }}';
    const API_AVAILABLE_SLOTS = '{{ route("admin.showtime.api.available_slots") }}';

    let currentStep = 1;
    let selectedMovie = null;
    let selectedDate = null;

    // ===== DOM REFERENCES =====
    const selectMovie = document.getElementById('selectMovie');
    const selectDate = document.getElementById('selectDate');
    const inputLanguageType = document.getElementById('inputLanguageType');
    const selectedRoomInput = document.getElementById('selectedRoomId');
    const hiddenStartTimeInput = document.getElementById('hiddenStartTime');
    const showtimeForm = document.getElementById('showtimeForm');

    // ===== GO TO STEP =====
    function goToStep(step) {
        document.querySelectorAll('.wizard-panel').forEach(p => p.style.display = 'none');
        document.getElementById('panel' + step).style.display = 'block';

        for (let i = 1; i <= 3; i++) {
            const el = document.getElementById('wizardStep' + i);
            el.classList.remove('active', 'completed');
            if (i < step) el.classList.add('completed');
            if (i === step) el.classList.add('active');
        }

        const lineActive = document.getElementById('wizardLineActive');
        const percents = { 1: '0%', 2: '50%', 3: '100%' };
        lineActive.style.width = percents[step] || '0%';

        currentStep = step;
    }

    // ===== STEP 1: CHỌN PHIM =====
    selectMovie.addEventListener('change', function() {
        const movieId = this.value;
        const movieInfoCard = document.getElementById('movieInfoCard');
        const btnNext1 = document.getElementById('btnNext1');

        if (!movieId) {
            movieInfoCard.style.display = 'none';
            btnNext1.disabled = true;
            selectedMovie = null;
            inputLanguageType.value = '';
            return;
        }

        fetch(API_MOVIE_INFO, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ movie_id: movieId })
        })
        .then(r => r.json())
        .then(data => {
            selectedMovie = data.movie;

            // Render movie details
            document.getElementById('moviePoster').src = data.movie.poster_url || '/assets/images/no-poster.png';
            document.getElementById('movieTitle').textContent = data.movie.title;
            document.getElementById('movieOriginalTitle').textContent = data.movie.original_title || '';
            document.getElementById('movieDuration').innerHTML = '<i class="bi bi-clock me-1"></i>' + data.movie.duration_minutes + ' phút';
            document.getElementById('movieAgeRating').textContent = data.movie.age_rating || 'N/A';
            document.getElementById('movieLanguage').textContent = data.movie.language || 'N/A';
            document.getElementById('movieDirector').textContent = data.movie.director || 'N/A';
            document.getElementById('movieGenres').textContent = data.movie.genres.join(', ') || 'N/A';

            const statusMap = { 'NOW_SHOWING': 'Đang chiếu', 'COMING_SOON': 'Sắp chiếu', 'STOPPED': 'Ngừng chiếu' };
            document.getElementById('movieStatus').textContent = statusMap[data.movie.status] || data.movie.status;

            let releaseText = '';
            if (data.movie.release_date) releaseText += formatDate(data.movie.release_date);
            if (data.movie.end_date) releaseText += ' → ' + formatDate(data.movie.end_date);
            document.getElementById('movieRelease').textContent = releaseText || 'Chưa xác định';

            // Tự động điền Ngôn ngữ gợi ý từ Phim
            let defaultLanguageType = data.movie.language || '';
            if (data.movie.subtitle) {
                defaultLanguageType += ' - Phụ đề ' + data.movie.subtitle;
            } else {
                defaultLanguageType += ' - Bản gốc';
            }
            inputLanguageType.value = defaultLanguageType;

            movieInfoCard.style.display = 'block';
            btnNext1.disabled = false;
        })
        .catch(err => {
            console.error('Lỗi lấy thông tin phim:', err);
            movieInfoCard.style.display = 'none';
            btnNext1.disabled = true;
        });
    });

    // ===== STEP 2: CHỌN NGÀY =====
    selectDate.addEventListener('change', function() {
        selectedDate = this.value;
        const btnNext2 = document.getElementById('btnNext2');
        const dateSummary = document.getElementById('dateSummary');

        if (!selectedDate || !selectedMovie) {
            dateSummary.style.display = 'none';
            btnNext2.disabled = true;
            return;
        }

        fetch(API_MOVIE_INFO, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ movie_id: selectedMovie.id, date: selectedDate })
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('totalShowtimesDay').textContent = data.total_showtimes_in_day;
            document.getElementById('movieShowtimesDay').textContent = data.showtime_count_today;
            document.getElementById('selectedMovieName').textContent = selectedMovie.title;
            document.getElementById('movieDurationBig').textContent = selectedMovie.duration_minutes;

            dateSummary.style.display = 'block';
            btnNext2.disabled = false;
        })
        .catch(err => {
            console.error('Lỗi lấy tổng quan ngày:', err);
            dateSummary.style.display = 'none';
            btnNext2.disabled = true;
        });
    });

    // ===== STEP 3: QUÉT PHÒNG & HIỂN THỊ KHUNG GIỜ TRỐNG =====
    function loadAvailableSlots() {
        const slotsLoading = document.getElementById('slotsLoading');
        const slotsContainer = document.getElementById('slotsContainer');
        const noSlotsAlert = document.getElementById('noSlotsAlert');

        slotsLoading.style.display = 'block';
        slotsContainer.style.display = 'none';
        noSlotsAlert.style.display = 'none';
        slotsContainer.innerHTML = '';

        fetch(API_AVAILABLE_SLOTS, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({
                movie_id: selectedMovie.id,
                date: selectedDate
            })
        })
        .then(r => r.json())
        .then(data => {
            slotsLoading.style.display = 'none';
            
            let html = '';
            let totalAvailableSlotsCount = 0;

            if (!data.rooms || data.rooms.length === 0) {
                slotsContainer.innerHTML = '<div class="col-12 text-center text-muted">Không tìm thấy phòng chiếu hoạt động nào trong hệ thống.</div>';
                slotsContainer.style.display = 'block';
                return;
            }

            data.rooms.forEach(room => {
                const slots = room.slots || [];
                totalAvailableSlotsCount += slots.length;

                let slotsHtml = '';
                if (slots.length === 0) {
                    slotsHtml = `<div class="text-danger small py-2"><i class="bi bi-x-circle me-1"></i>Không còn khoảng trống phù hợp</div>`;
                } else {
                    slotsHtml = '<div class="d-flex flex-wrap gap-2">';
                    slots.forEach(slot => {
                        // Chuyển khoảng trắng sang T để tương thích datetime-local format
                        const formStartTime = slot.start_time.replace(' ', 'T');
                        
                        slotsHtml += `
                            <button type="button" 
                                    class="btn btn-outline-primary slot-btn" 
                                    data-room-id="${room.id}" 
                                    data-start-time="${formStartTime}">
                                <i class="bi bi-clock me-1"></i>${slot.start_label} - ${slot.end_label}
                            </button>
                        `;
                    });
                    slotsHtml += '</div>';
                }

                html += `
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm border room-card-slot">
                            <div class="card-header bg-transparent py-3 room-card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold"><i class="bi bi-door-open me-2 text-primary"></i>${room.name}</h6>
                                <span class="badge text-bg-secondary">${room.room_type || 'Thường'}</span>
                            </div>
                            <div class="card-body p-3 d-flex flex-column justify-content-between">
                                <div class="mb-3 small text-muted">
                                    <i class="bi bi-grid-3x3 me-1"></i> Sức chứa: <strong class="text-dark">${room.total_seats} ghế</strong>
                                </div>
                                ${slotsHtml}
                            </div>
                        </div>
                    </div>
                `;
            });

            slotsContainer.innerHTML = html;
            slotsContainer.style.display = 'flex';

            // Nếu không có slot trống nào trong ngày trên tất cả các phòng
            if (totalAvailableSlotsCount === 0) {
                noSlotsAlert.style.display = 'block';
            }

            // Gắn sự kiện click vào slot
            document.querySelectorAll('.slot-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const roomId = this.dataset.roomId;
                    const startTime = this.dataset.startTime;

                    selectedRoomInput.value = roomId;
                    hiddenStartTimeInput.value = startTime;

                    // Submit form ngay lập tức!
                    showtimeForm.submit();
                });
            });
        })
        .catch(err => {
            console.error('Lỗi quét lịch trống:', err);
            slotsLoading.style.display = 'none';
            slotsContainer.innerHTML = '<div class="col-12 text-center text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Có lỗi xảy ra khi quét lịch trống phòng. Vui lòng thử lại.</div>';
            slotsContainer.style.display = 'block';
        });
    }

    // ===== NAV BUTTON EVENTS =====
    document.getElementById('btnNext1').addEventListener('click', () => {
        goToStep(2);
        selectDate.min = new Date().toISOString().split('T')[0];
    });

    document.getElementById('btnBack2').addEventListener('click', () => goToStep(1));
    document.getElementById('btnNext2').addEventListener('click', () => {
        goToStep(3);
        loadAvailableSlots();
    });

    document.getElementById('btnBack3').addEventListener('click', () => goToStep(2));

    // ===== UTILITY =====
    function formatDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    // Khởi tạo nếu có dữ liệu cũ
    @if(old('movie_id'))
        selectMovie.dispatchEvent(new Event('change'));
    @endif
});
</script>
@endpush

@endsection