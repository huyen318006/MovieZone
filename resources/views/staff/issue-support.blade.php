@extends('layout.staff')

@section('title', 'Hỗ trợ sự cố đặt vé')
@section('page-title', '🔧 Hỗ trợ sự cố đặt vé')

@section('topbar-actions')
    <span class="text-muted small d-none d-md-inline">
        <i class="bi bi-clock-history me-1"></i>
        <span id="liveClock">{{ now()->format('H:i:s') }}</span>
    </span>
@endsection

@section('content')
<div id="issueSupportApp" class="issue-app">

    {{-- ══════ SEARCH BAR (Hiện đại) ══════ --}}
    <div class="card border-0 shadow-sm mb-4 issue-search-card">
        <div class="card-body p-4">
            {{-- Header --}}
            <div class="d-flex align-items-center gap-2 mb-3">
                <div class="icon-circle">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-0" style="color: var(--staff-text);">Trung tâm xử lý sự cố</h6>
                    <small class="text-muted">Nhập thông tin để hệ thống tự động chẩn đoán</small>
                </div>
                <div class="ms-auto d-none d-md-flex gap-1">
                    <span class="badge px-3 py-2" style="background: rgba(16,185,129,0.15); color: #10b981; font-size: 11px;">
                        <i class="bi bi-check-circle-fill me-1"></i> UC-STAFF-04
                    </span>
                </div>
            </div>

            {{-- Search Row --}}
            <div class="row g-2 align-items-center search-row">
                <div class="col-12 col-md-3">
                    <div class="search-type-group">
                        <select id="issueInputType" class="form-select issue-select">
                            <option value="booking_code"> Mã Booking</option>
                            <option value="ticket_code"> Mã Vé / QR</option>
                            <option value="phone"> Số điện thoại</option>
                            <option value="email">Email</option>
                            <option value="qr_content"> Nội dung QR</option>
                        </select>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="input-group input-group-lg issue-input-group">
                        <span class="input-group-text issue-input-icon">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="issueInputValue" class="form-control issue-input"
                               placeholder="Nhập mã booking, mã vé/QR, sđt hoặc email..."
                               autocomplete="off" spellcheck="false">
                        <button class="btn btn-clear" id="btnClearInput" style="display:none;" type="button">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div id="recentIssueSearchesDropdown" class="recent-searches-dropdown" style="display:none;"></div>
                </div>

                <div class="col-12 col-md-3">
                    <button type="button" id="btnIssueDiagnose" class="btn btn-diagnose w-100" disabled>
                        <i class="bi bi-shield-fill-check me-2"></i> Xác định sự cố
                        <span class="btn-shortcut d-none d-md-inline">↵</span>
                    </button>
                </div>
            </div>

            {{-- Validation Error --}}
            <div id="issueValidationError" class="validation-error" style="display:none;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <span id="issueValidationText"></span>
            </div>

            {{-- Shortcut hints --}}
            <div class="shortcut-hints mt-3">
                <span class="sc-hint"><kbd>Ctrl</kbd>+<kbd>K</kbd> Focus</span>
                <span class="sc-hint"><kbd>↵</kbd> Tra cứu</span>
                <span class="sc-hint"><kbd>Esc</kbd> Đóng kết quả</span>
            </div>
        </div>
    </div>

