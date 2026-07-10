{{--
    ============================================================
    WIZARD TẠO SUẤT CHIẾU - 4 BƯỚC
    ============================================================
    Luồng: Chọn phim → Chọn ngày → Chọn phòng (xem timeline) → Chọn giờ & xác nhận
    - Mỗi bước dùng AJAX gọi API để lấy dữ liệu realtime
    - Hiển thị timeline trực quan cho phòng chiếu
    - Kiểm tra trùng lịch tự động khi chọn giờ
    - Form submit POST giống logic cũ (store() không đổi)
    ============================================================
--}}
@extends('layout.admin')

@section('title', 'Tạo suất chiếu')

@section('content')

{{-- ===== THÔNG BÁO (giữ nguyên) ===== --}}
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
        <p class="text-muted mb-0">Tạo suất chiếu mới theo quy trình 4 bước — kiểm tra trùng lịch tự động.</p>
    </div>
    <a href="{{ route('admin.showtime') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>
        Quay lại
    </a>
</div>

{{-- ===== THANH TIẾN TRÌNH WIZARD ===== --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <div class="wizard-progress d-flex align-items-center justify-content-between position-relative">
            {{-- Đường line nền nối các bước --}}
            <div class="wizard-line"></div>
            <div class="wizard-line-active" id="wizardLineActive"></div>

            {{-- Bước 1: Chọn phim --}}
            <div class="wizard-step active" data-step="1" id="wizardStep1">
                <div class="wizard-circle"><i class="bi bi-film"></i></div>
                <div class="wizard-label">Chọn phim</div>
            </div>
            {{-- Bước 2: Chọn ngày --}}
            <div class="wizard-step" data-step="2" id="wizardStep2">
                <div class="wizard-circle"><i class="bi bi-calendar3"></i></div>
                <div class="wizard-label">Chọn ngày</div>
            </div>
            {{-- Bước 3: Chọn phòng --}}
            <div class="wizard-step" data-step="3" id="wizardStep3">
                <div class="wizard-circle"><i class="bi bi-door-open"></i></div>
                <div class="wizard-label">Chọn phòng</div>
            </div>
            {{-- Bước 4: Chọn giờ & xác nhận --}}
            <div class="wizard-step" data-step="4" id="wizardStep4">
                <div class="wizard-circle"><i class="bi bi-clock"></i></div>
                <div class="wizard-label">Giờ chiếu & Xác nhận</div>
            </div>
        </div>
    </div>
</div>

{{-- ===== FORM CHÍNH (action giữ nguyên như cũ để store() xử lý) ===== --}}
<form action="{{ route('admin.store.showtime') }}" method="POST" id="showtimeForm">
    @csrf

    {{-- ============================================================
         BƯỚC 1: CHỌN PHIM
         - Dropdown chọn phim
         - Khi chọn → AJAX lấy thông tin phim → Hiện card info
         ============================================================ --}}
    <div class="wizard-panel" id="panel1">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent">
                <div class="fw-semibold"><i class="bi bi-film me-2"></i>Bước 1: Chọn phim</div>
                <small class="text-muted">Chọn phim muốn xếp lịch chiếu. Thông tin phim sẽ hiển thị tự động.</small>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-lg-6">
                        <label class="form-label">Phim <span class="text-danger">*</span></label>
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
                </div>

                {{-- Card thông tin phim (ẩn, hiện khi chọn phim) --}}
                <div id="movieInfoCard" class="mt-4" style="display:none;">
                    <div class="card border border-primary border-opacity-25" style="background: var(--bs-body-bg);">
                        <div class="card-body">
                            <div class="row g-3">
                                {{-- Poster phim --}}
                                <div class="col-auto">
                                    <img id="moviePoster" src="" alt="Poster"
                                         class="rounded shadow-sm" style="width:120px; height:170px; object-fit:cover;">
                                </div>
                                {{-- Thông tin phim --}}
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
                <button type="button" class="btn btn-primary" id="btnNext1" disabled>
                    Tiếp theo <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- ============================================================
         BƯỚC 2: CHỌN NGÀY
         - Date picker chọn ngày chiếu
         - Hiện tổng quan: số suất đã xếp trong ngày
         ============================================================ --}}
    <div class="wizard-panel" id="panel2" style="display:none;">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent">
                <div class="fw-semibold"><i class="bi bi-calendar3 me-2"></i>Bước 2: Chọn ngày chiếu</div>
                <small class="text-muted">Chọn ngày chiếu, hệ thống sẽ hiển thị lịch chiếu hiện có trong ngày.</small>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-lg-4">
                        <label class="form-label">Ngày chiếu <span class="text-danger">*</span></label>
                        <input type="date" id="selectDate" class="form-control" required>
                    </div>
                </div>

                {{-- Tổng quan ngày (ẩn, hiện khi chọn ngày) --}}
                <div id="dateSummary" class="mt-4" style="display:none;">
                    <div class="row g-3">
                        <div class="col-sm-6 col-lg-3">
                            <div class="card border border-info border-opacity-25 h-100">
                                <div class="card-body text-center">
                                    <div class="text-muted small">Tổng suất chiếu trong ngày</div>
                                    <div class="fs-2 fw-bold text-info" id="totalShowtimesDay">0</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="card border border-warning border-opacity-25 h-100">
                                <div class="card-body text-center">
                                    <div class="text-muted small">Phim này đã chiếu</div>
                                    <div class="fs-2 fw-bold text-warning" id="movieShowtimesDay">0</div>
                                    <div class="text-muted small">suất trong ngày</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="card border border-success border-opacity-25 h-100">
                                <div class="card-body text-center">
                                    <div class="text-muted small">Phim đã chọn</div>
                                    <div class="fs-6 fw-bold text-success" id="selectedMovieName">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="card border border-primary border-opacity-25 h-100">
                                <div class="card-body text-center">
                                    <div class="text-muted small">Thời lượng phim</div>
                                    <div class="fs-2 fw-bold text-primary" id="movieDurationBig">0</div>
                                    <div class="text-muted small">phút</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent d-flex justify-content-between gap-2">
                <button type="button" class="btn btn-outline-secondary" id="btnBack2">
                    <i class="bi bi-arrow-left me-1"></i> Quay lại
                </button>
                <button type="button" class="btn btn-primary" id="btnNext2" disabled>
                    Tiếp theo <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- ============================================================
         BƯỚC 3: CHỌN PHÒNG
         - Hiện danh sách phòng với trạng thái (trống/bận/sắp chiếu)
         - Khi chọn phòng → hiện timeline bar trực quan
         ============================================================ --}}
    <div class="wizard-panel" id="panel3" style="display:none;">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent">
                <div class="fw-semibold"><i class="bi bi-door-open me-2"></i>Bước 3: Chọn phòng chiếu</div>
                <small class="text-muted">Chọn phòng chiếu. Xem timeline để biết phòng nào còn trống khung giờ nào.</small>
            </div>
            <div class="card-body">
                {{-- Danh sách phòng dạng grid --}}
                <div id="roomGrid" class="row g-3 mb-4">
                    <div class="text-center text-muted py-4">
                        <div class="spinner-border spinner-border-sm me-2"></div>
                        Đang tải danh sách phòng...
                    </div>
                </div>

                {{-- Hidden input lưu room_id --}}
                <input type="hidden" name="room_id" id="selectedRoomId" value="{{ old('room_id') }}">

                {{-- Timeline phòng đã chọn (ẩn, hiện khi click phòng) --}}
                <div id="roomTimelineSection" style="display:none;">
                    <hr>
                    <h6 class="mb-3"><i class="bi bi-clock-history me-2"></i>Timeline phòng: <strong id="timelineRoomName"></strong></h6>

                    {{-- Timeline bar 6:00 → 24:00 --}}
                    <div class="timeline-container mb-3">
                        <div class="timeline-header d-flex justify-content-between">
                            <span>06:00</span><span>08:00</span><span>10:00</span><span>12:00</span>
                            <span>14:00</span><span>16:00</span><span>18:00</span><span>20:00</span>
                            <span>22:00</span><span>24:00</span>
                        </div>
                        <div class="timeline-bar" id="timelineBar">
                            {{-- Các block suất chiếu sẽ render bằng JS --}}
                        </div>
                    </div>

                    {{-- Chú thích timeline --}}
                    <div class="d-flex gap-3 flex-wrap small mb-3">
                        <span><span class="timeline-legend" style="background:#dc3545;"></span> Đang chiếu / Đã xếp</span>
                        <span><span class="timeline-legend" style="background:#198754;"></span> Khoảng trống</span>
                        <span><span class="timeline-legend" style="background:rgba(108,117,125,0.3);"></span> Buffer 15 phút vệ sinh</span>
                    </div>

                    {{-- Danh sách suất chiếu chi tiết trong phòng --}}
                    <div id="roomShowtimeList"></div>
                </div>
            </div>
            <div class="card-footer bg-transparent d-flex justify-content-between gap-2">
                <button type="button" class="btn btn-outline-secondary" id="btnBack3">
                    <i class="bi bi-arrow-left me-1"></i> Quay lại
                </button>
                <button type="button" class="btn btn-primary" id="btnNext3" disabled>
                    Tiếp theo <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- ============================================================
         BƯỚC 4: CHỌN GIỜ + ĐỊNH DẠNG + NGÔN NGỮ + XÁC NHẬN
         - Time picker chọn giờ bắt đầu
         - Preview block thời gian (start → end)
         - Kiểm tra trùng lịch realtime
         - Chọn định dạng (2D/3D/IMAX) + ngôn ngữ
         - Nút submit form
         ============================================================ --}}
    <div class="wizard-panel" id="panel4" style="display:none;">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent">
                <div class="fw-semibold"><i class="bi bi-clock me-2"></i>Bước 4: Chọn giờ chiếu & Xác nhận</div>
                <small class="text-muted">Chọn giờ bắt đầu, hệ thống tự tính giờ kết thúc và kiểm tra trùng lịch.</small>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    {{-- Giờ bắt đầu --}}
                    <div class="col-12 col-lg-4">
                        <label class="form-label">Giờ bắt đầu <span class="text-danger">*</span></label>
                        <input type="time" id="selectTime" class="form-control @error('start_time') is-invalid @enderror" required>
                        @error('start_time')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Hidden start_time (ghép date + time) --}}
                    <input type="hidden" name="start_time" id="hiddenStartTime" value="{{ old('start_time') }}">

                    {{-- Định dạng --}}
                    <div class="col-12 col-lg-4">
                        <label class="form-label">Định dạng <span class="text-danger">*</span></label>
                        <select name="format" class="form-select @error('format') is-invalid @enderror" required>
                            <option value="2D" {{ old('format', '2D') === '2D' ? 'selected' : '' }}>2D</option>
                            <option value="3D" {{ old('format') === '3D' ? 'selected' : '' }}>3D</option>
                            <option value="IMAX" {{ old('format') === 'IMAX' ? 'selected' : '' }}>IMAX</option>
                        </select>
                        @error('format')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Ngôn ngữ --}}
                    <div class="col-12 col-lg-4">
                        <label class="form-label">Ngôn ngữ <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            name="language_type"
                            class="form-control @error('language_type') is-invalid @enderror"
                            value="{{ old('language_type') }}"
                            placeholder="Ví dụ: Phụ đề, Lồng tiếng"
                            required
                        >
                        @error('language_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Preview thời gian + Kiểm tra trùng lịch --}}
                <div id="timePreview" class="mt-4" style="display:none;">
                    <div class="row g-3">
                        {{-- Preview block thời gian --}}
                        <div class="col-lg-6">
                            <div class="card border h-100" id="timePreviewCard">
                                <div class="card-body">
                                    <h6 class="mb-3"><i class="bi bi-clock-fill me-2"></i>Thời gian suất chiếu</h6>
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <div class="text-center">
                                            <div class="text-muted small">Bắt đầu</div>
                                            <div class="fs-3 fw-bold text-success" id="previewStart">--:--</div>
                                        </div>
                                        <div class="fs-4 text-muted"><i class="bi bi-arrow-right"></i></div>
                                        <div class="text-center">
                                            <div class="text-muted small">Kết thúc</div>
                                            <div class="fs-3 fw-bold text-danger" id="previewEnd">--:--</div>
                                        </div>
                                        <div class="ms-3">
                                            <div class="text-muted small">Thời lượng</div>
                                            <div class="fw-bold" id="previewDuration">-- phút</div>
                                        </div>
                                    </div>
                                    <div class="small text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Giờ kết thúc tự động tính từ thời lượng phim. Hệ thống thêm 15 phút buffer vệ sinh giữa các suất.
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- Kết quả kiểm tra trùng lịch --}}
                        <div class="col-lg-6">
                            <div class="card border h-100" id="conflictResultCard">
                                <div class="card-body d-flex flex-column justify-content-center align-items-center">
                                    <div id="conflictLoading" style="display:none;">
                                        <div class="spinner-border text-primary mb-2"></div>
                                        <div class="text-muted">Đang kiểm tra trùng lịch...</div>
                                    </div>
                                    <div id="conflictOk" style="display:none;">
                                        <div class="text-success mb-2"><i class="bi bi-check-circle-fill fs-1"></i></div>
                                        <div class="fw-bold text-success">Không trùng lịch!</div>
                                        <div class="text-muted small mt-1">Khung giờ này còn trống, có thể tạo suất chiếu.</div>
                                    </div>
                                    <div id="conflictError" style="display:none;">
                                        <div class="text-danger mb-2"><i class="bi bi-x-circle-fill fs-1"></i></div>
                                        <div class="fw-bold text-danger">Trùng lịch!</div>
                                        <div class="text-muted small mt-1" id="conflictMessage">Phòng chiếu đã có suất chiếu trong khung giờ này.</div>
                                    </div>
                                    <div id="conflictIdle">
                                        <div class="text-muted mb-2"><i class="bi bi-hourglass fs-1"></i></div>
                                        <div class="text-muted">Chọn giờ bắt đầu để kiểm tra trùng lịch</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tóm tắt tất cả lựa chọn --}}
                <div id="finalSummary" class="mt-4" style="display:none;">
                    <div class="alert alert-primary border-0">
                        <h6 class="alert-heading"><i class="bi bi-clipboard-check me-2"></i>Tóm tắt suất chiếu</h6>
                        <div class="row g-2">
                            <div class="col-sm-6"><strong>Phim:</strong> <span id="sumMovie">—</span></div>
                            <div class="col-sm-6"><strong>Phòng:</strong> <span id="sumRoom">—</span></div>
                            <div class="col-sm-6"><strong>Ngày:</strong> <span id="sumDate">—</span></div>
                            <div class="col-sm-6"><strong>Giờ:</strong> <span id="sumTime">—</span></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent d-flex justify-content-between gap-2">
                <button type="button" class="btn btn-outline-secondary" id="btnBack4">
                    <i class="bi bi-arrow-left me-1"></i> Quay lại
                </button>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.showtime') }}" class="btn btn-outline-secondary">Hủy</a>
                    <button type="submit" class="btn btn-primary" id="btnSubmit" disabled>
                        <i class="bi bi-save me-1"></i>
                        Tạo suất chiếu
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