{{-- ══════ STATE: EMPTY ══════ --}}
    <div id="stateEmpty" class="state-card state-empty">
        <div class="state-illustration">
            <div class="ill-circle">
                <i class="bi bi-patch-question"></i>
            </div>
            <div class="ill-ring"></div>
        </div>
        <h5>Trung tâm hỗ trợ sự cố</h5>
        <p>Nhập thông tin tìm kiếm phía trên. Hệ thống sẽ tự động đối soát và đề xuất quy trình xử lý.</p>
        <div class="state-features">
            <div class="sf-item">
                <i class="bi bi-1-circle-fill text-primary"></i>
                <span>Chọn loại thông tin</span>
            </div>
            <div class="sf-item">
                <i class="bi bi-2-circle-fill text-primary"></i>
                <span>Nhập dữ liệu</span>
            </div>
            <div class="sf-item">
                <i class="bi bi-3-circle-fill text-primary"></i>
                <span>Nhận chẩn đoán tự động</span>
            </div>
        </div>

        {{-- Danh sách booking có vấn đề hiển thị mặc định --}}
        @if(!empty($problemBookings) && $problemBookings->isNotEmpty())
        <div class="problem-bookings mt-4 w-100 text-start">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-bold m-0 d-flex align-items-center gap-2" style="color: var(--staff-text);">
                    <i class="bi bi-exclamation-triangle-fill" style="color: var(--staff-warning);"></i>
                    Booking đang có vấn đề ({{ $problemBookings->count() }})
                </h6>
                <span class="badge" style="background: rgba(245,158,11,0.15); color: #f59e0b; font-size: 11px;">
                    Cần xử lý
                </span>
            </div>
            <div class="problem-bookings-list">
                @foreach($problemBookings as $pb)
                <button type="button" class="problem-booking-item" onclick="window.IssueSupport.diagnoseFromBookingCode('{{ $pb->booking_code }}')">
                    <div class="pb-left">
                        <span class="pb-code">{{ $pb->booking_code }}</span>
                        <span class="pb-customer">{{ $pb->customer_name }}</span>
                    </div>
                    <div class="pb-mid">
                        <span class="pb-movie">{{ $pb->movie_title }}</span>
                        <span class="pb-time">
                            {{ $pb->showtime ? \Carbon\Carbon::parse($pb->showtime)->format('d/m H:i') : 'N/A' }}
                            • {{ $pb->cinema_name }}
                        </span>
                    </div>
                    <div class="pb-right">
                        <span class="badge pb-badge" data-issue="{{ $pb->issue_type }}">{{ $pb->issue_label }}</span>
                        <span class="btn-diagnose-mini"><i class="bi bi-shield-check"></i> Chẩn đoán</span>
                    </div>
                </button>
                @endforeach
            </div>
        </div>
        @else
        <div class="no-problem mt-4">
            <span class="badge" style="background: rgba(16,185,129,0.15); color: #10b981; font-size: 12px;">
                <i class="bi bi-check-circle me-1"></i> Không có booking nào đang gặp vấn đề
            </span>
        </div>
        @endif
    </div>

    {{-- ══════ STATE: LOADING ══════ --}}
    <div id="stateLoading" class="state-card state-loading" style="display:none;">
        <div class="loading-animation">
            <div class="spinner-grow text-primary" role="status" style="width: 3.5rem; height: 3.5rem; animation-duration: 1.2s;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="spinner-grow text-primary spinner-delay-2" role="status" style="width: 2.5rem; height: 2.5rem; animation-duration: 1.2s;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="spinner-grow text-primary spinner-delay-4" role="status" style="width: 1.5rem; height: 1.5rem; animation-duration: 1.2s;">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
        <h6 class="fw-bold text-primary mt-3 mb-1">Đang truy vấn dữ liệu...</h6>
        <p class="text-muted small mb-0">Hệ thống đang kiểm tra trạng thái thanh toán và thông tin vé</p>
        <div class="loading-progress mt-3">
            <div class="progress" style="height: 4px; width: 200px; background: var(--staff-border);">
                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%; background: var(--staff-primary);"></div>
            </div>
        </div>
    </div>

    {{-- ══════ STATE: RESULT ══════ --}}
    <div id="issueResultCard" class="issue-result-wrapper" style="display:none;">
        <div class="row g-4">

            {{-- LEFT COLUMN --}}
            <div class="col-12 col-lg-7">
                {{-- Diagnosis Card --}}
                <div class="card border-0 shadow-sm mb-4 diagnosis-card">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="diagnosis-badge" id="issueType">N/A</span>
                            <span class="text-muted small">| Kết quả phân tích</span>
                            <span class="ms-auto diagnosis-time text-muted small">
                                <i class="bi bi-clock me-1"></i>
                                <span id="diagnosisTime"></span>
                            </span>
                        </div>
                        <h4 class="fw-bold mb-3 diagnosis-title" id="issueTitle">N/A</h4>
                        <div class="diagnosis-summary" id="issueSummary">
                            <i class="bi bi-info-circle me-2"></i>
                            <span>—</span>
                        </div>
                    </div>
                </div>

                {{-- Actions Card --}}
                <div class="card border-0 shadow-sm actions-card">
                    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                        <h6 class="fw-bold text-uppercase m-0 d-flex align-items-center gap-2 section-title">
                            <i class="bi bi-journal-check fs-5"></i> Quy trình hướng dẫn xử lý
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <ul id="issueActions" class="list-unstyled mb-0 action-list">
                            <li class="action-placeholder text-muted">
                                <i class="bi bi-arrow-right-circle me-2"></i>
                                Đang tải hướng dẫn...
                            </li>
                        </ul>

                        <hr class="my-4 border-light">

                        <div class="note-box">
                            <div class="d-flex gap-3 align-items-start">
                                <div class="note-icon">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                </div>
                                <div>
                                    <strong class="d-block mb-1">Lưu ý phân quyền nghiệp vụ:</strong>
                                    <p class="mb-0" style="font-size: 12.5px; line-height: 1.6;">
                                        Nếu trường hợp cần can thiệp vượt thẩm quyền nhân viên vận hành (hoàn tiền trực tiếp, điều chỉnh thủ công trạng thái cổng thanh toán, khôi phục booking đã hủy) →
                                        Vui lòng lập biên bản ghi nhận sự cố và chuyển tiếp bộ phận <strong>Admin hệ thống</strong> xử lý.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT COLUMN --}}
            <div class="col-12 col-lg-5">
                <div class="card border-0 shadow-sm info-card">
                    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                        <h6 class="fw-bold text-uppercase m-0 d-flex align-items-center gap-2 section-title" style="color: var(--staff-text-muted) !important;">
                            <i class="bi bi-ticket-perforated fs-5"></i> Thông tin giao dịch
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="info-list">
                            <div class="info-row">
                                <span class="info-label">
                                    <i class="bi bi-upc-scan me-2 text-muted"></i>Mã Booking
                                </span>
                                <span class="info-value booking-code-value" id="bookingCode">N/A</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">
                                    <i class="bi bi-tag me-2 text-muted"></i>Trạng thái vé
                                </span>
                                <span class="info-value">
                                    <span class="status-badge" id="bookingStatus">N/A</span>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">
                                    <i class="bi bi-credit-card me-2 text-muted"></i>Thanh toán
                                </span>
                                <span class="info-value">
                                    <span class="status-badge" id="paymentStatus">N/A</span>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">
                                    <i class="bi bi-clock me-2 text-muted"></i>Thời hạn giữ chỗ
                                </span>
                                <span class="info-value" id="expiredAt">N/A</span>
                            </div>
                            <div class="info-row total-row">
                                <span class="info-label fw-bold text-dark">
                                    <i class="bi bi-cash-coin me-2 text-primary"></i>Tổng tiền
                                </span>
                                <span class="info-value total-amount" id="finalAmount">0₫</span>
                            </div>
                        </div>

                        {{-- Mini status indicator --}}
                        <div class="status-indicator mt-3 p-3 rounded" id="statusIndicator" style="display:none;">
                            <div class="d-flex align-items-center gap-2">
                                <div class="status-dot" id="statusDot"></div>
                                <span class="small fw-semibold" id="statusText">Đã thanh toán</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
/* ═══════════════════════════════════════════
   ISSUE SUPPORT — STAFF PORTAL UI
   ═══════════════════════════════════════════ */

/* ── Search Card ── */
.issue-search-card {
    background: var(--staff-surface);
    border: 1px solid var(--staff-border) !important;
    border-radius: 16px !important;
    transition: border-color 0.3s;
}
.issue-search-card:focus-within {
    border-color: var(--staff-primary) !important;
}

.icon-circle {
    width: 42px; height: 42px; border-radius: 12px;
    background: linear-gradient(135deg, var(--staff-primary), #6d28d9);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 20px; flex-shrink: 0;
}

.issue-select {
    background: var(--staff-bg) !important;
    color: var(--staff-text) !important;
    border: 1px solid var(--staff-border) !important;
    border-radius: 10px !important;
    padding: 10px 14px !important;
    font-size: 14px !important;
    height: 48px !important;
    cursor: pointer;
    transition: all 0.2s;
}
.issue-select:focus {
    border-color: var(--staff-primary) !important;
    box-shadow: 0 0 0 3px rgba(139,92,246,0.15) !important;
}

.issue-input-group {
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid var(--staff-border);
    transition: border-color 0.2s;
    background: var(--staff-bg);
}
.issue-input-group:focus-within {
    border-color: var(--staff-primary);
    box-shadow: 0 0 0 3px rgba(139,92,246,0.12);
}
.issue-input-icon {
    background: var(--staff-bg) !important;
    border: none !important;
    color: var(--staff-text-muted) !important;
    font-size: 16px;
    padding: 0 0 0 14px !important;
}
.issue-input {
    background: var(--staff-bg) !important;
    border: none !important;
    color: var(--staff-text) !important;
    font-size: 15px !important;
    padding: 10px 0 10px 8px !important;
    height: 48px !important;
}
.issue-input::placeholder { color: var(--staff-text-muted); opacity: 0.7; }
.issue-input:focus { box-shadow: none !important; }

.btn-clear {
    background: transparent;
    border: none;
    color: var(--staff-text-muted);
    padding: 0 14px 0 4px;
    font-size: 14px;
    cursor: pointer;
    transition: color 0.2s;
}
.btn-clear:hover { color: var(--staff-text); }

.btn-diagnose {
    background: linear-gradient(135deg, var(--staff-primary), #7c3aed);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 12px 20px;
    font-size: 15px;
    font-weight: 600;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    position: relative;
    overflow: hidden;
}
.btn-diagnose:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(139,92,246,0.4);
}
.btn-diagnose:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: var(--staff-border);
}
.btn-diagnose:not(:disabled)::after {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
    transform: rotate(45deg) translateX(-100%);
    transition: transform 0.6s;
}
.btn-diagnose:not(:disabled):hover::after {
    transform: rotate(45deg) translateX(100%);
}
.btn-shortcut {
    background: rgba(255,255,255,0.2);
    border-radius: 4px;
    padding: 1px 8px;
    font-size: 12px;
    margin-left: auto;
}