{{-- ===== CSS cho wizard, timeline, room cards ===== --}}
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

/* ---------- TIMELINE BAR ---------- */
.timeline-container {
    position: relative;
}
.timeline-header {
    font-size: .7rem;
    color: var(--bs-secondary-color);
    margin-bottom: 4px;
    padding: 0 2px;
}
.timeline-bar {
    position: relative;
    height: 48px;
    background: rgba(25,135,84,0.1);
    border: 1px solid var(--bs-border-color);
    border-radius: 8px;
    overflow: hidden;
}
.timeline-block {
    position: absolute;
    top: 4px;
    bottom: 4px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .65rem;
    font-weight: 600;
    color: #fff;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    padding: 0 4px;
    cursor: default;
    transition: opacity .2s;
}
.timeline-block:hover {
    opacity: .85;
}
.timeline-block.occupied {
    background: linear-gradient(135deg, #dc3545, #c82333);
}
.timeline-block.buffer-zone {
    background: rgba(108,117,125,0.3);
}
.timeline-block.preview-block {
    background: linear-gradient(135deg, #0d6efd, #0a58ca);
    border: 2px dashed #fff;
    animation: pulse-preview 1.5s infinite;
}
@keyframes pulse-preview {
    0%, 100% { opacity: 1; }
    50% { opacity: .7; }
}

/* ---------- TIMELINE LEGEND ---------- */
.timeline-legend {
    display: inline-block;
    width: 14px;
    height: 14px;
    border-radius: 3px;
    vertical-align: middle;
    margin-right: 4px;
}

/* ---------- ROOM CARDS ---------- */
.room-card {
    cursor: pointer;
    transition: all .2s ease;
    border: 2px solid transparent !important;
}
.room-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,.15);
}
.room-card.selected {
    border-color: #0d6efd !important;
    box-shadow: 0 0 0 3px rgba(13,110,253,.2);
}
.room-status-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
}
.room-status-dot.FREE { background: #198754; }
.room-status-dot.SHOWING { background: #dc3545; }
.room-status-dot.UPCOMING { background: #ffc107; }

/* ---------- SHOWTIME LIST IN TIMELINE ---------- */
.showtime-list-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 12px;
    border-radius: 8px;
    background: var(--bs-tertiary-bg);
    margin-bottom: 6px;
}
</style>

@push('scripts')
<script>
/**
 * ============================================================
 * WIZARD TẠO SUẤT CHIẾU - JAVASCRIPT
 * ============================================================
 * Xử lý chuyển bước, gọi API, render timeline, kiểm tra trùng lịch
 * ============================================================
 */
document.addEventListener('DOMContentLoaded', function() {
    // ===== BIẾN TOÀN CỤC =====
    const CSRF = '{{ csrf_token() }}';
    const API_MOVIE_INFO = '{{ route("admin.showtime.api.movie_info") }}';
    const API_ROOM_SCHEDULE = '{{ route("admin.showtime.api.room_schedule") }}';
    const API_ROOM_TIMELINE = '{{ route("admin.showtime.api.room_timeline") }}';
    const API_CHECK_CONFLICT = '{{ route("admin.showtimes.check-conflict") }}';

    let currentStep = 1;
    let selectedMovie = null;     // Object chứa thông tin phim đã chọn
    let selectedDate = null;      // String YYYY-MM-DD
    let selectedRoomId = null;    // ID phòng đã chọn
    let selectedRoomName = null;  // Tên phòng đã chọn
    let conflictStatus = null;    // true/false kết quả kiểm tra trùng lịch

    // ===== DOM REFERENCES =====
    const selectMovie = document.getElementById('selectMovie');
    const selectDate = document.getElementById('selectDate');
    const selectTime = document.getElementById('selectTime');
    const hiddenStartTime = document.getElementById('hiddenStartTime');
    const selectedRoomInput = document.getElementById('selectedRoomId');

    // ===== HÀM CHUYỂN BƯỚC WIZARD =====
    // Chuyển đến bước (step) được chỉ định, ẩn/hiện panel tương ứng,
    // cập nhật class active/completed cho thanh tiến trình
    function goToStep(step) {
        // Ẩn tất cả panel
        document.querySelectorAll('.wizard-panel').forEach(p => p.style.display = 'none');
        // Hiện panel đúng bước
        document.getElementById('panel' + step).style.display = 'block';

        // Cập nhật trạng thái wizard steps
        for (let i = 1; i <= 4; i++) {
            const el = document.getElementById('wizardStep' + i);
            el.classList.remove('active', 'completed');
            if (i < step) el.classList.add('completed');
            if (i === step) el.classList.add('active');
        }

        // Cập nhật thanh tiến trình active line
        const lineActive = document.getElementById('wizardLineActive');
        const percents = { 1: '0%', 2: '33%', 3: '66%', 4: '100%' };
        lineActive.style.width = percents[step] || '0%';

        currentStep = step;
    }

    // ============================================================
    // BƯỚC 1: Chọn phim → AJAX lấy thông tin phim
    // ============================================================
    selectMovie.addEventListener('change', function() {
        const movieId = this.value;
        const movieInfoCard = document.getElementById('movieInfoCard');
        const btnNext1 = document.getElementById('btnNext1');

        if (!movieId) {
            // Chưa chọn phim → ẩn card info, disable nút Next
            movieInfoCard.style.display = 'none';
            btnNext1.disabled = true;
            selectedMovie = null;
            return;
        }

        // Gọi API lấy thông tin phim
        fetch(API_MOVIE_INFO, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ movie_id: movieId })
        })
        .then(r => r.json())
        .then(data => {
            selectedMovie = data.movie;

            // Render thông tin phim lên card
            document.getElementById('moviePoster').src = data.movie.poster_url || '/assets/images/no-poster.png';
            document.getElementById('movieTitle').textContent = data.movie.title;
            document.getElementById('movieOriginalTitle').textContent = data.movie.original_title || '';
            document.getElementById('movieDuration').innerHTML = '<i class="bi bi-clock me-1"></i>' + data.movie.duration_minutes + ' phút';
            document.getElementById('movieAgeRating').textContent = data.movie.age_rating || 'N/A';
            document.getElementById('movieLanguage').textContent = data.movie.language || 'N/A';
            document.getElementById('movieDirector').textContent = data.movie.director || 'N/A';
            document.getElementById('movieGenres').textContent = data.movie.genres.join(', ') || 'N/A';

            // Hiện trạng thái phim
            const statusMap = { 'NOW_SHOWING': 'Đang chiếu', 'COMING_SOON': 'Sắp chiếu', 'STOPPED': 'Ngừng chiếu' };
            document.getElementById('movieStatus').textContent = statusMap[data.movie.status] || data.movie.status;

            // Hiện ngày phát hành
            let releaseText = '';
            if (data.movie.release_date) releaseText += formatDate(data.movie.release_date);
            if (data.movie.end_date) releaseText += ' → ' + formatDate(data.movie.end_date);
            document.getElementById('movieRelease').textContent = releaseText || 'Chưa xác định';

            movieInfoCard.style.display = 'block';
            btnNext1.disabled = false;
        })
        .catch(err => {
            console.error('Lỗi lấy thông tin phim:', err);
            movieInfoCard.style.display = 'none';
            btnNext1.disabled = true;
        });
    });

    // ============================================================
    // BƯỚC 2: Chọn ngày → AJAX lấy tổng quan lịch chiếu
    // ============================================================
    selectDate.addEventListener('change', function() {
        selectedDate = this.value;
        const btnNext2 = document.getElementById('btnNext2');
        const dateSummary = document.getElementById('dateSummary');

        if (!selectedDate || !selectedMovie) {
            dateSummary.style.display = 'none';
            btnNext2.disabled = true;
            return;
        }

        // Gọi API lấy thông tin phim + lịch chiếu trong ngày
        fetch(API_MOVIE_INFO, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ movie_id: selectedMovie.id, date: selectedDate })
        })
        .then(r => r.json())
        .then(data => {
            // Render tổng quan ngày
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

    // ============================================================
    // BƯỚC 3: Tải danh sách phòng + timeline
    // ============================================================
    // Hàm tải danh sách phòng cho ngày đã chọn
    function loadRoomSchedule() {
        const roomGrid = document.getElementById('roomGrid');
        roomGrid.innerHTML = '<div class="col-12 text-center text-muted py-4"><div class="spinner-border spinner-border-sm me-2"></div>Đang tải danh sách phòng...</div>';

        fetch(API_ROOM_SCHEDULE, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ date: selectedDate })
        })
        .then(r => r.json())
        .then(data => {
            renderRoomGrid(data.rooms);
        })
        .catch(err => {
            console.error('Lỗi tải danh sách phòng:', err);
            roomGrid.innerHTML = '<div class="col-12 text-center text-danger py-4"><i class="bi bi-exclamation-triangle me-2"></i>Không thể tải danh sách phòng</div>';
        });
    }

    // Render danh sách phòng dạng card grid
    // Mỗi phòng hiển thị: tên, loại, số ghế, trạng thái, số suất đã xếp
    function renderRoomGrid(rooms) {
        const grid = document.getElementById('roomGrid');
        if (!rooms || rooms.length === 0) {
            grid.innerHTML = '<div class="col-12 text-center text-muted py-4"><i class="bi bi-emoji-frown me-2"></i>Không có phòng nào đang hoạt động</div>';
            return;
        }

        let html = '';
        rooms.forEach(room => {
            const isSelected = selectedRoomId == room.id;
            html += `
                <div class="col-sm-6 col-lg-4 col-xl-3">
                    <div class="card room-card h-100 ${isSelected ? 'selected' : ''}" data-room-id="${room.id}" data-room-name="${room.name}">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="mb-0">${room.name}</h6>
                                <span class="room-status-dot ${room.status}" title="${room.status_label}"></span>
                            </div>
                            <div class="small text-muted mb-1"><i class="bi bi-display me-1"></i>${room.room_type || 'Thường'}</div>
                            <div class="small text-muted mb-2"><i class="bi bi-grid-3x3 me-1"></i>${room.total_seats} ghế</div>
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="badge ${room.status === 'FREE' ? 'text-bg-success' : room.status === 'SHOWING' ? 'text-bg-danger' : 'text-bg-warning'}">${room.status_label}</span>
                                <span class="small text-muted">${room.showtime_count} suất</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        grid.innerHTML = html;

        // Gắn sự kiện click cho từng room card
        document.querySelectorAll('.room-card').forEach(card => {
            card.addEventListener('click', function() {
                // Bỏ selected tất cả card khác
                document.querySelectorAll('.room-card').forEach(c => c.classList.remove('selected'));
                // Thêm selected cho card này
                this.classList.add('selected');

                selectedRoomId = this.dataset.roomId;
                selectedRoomName = this.dataset.roomName;
                selectedRoomInput.value = selectedRoomId;

                // Tải timeline cho phòng đã chọn
                loadRoomTimeline(selectedRoomId);

                // Enable nút Next
                document.getElementById('btnNext3').disabled = false;
            });
        });
    }

    // Tải timeline chi tiết của 1 phòng trong ngày
    // Render thanh timeline bar (6:00→24:00) với các block suất chiếu
    function loadRoomTimeline(roomId) {
        const section = document.getElementById('roomTimelineSection');
        section.style.display = 'block';
        document.getElementById('timelineRoomName').textContent = selectedRoomName;

        fetch(API_ROOM_TIMELINE, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ room_id: roomId, date: selectedDate })
        })
        .then(r => r.json())
        .then(data => {
            renderTimeline(data.showtimes);
            renderShowtimeList(data.showtimes, data.gaps);
        })
        .catch(err => {
            console.error('Lỗi tải timeline:', err);
        });
    }

    // Render timeline bar trực quan
    // Thanh ngang từ 6:00 (0%) → 24:00 (100%), mỗi suất chiếu là 1 block đỏ
    function renderTimeline(showtimes) {
        const bar = document.getElementById('timelineBar');
        const TIMELINE_START = 6;  // 6:00
        const TIMELINE_END = 24;   // 24:00
        const TOTAL_HOURS = TIMELINE_END - TIMELINE_START; // 18 giờ

        let html = '';
        showtimes.forEach(st => {
            // Tính vị trí % trên timeline
            const left = ((st.start_hour - TIMELINE_START) / TOTAL_HOURS) * 100;
            const width = ((st.end_hour - st.start_hour) / TOTAL_HOURS) * 100;

            html += `<div class="timeline-block occupied"
                          style="left:${Math.max(0, left)}%; width:${Math.max(1, width)}%;"
                          title="${st.movie_title} (${st.start_time} - ${st.end_time})">
                        ${st.start_time}
                     </div>`;
        });
        bar.innerHTML = html;
    }

    // Render danh sách suất chiếu + khoảng trống
    function renderShowtimeList(showtimes, gaps) {
        const list = document.getElementById('roomShowtimeList');
        let html = '';

        if (showtimes.length === 0) {
            html = '<div class="text-center text-muted py-3"><i class="bi bi-calendar-check me-2"></i>Phòng chưa có suất chiếu nào trong ngày này — hoàn toàn trống!</div>';
        } else {
            html += '<div class="small fw-semibold mb-2"><i class="bi bi-list-ul me-1"></i>Các suất chiếu đã xếp:</div>';
            showtimes.forEach(st => {
                html += `
                    <div class="showtime-list-item">
                        <span class="badge text-bg-danger">${st.start_time} - ${st.end_time}</span>
                        <span class="fw-semibold">${st.movie_title}</span>
                        <span class="text-muted small">(${st.duration_minutes} phút)</span>
                    </div>
                `;
            });
        }

        // Hiện các khoảng trống
        if (gaps && gaps.length > 0) {
            html += '<div class="small fw-semibold mt-3 mb-2"><i class="bi bi-check-circle me-1 text-success"></i>Khung giờ còn trống:</div>';
            gaps.forEach(gap => {
                html += `
                    <div class="showtime-list-item" style="background: rgba(25,135,84,0.1);">
                        <span class="badge text-bg-success">${gap.start} - ${gap.end}</span>
                        <span class="text-success small">${gap.minutes} phút trống</span>
                    </div>
                `;
            });
        }

        list.innerHTML = html;
    }

    // ============================================================
    // BƯỚC 4: Chọn giờ → Kiểm tra trùng lịch realtime
    // ============================================================
    let conflictTimeout = null;

    // Khi người dùng chọn giờ bắt đầu:
    // 1. Ghép date + time → hidden start_time
    // 2. Tính giờ kết thúc từ duration_minutes
    // 3. Gọi API kiểm tra trùng lịch
    selectTime.addEventListener('change', function() {
        const time = this.value;
        if (!time || !selectedDate || !selectedMovie || !selectedRoomId) return;

        // Ghép date + time thành datetime-local format
        hiddenStartTime.value = selectedDate + 'T' + time;

        // Hiện preview
        document.getElementById('timePreview').style.display = 'block';
        document.getElementById('previewStart').textContent = time;

        // Tính giờ kết thúc
        const [hours, minutes] = time.split(':').map(Number);
        const endMinutes = hours * 60 + minutes + selectedMovie.duration_minutes;
        const endH = String(Math.floor(endMinutes / 60)).padStart(2, '0');
        const endM = String(endMinutes % 60).padStart(2, '0');
        document.getElementById('previewEnd').textContent = endH + ':' + endM;
        document.getElementById('previewDuration').textContent = selectedMovie.duration_minutes + ' phút';

        // Cập nhật tóm tắt
        document.getElementById('finalSummary').style.display = 'block';
        document.getElementById('sumMovie').textContent = selectedMovie.title;
        document.getElementById('sumRoom').textContent = selectedRoomName;
        document.getElementById('sumDate').textContent = formatDate(selectedDate);
        document.getElementById('sumTime').textContent = time + ' → ' + endH + ':' + endM;

        // Debounce kiểm tra trùng lịch (300ms)
        clearTimeout(conflictTimeout);
        conflictTimeout = setTimeout(() => checkConflict(time), 300);
    });

    // Gọi API kiểm tra trùng lịch
    function checkConflict(time) {
        showConflictState('loading');

        fetch(API_CHECK_CONFLICT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({
                room_id: selectedRoomId,
                movie_id: selectedMovie.id,
                start_time: selectedDate + 'T' + time
            })
        })
        .then(r => r.json())
        .then(data => {
            conflictStatus = data.conflict;
            if (data.conflict) {
                // Trùng lịch → hiện cảnh báo đỏ, disable nút submit
                showConflictState('error');
                document.getElementById('conflictMessage').textContent = data.message;
                document.getElementById('btnSubmit').disabled = true;
            } else {
                // Không trùng → hiện tick xanh, enable nút submit
                showConflictState('ok');
                document.getElementById('btnSubmit').disabled = false;
            }
        })
        .catch(err => {
            console.error('Lỗi kiểm tra trùng lịch:', err);
            showConflictState('error');
            document.getElementById('conflictMessage').textContent = 'Có lỗi khi kiểm tra trùng lịch.';
            document.getElementById('btnSubmit').disabled = true;
        });
    }

    // Hiện trạng thái kiểm tra trùng lịch (loading/ok/error/idle)
    function showConflictState(state) {
        ['Loading', 'Ok', 'Error', 'Idle'].forEach(s => {
            document.getElementById('conflict' + s).style.display = 'none';
        });
        const map = { loading: 'Loading', ok: 'Ok', error: 'Error', idle: 'Idle' };
        document.getElementById('conflict' + map[state]).style.display = 'block';
    }

    // ============================================================
    // ĐIỀU HƯỚNG CÁC NÚT NEXT/BACK
    // ============================================================
    document.getElementById('btnNext1').addEventListener('click', () => {
        goToStep(2);
        // Đặt min date = hôm nay
        selectDate.min = new Date().toISOString().split('T')[0];
    });

    document.getElementById('btnBack2').addEventListener('click', () => goToStep(1));
    document.getElementById('btnNext2').addEventListener('click', () => {
        goToStep(3);
        // Khi chuyển sang bước 3, tải danh sách phòng
        loadRoomSchedule();
    });

    document.getElementById('btnBack3').addEventListener('click', () => goToStep(2));
    document.getElementById('btnNext3').addEventListener('click', () => {
        goToStep(4);
        // Reset trạng thái trùng lịch
        showConflictState('idle');
        document.getElementById('btnSubmit').disabled = true;
    });

    document.getElementById('btnBack4').addEventListener('click', () => {
        goToStep(3);
        // Tải lại room schedule khi quay lại
        loadRoomSchedule();
    });

    // ============================================================
    // HÀM TIỆN ÍCH
    // ============================================================
    // Format ngày từ YYYY-MM-DD sang DD/MM/YYYY
    function formatDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    // Khởi tạo: nếu có old() values thì set lại
    @if(old('movie_id'))
        selectMovie.dispatchEvent(new Event('change'));
    @endif
});
</script>
@endpush

@endsection