.validation-error {
    margin-top: 12px;
    padding: 10px 16px;
    background: rgba(239,68,68,0.1);
    border: 1px solid rgba(239,68,68,0.25);
    border-radius: 10px;
    color: #fca5a5;
    font-size: 13px;
    display: flex;
    align-items: center;
}

.shortcut-hints { display: flex; gap: 12px; flex-wrap: wrap; }
.sc-hint { font-size: 12px; color: var(--staff-text-muted); }
.sc-hint kbd {
    background: var(--staff-bg);
    border: 1px solid var(--staff-border);
    border-radius: 4px;
    padding: 2px 6px;
    font-size: 11px;
    font-family: inherit;
    color: var(--staff-text-muted);
}

/* ── Problem Bookings List ── */
.problem-bookings {
    max-height: 420px;
    overflow-y: auto;
}
.problem-bookings-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.problem-booking-item {
    display: flex;
    align-items: center;
    gap: 16px;
    width: 100%;
    padding: 12px 16px;
    background: var(--staff-bg);
    border: 1px solid var(--staff-border);
    border-radius: 12px;
    cursor: pointer;
    text-align: left;
    transition: all 0.2s;
}
.problem-booking-item:hover {
    border-color: var(--staff-primary);
    background: var(--staff-surface-hover);
    transform: translateY(-1px);
}
.pb-left {
    display: flex;
    flex-direction: column;
    min-width: 150px;
}
.pb-code {
    font-family: 'Courier New', monospace;
    font-size: 13px;
    font-weight: 700;
    color: var(--staff-primary);
}
.pb-customer {
    font-size: 12px;
    color: var(--staff-text-muted);
}
.pb-mid {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
}
.pb-movie {
    font-size: 13px;
    font-weight: 600;
    color: var(--staff-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.pb-time {
    font-size: 11px;
    color: var(--staff-text-muted);
}
.pb-right {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}
.pb-badge {
    background: rgba(245,158,11,0.15);
    color: #f59e0b;
    font-size: 11px;
    padding: 4px 10px;
    white-space: nowrap;
}
.btn-diagnose-mini {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: rgba(139,92,246,0.1);
    color: var(--staff-primary);
    border: 1px solid rgba(139,92,246,0.3);
    border-radius: 6px;
    padding: 4px 10px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
}
.problem-booking-item:hover .btn-diagnose-mini {
    background: var(--staff-primary);
    color: #fff;
}
.no-problem {
    text-align: center;
}

/* ── State Cards ── */
.state-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    text-align: center;
    background: var(--staff-surface);
    border: 1px solid var(--staff-border);
    border-radius: 16px;
    min-height: 350px;
}
.state-card h5 { font-size: 18px; margin: 20px 0 8px; color: var(--staff-text); font-weight: 700; }
.state-card p { color: var(--staff-text-muted); font-size: 14px; max-width: 440px; line-height: 1.6; }

.state-illustration { position: relative; margin-bottom: 8px; }
.ill-circle {
    width: 80px; height: 80px; border-radius: 50%;
    background: rgba(139,92,246,0.1);
    display: flex; align-items: center; justify-content: center;
    font-size: 36px; color: var(--staff-primary);
    position: relative; z-index: 1;
}
.ill-ring {
    position: absolute; top: -8px; left: -8px;
    width: 96px; height: 96px; border-radius: 50%;
    border: 2px dashed rgba(139,92,246,0.2);
    animation: spin 20s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.state-features {
    display: flex; gap: 20px; margin-top: 16px; flex-wrap: wrap; justify-content: center;
}
.sf-item {
    display: flex; align-items: center; gap: 6px;
    font-size: 13px; color: var(--staff-text-muted);
}
.sf-item i { font-size: 16px; }

/* Loading */
.state-loading { min-height: 300px; }
.loading-animation { display: flex; align-items: center; justify-content: center; gap: 8px; }
.spinner-delay-2 { animation-delay: 0.2s !important; }
.spinner-delay-4 { animation-delay: 0.4s !important; }

.loading-progress .progress { margin: 0 auto; border-radius: 4px; overflow: hidden; background: var(--staff-border) !important; }

/* ── Recent Searches Dropdown ── */
.recent-searches-dropdown {
    position: absolute; top: calc(100% + 4px); left: 0; right: 0;
    background: var(--staff-surface);
    border: 1px solid var(--staff-border);
    border-radius: 10px;
    max-height: 200px; overflow-y: auto;
    z-index: 100;
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
}
.recent-search-item {
    padding: 10px 14px; cursor: pointer; font-size: 13px;
    color: var(--staff-text-muted);
    display: flex; align-items: center; gap: 8px;
    transition: background 0.15s;
    border-bottom: 1px solid rgba(51,65,85,0.3);
}
.recent-search-item:last-child { border-bottom: none; }
.recent-search-item:hover { background: var(--staff-surface-hover); color: var(--staff-text); }

/* ── Diagnosis Card ── */
.diagnosis-card {
    border-left: 5px solid var(--staff-warning) !important;
    border-radius: 16px !important;
    background: var(--staff-surface) !important;
}
.diagnosis-badge {
    background: rgba(245,158,11,0.15);
    color: #fbbf24;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}
.diagnosis-title { color: var(--staff-text); font-size: 18px; }
.diagnosis-summary {
    padding: 14px 16px;
    border-radius: 10px;
    background: var(--staff-bg);
    border-left: 3px solid var(--staff-primary);
    font-size: 13.5px;
    line-height: 1.6;
    color: var(--staff-text-muted);
    display: flex;
    align-items: flex-start;
    gap: 10px;
}
.diagnosis-summary i { flex-shrink: 0; margin-top: 2px; color: var(--staff-primary); }

/* ── Actions Card ── */
.actions-card {
    border-radius: 16px !important;
    background: var(--staff-surface) !important;
}
.section-title {
    color: var(--staff-primary) !important;
    font-size: 12px;
    letter-spacing: 1px;
}

.action-list { display: flex; flex-direction: column; gap: 6px; }
.action-list li {
    padding: 8px 12px;
    border-radius: 8px;
    background: rgba(139,92,246,0.05);
    font-size: 14px;
    color: var(--staff-text);
    display: flex;
    align-items: flex-start;
    gap: 10px;
    transition: background 0.2s;
}
.action-list li:hover { background: rgba(139,92,246,0.1); }
.action-list li i { flex-shrink: 0; margin-top: 3px; color: var(--staff-primary); font-size: 14px; }
.action-placeholder { opacity: 0.6; }

.note-box {
    background: rgba(239,68,68,0.06);
    border: 1px dashed rgba(239,68,68,0.2);
    border-radius: 10px;
    padding: 14px;
    font-size: 12.5px;
    color: #fca5a5;
}
.note-icon {
    width: 32px; height: 32px; border-radius: 8px;
    background: rgba(239,68,68,0.1);
    display: flex; align-items: center; justify-content: center;
    color: #ef4444; font-size: 16px; flex-shrink: 0;
}

/* ── Info Card ── */
.info-card {
    border-radius: 16px !important;
    background: var(--staff-surface) !important;
    position: sticky;
    top: 24px;
}
.info-list { display: flex; flex-direction: column; }
.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid rgba(51,65,85,0.4);
}
.info-row:last-child { border-bottom: none; }
.info-label {
    font-size: 13px;
    color: var(--staff-text-muted);
    display: flex;
    align-items: center;
}
.info-label i { font-size: 14px; width: 18px; }
.info-value {
    font-size: 13px;
    font-weight: 600;
    color: var(--staff-text);
    text-align: right;
}
.booking-code-value {
    font-family: 'Courier New', monospace;
    color: var(--staff-primary);
    font-size: 13px;
}
.total-row { padding-top: 14px; }
.total-amount {
    font-size: 20px;
    font-weight: 800;
    color: var(--staff-primary);
}

.status-badge {
    display: inline-flex; align-items: center;
    padding: 3px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.status-indicator {
    background: rgba(16,185,129,0.1);
    border: 1px solid rgba(16,185,129,0.2);
    border-radius: 10px;
}
.status-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #10b981;
    animation: pulse-dot 2s infinite;
}
@keyframes pulse-dot {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}

/* ── Animations ── */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.issue-result-wrapper { animation: fadeInUp 0.4s ease; }

/* ── Dark mode overrides ── */
[data-theme="dark"] .btn-clear { color: #94a3b8; }
[data-theme="dark"] .btn-clear:hover { color: #e2e8f0; }
[data-theme="dark"] .total-row .info-label { color: var(--staff-text) !important; }
</style>

@push('scripts')
<script>
const TAB_TOKEN = new URLSearchParams(window.location.search).get('tab_token');
const API_BASE = '{{ url("staff/api/issue-support") }}';
const API_DIAGNOSE_URL = `${API_BASE}/diagnose?tab_token=${TAB_TOKEN}`;
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content;

// ── DOM refs ──
const stateEmpty = document.getElementById('stateEmpty');
const stateLoading = document.getElementById('stateLoading');
const issueResultCard = document.getElementById('issueResultCard');
const issueInputType = document.getElementById('issueInputType');
const issueInputValue = document.getElementById('issueInputValue');
const btnIssueDiagnose = document.getElementById('btnIssueDiagnose');
const btnClearInput = document.getElementById('btnClearInput');
const issueValidationError = document.getElementById('issueValidationError');
const issueValidationText = document.getElementById('issueValidationText');

// ── State ──
function showState(name){
    stateEmpty.style.display = name === 'empty' ? 'flex' : 'none';
    stateLoading.style.display = name === 'loading' ? 'flex' : 'none';
    issueResultCard.style.display = 'none';
}

function showValidationError(msg){
    issueValidationText.textContent = msg;
    issueValidationError.style.display = 'flex';
}

function hideValidationError(){
    issueValidationError.style.display = 'none';
}

// ── Validators ──
const VALIDATORS = {
    booking_code: { regex: /^BK[A-Z2-9]{10}$/, msg: 'Mã booking phải có dạng BK + 10 ký tự [A-Z2-9]' },
    ticket_code:  { regex: /^TK[A-Z2-9]{12}$/, msg: 'Mã vé phải có dạng TK + 12 ký tự [A-Z2-9]' },
    phone:        { regex: /^(0|\+84)(3|5|7|8|9)\d{8}$/, msg: 'Số điện thoại không hợp lệ (VD: 0912345678)' },
    email:        { regex: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, msg: 'Email không đúng định dạng' },
    qr_content:   { regex: /^MZ\|.+\|.{12}$/, msg: 'QR content không đúng định dạng (MZ|code|checksum)' },
};

function validateClient(){
    const type = issueInputType.value;
    const val = issueInputValue.value.trim();
    if(!val) return false;

    const v = VALIDATORS[type];
    if(v && !v.regex.test(val)){
        showValidationError(v.msg);
        return false;
    }

    hideValidationError();
    return true;
}

// ── Placeholders ──
const PLACEHOLDERS = {
    booking_code: 'Nhập mã booking, VD: BKXM7QP9RWBF',
    ticket_code:  'Nhập mã vé hoặc quét QR, VD: TK3QNH65UJP8H8',
    phone:        'Nhập số điện thoại, VD: 0912345678',
    email:        'Nhập email khách hàng',
    qr_content:   'Dán nội dung QR code, VD: MZ|TK...|a1b2c3d4e5f6',
};

// ── Recent searches ──
const RECENT_KEY = 'mz_staff_issue_recent';

function saveRecentSearch(type, value) {
    let recent = JSON.parse(sessionStorage.getItem(RECENT_KEY) || '[]');
    recent = recent.filter(r => !(r.type === type && r.value === value));
    recent.unshift({ type, value, time: Date.now() });
    if (recent.length > 5) recent.pop();
    sessionStorage.setItem(RECENT_KEY, JSON.stringify(recent));
}

function showRecentSearches() {
    const dropdown = document.getElementById('recentIssueSearchesDropdown');
    const recent = JSON.parse(sessionStorage.getItem(RECENT_KEY) || '[]');
    if (recent.length === 0) { dropdown.style.display = 'none'; return; }
    const icons = { booking_code:'📋', ticket_code:'🎟️', phone:'📞', email:'📧', qr_content:'📷' };
    dropdown.innerHTML = recent.map(r =>
        `<div class="recent-search-item" onclick="useRecent('${r.type}','${r.value.replace(/'/g,"\\'")}')">
            ${icons[r.type] || '🔍'} ${r.value}
        </div>`
    ).join('');
    dropdown.style.display = 'block';
}

function useRecent(type, value) {
    issueInputType.value = type;
    issueInputValue.value = value;
    issueInputValue.placeholder = PLACEHOLDERS[type] || '';
    btnIssueDiagnose.disabled = false;
    checkClearButton();
    document.getElementById('recentIssueSearchesDropdown').style.display = 'none';
    diagnose();
}

// ── Clear button ──
function checkClearButton(){
    btnClearInput.style.display = issueInputValue.value.trim() ? 'block' : 'none';
}

// ── Input events ──
issueInputValue.addEventListener('input', () => {
    btnIssueDiagnose.disabled = issueInputValue.value.trim().length === 0;
    hideValidationError();
    checkClearButton();
});

issueInputValue.addEventListener('focus', showRecentSearches);
issueInputValue.addEventListener('blur', () => {
    setTimeout(() => document.getElementById('recentIssueSearchesDropdown').style.display = 'none', 200);
});

btnClearInput.addEventListener('click', () => {
    issueInputValue.value = '';
    btnIssueDiagnose.disabled = true;
    btnClearInput.style.display = 'none';
    issueInputValue.focus();
});

issueInputType.addEventListener('change', () => {
    issueInputValue.placeholder = PLACEHOLDERS[issueInputType.value] || '';
    hideValidationError();
    issueInputValue.value = '';
    btnIssueDiagnose.disabled = true;
    btnClearInput.style.display = 'none';
});

// ── Keyboard shortcuts ──
document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        issueInputValue.focus();
        issueInputValue.select();
    }
});

issueInputValue.addEventListener('keydown', (e) => {
    if(e.key === 'Enter') diagnose();
});

btnIssueDiagnose.addEventListener('click', diagnose);

// ── Diagnose API call ──
async function diagnose(){
    const type = issueInputType.value;
    const value = issueInputValue.value.trim();

    if(!value) return;
    if(!validateClient()) return;

    saveRecentSearch(type, value);
    showState('loading');
    issueResultCard.style.display = 'none';

    try{
        const resp = await fetch(API_DIAGNOSE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
            body: JSON.stringify({ input_type: type, input_value: value })
        });

        const json = await resp.json();
        if(!resp.ok || !json.success){
            throw new Error(json?.issue?.title || json?.error?.message || 'Lỗi hệ thống');
        }

        render(json);
        showState('empty');
        issueResultCard.style.display = 'block';
        issueResultCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    }catch(err){
        console.error('Diagnose error:', err);
        showState('empty');
        showValidationError(err.message || 'Không thể tra cứu. Vui lòng thử lại.');
    }
}

// ── Render Results ──
function fmt(dt){
    if(!dt) return 'N/A';
    try{ return new Date(dt).toLocaleString('vi-VN'); }catch(e){ return dt; }
}

function render(json){
    const issue = json.issue;

    // Diagnosis
    const typeEl = document.getElementById('issueType');
    typeEl.textContent = issue.type || 'N/A';

    // Color badge based on type
    typeEl.style.background = issue.type === 'READY_FOR_CHECKIN'
        ? 'rgba(16,185,129,0.15)' : issue.type === 'NOT_FOUND'
        ? 'rgba(239,68,68,0.15)' : 'rgba(245,158,11,0.15)';
    typeEl.style.color = issue.type === 'READY_FOR_CHECKIN'
        ? '#10b981' : issue.type === 'NOT_FOUND'
        ? '#ef4444' : '#fbbf24';

    document.getElementById('issueTitle').textContent = issue.title || 'N/A';

    const summaryEl = document.getElementById('issueSummary');
    summaryEl.innerHTML = '<i class="bi bi-info-circle me-2"></i><span>' + (issue.summary || '—') + '</span>';

    // Timestamp
    document.getElementById('diagnosisTime').textContent = new Date().toLocaleTimeString('vi-VN');

    // Booking Info
    document.getElementById('bookingCode').textContent = json.booking?.booking_code || 'N/A';
    document.getElementById('bookingStatus').textContent = json.booking?.status || 'N/A';
    document.getElementById('paymentStatus').textContent = json.booking?.payment_status || 'N/A';
    document.getElementById('expiredAt').textContent = fmt(json.booking?.expired_at);
    document.getElementById('finalAmount').textContent = json.booking?.final_amount
        ? Number(json.booking.final_amount).toLocaleString('vi-VN') + '₫'
        : '0₫';

    // Status badge styling
    ['bookingStatus','paymentStatus'].forEach(id => {
        const el = document.getElementById(id);
        const text = el.textContent;
        const colors = {
            'PAID': 'rgba(16,185,129,0.15)',
            'UNPAID': 'rgba(245,158,11,0.15)',
            'FAILED': 'rgba(239,68,68,0.15)',
            'REFUNDED': 'rgba(59,130,246,0.15)',
            'CANCELLED': 'rgba(239,68,68,0.15)',
            'PENDING': 'rgba(245,158,11,0.15)',
            'EXPIRED': 'rgba(148,163,184,0.15)',
        };
        const textColors = {
            'PAID': '#10b981',
            'UNPAID': '#f59e0b',
            'FAILED': '#ef4444',
            'REFUNDED': '#3b82f6',
            'CANCELLED': '#ef4444',
            'PENDING': '#f59e0b',
            'EXPIRED': '#94a3b8',
        };
        el.style.background = colors[text] || 'rgba(148,163,184,0.15)';
        el.style.color = textColors[text] || '#94a3b8';
    });

    // Actions
    const actionsEl = document.getElementById('issueActions');
    actionsEl.innerHTML = '';
    if(issue.actions && issue.actions.length > 0){
        issue.actions.forEach(a => {
            const li = document.createElement('li');
            li.innerHTML = '<i class="bi bi-arrow-right-circle"></i> ' + a;
            actionsEl.appendChild(li);
        });
    } else {
        actionsEl.innerHTML = '<li class="action-placeholder"><i class="bi bi-dash-circle"></i> Không có hướng dẫn cụ thể.</li>';
    }

    // Status indicator
    const indicator = document.getElementById('statusIndicator');
    const dot = document.getElementById('statusDot');
    const stText = document.getElementById('statusText');
    if(issue.type === 'READY_FOR_CHECKIN'){
        indicator.style.display = 'block';
        indicator.style.background = 'rgba(16,185,129,0.1)';
        indicator.style.border = '1px solid rgba(16,185,129,0.2)';
        dot.style.background = '#10b981';
        stText.textContent = '✅ Booking hợp lệ, sẵn sàng check-in';
    } else if(issue.type === 'NOT_FOUND'){
        indicator.style.display = 'block';
        indicator.style.background = 'rgba(239,68,68,0.1)';
        indicator.style.border = '1px solid rgba(239,68,68,0.2)';
        dot.style.background = '#ef4444';
        stText.textContent = '❌ Không tìm thấy thông tin';
    } else if(issue.type === 'BOOKING_EXPIRED' || issue.type === 'PAYMENT_NOT_UPDATED' || issue.type === 'PAYMENT_FAILED_OR_REFUNDED'){
        indicator.style.display = 'block';
        indicator.style.background = 'rgba(245,158,11,0.1)';
        indicator.style.border = '1px solid rgba(245,158,11,0.2)';
        dot.style.background = '#f59e0b';
        stText.textContent = '⚠️ ' + (issue.title || 'Có vấn đề cần xử lý');
    } else {
        indicator.style.display = 'none';
    }
}

// ── Live clock ──
function updateClock(){
    const el = document.getElementById('liveClock');
    if(el) el.textContent = new Date().toLocaleTimeString('vi-VN');
}
setInterval(updateClock, 1000);

// ── Public API: chẩn đoán nhanh 1 booking từ danh sách booking có vấn đề ──
function diagnoseFromBookingCode(bookingCode){
    if(!bookingCode) return;
    issueInputType.value = 'booking_code';
    issueInputValue.value = bookingCode;
    issueInputValue.placeholder = PLACEHOLDERS.booking_code;
    btnIssueDiagnose.disabled = false;
    checkClearButton();
    hideValidationError();
    diagnose();
}

// Expose public API cho các element dùng onclick inline
window.IssueSupport = {
    diagnoseFromBookingCode,
    diagnose,
    render,
    showState,
};
</script>
@endpush
@endsection

