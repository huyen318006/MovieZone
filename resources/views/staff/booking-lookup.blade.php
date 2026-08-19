@extends('layout.staff')

@section('title', 'Tra cứu Booking/Vé')
@section('page-title', 'Tra cứu Booking/Vé')

@section('content')
<div id="bookingLookupApp">

    {{-- ══════ SEARCH BAR ══════ --}}
    <div class="lookup-search-card">
        <div class="search-row">
            <div class="search-type-wrap">
                <select id="searchType" class="search-type-select">
                    <option value="booking_code">Mã Booking</option>
                    <option value="ticket_code">Mã Vé / QR</option>
                    <option value="phone">Số điện thoại</option>
                    <option value="email">Email</option>
                </select>
            </div>
            <div class="search-input-wrap">
                <i class="bi bi-search search-icon"></i>
                <input type="text" id="searchValue" class="search-input"
                       placeholder="Nhập mã booking, VD: BKXM7QP9RWBF"
                       autocomplete="off" spellcheck="false">
                <div id="recentSearchesDropdown" class="recent-searches-dropdown" style="display:none;"></div>
            </div>
            <button type="button" id="btnSearch" class="btn-search" disabled>
                <i class="bi bi-search"></i> Tìm kiếm
            </button>
        </div>

        {{-- Client-side validation error --}}
        <div id="validationError" class="validation-error" style="display:none;"></div>

        {{-- Quick Filters --}}
        <div class="quick-filters">
            <button class="qf-btn active" data-filter="all">
                <i class="bi bi-grid"></i> Tất cả
            </button>
            <button class="qf-btn" data-filter="today">
                <i class="bi bi-calendar-check"></i> Hôm nay
            </button>
            <button class="qf-btn" data-filter="issue">
                <i class="bi bi-exclamation-triangle"></i> Có vấn đề
            </button>
            <button class="qf-btn" data-filter="upcoming">
                <i class="bi bi-clock"></i> Sắp chiếu
            </button>
        </div>
    </div>

    {{-- ══════ FILTERS PANEL (Collapsible) ══════ --}}
    <div class="lookup-filter-card" id="filterPanel" style="display:none;">
        <div class="filter-row">
            <div class="filter-item">
                <label>Trạng thái Booking</label>
                <select id="filterBookingStatus" class="filter-select">
                    <option value="">Tất cả</option>
                    <option value="PENDING">Chờ thanh toán</option>
                    <option value="PAID">Đã thanh toán</option>
                    <option value="CANCELLED">Đã hủy</option>
                    <option value="EXPIRED">Hết hạn</option>
                </select>
            </div>
            <div class="filter-item">
                <label>Trạng thái thanh toán</label>
                <select id="filterPaymentStatus" class="filter-select">
                    <option value="">Tất cả</option>
                    <option value="UNPAID">Chưa thanh toán</option>
                    <option value="PAID">Đã thanh toán</option>
                    <option value="FAILED">Thất bại</option>
                    <option value="REFUNDED">Hoàn tiền</option>
                </select>
            </div>
            <div class="filter-item">
                <label>Ngày chiếu từ</label>
                <input type="date" id="filterDateFrom" class="filter-input">
            </div>
            <div class="filter-item">
                <label>Ngày chiếu đến</label>
                <input type="date" id="filterDateTo" class="filter-input">
            </div>
            <div class="filter-item">
                <label>Rạp</label>
                <select id="filterCinema" class="filter-select">
                    <option value="">Tất cả rạp</option>
                    @foreach ($cinemas as $cinema)
                        <option value="{{ $cinema->id }}">{{ $cinema->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="filter-actions">
            <button class="btn-filter-apply" onclick="BookingLookup.applyFilters()">
                <i class="bi bi-funnel"></i> Áp dụng
            </button>
            <button class="btn-filter-clear" onclick="BookingLookup.clearFilters()">
                <i class="bi bi-x-circle"></i> Xóa bộ lọc
            </button>
        </div>
    </div>

    {{-- ══════ RESULTS AREA ══════ --}}
    <div id="resultsArea">

        {{-- State: Empty (default) --}}
        <div id="stateEmpty" class="state-card">
            <div class="state-illustration">
                <i class="bi bi-search" style="font-size:64px; color: var(--staff-primary); opacity:0.5;"></i>
            </div>
            <h3>Tra cứu Booking / Vé</h3>
            <p>Nhập mã booking, mã vé, số điện thoại hoặc email để bắt đầu tìm kiếm.</p>
            <div class="shortcut-hints">
                <span class="shortcut-badge"><kbd>Ctrl</kbd>+<kbd>K</kbd> Focus tìm kiếm</span>
                <span class="shortcut-badge"><kbd>Enter</kbd> Tìm</span>
            </div>
        </div>

        {{-- State: Loading --}}
        <div id="stateLoading" class="state-card" style="display:none;">
            <div class="loading-spinner">
                <div class="spinner-border text-primary" role="status" style="width:3rem;height:3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
            <p style="margin-top:16px; color:var(--staff-text-muted);">Đang tìm kiếm...</p>
        </div>

        {{-- State: No Result --}}
        <div id="stateNoResult" class="state-card" style="display:none;">
            <div class="state-illustration">
                <i class="bi bi-inbox" style="font-size:64px; color:var(--staff-warning); opacity:0.7;"></i>
            </div>
            <h3>Không tìm thấy kết quả</h3>
            <p id="noResultMessage">Không có booking nào phù hợp với tiêu chí tìm kiếm.</p>
            <button class="btn-retry" onclick="BookingLookup.focusSearch()">
                <i class="bi bi-arrow-clockwise"></i> Tìm kiếm lại
            </button>
        </div>

        {{-- State: Error --}}
        <div id="stateError" class="state-card" style="display:none;">
            <div class="state-illustration">
                <i class="bi bi-exclamation-octagon" style="font-size:64px; color:var(--staff-danger); opacity:0.7;"></i>
            </div>
            <h3>Đã xảy ra lỗi</h3>
            <p id="errorMessage">Không thể kết nối đến server. Vui lòng thử lại.</p>
            <button class="btn-retry" onclick="BookingLookup.retrySearch()">
                <i class="bi bi-arrow-clockwise"></i> Thử lại
            </button>
        </div>

        {{-- State: Results --}}
        <div id="stateResults" style="display:none;">
            <div class="results-header">
                <div class="results-info">
                    <span id="resultCount">0</span> kết quả
                    <button class="btn-toggle-filter" onclick="BookingLookup.toggleFilters()">
                        <i class="bi bi-funnel"></i> Bộ lọc
                    </button>
                </div>
                <div class="results-sort">
                    <select id="sortBy" class="sort-select" onchange="BookingLookup.search()">
                        <option value="created_at">Mới nhất</option>
                        <option value="start_time">Ngày chiếu</option>
                        <option value="booking_code">Mã booking</option>
                    </select>
                </div>
            </div>

            <div class="results-table-wrap">
                <table class="results-table" id="resultsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Booking ID</th>
                            <th>Khách hàng</th>
                            <th>Phim</th>
                            <th>Suất chiếu</th>
                            <th>Ghế</th>
                            <th>Trạng thái</th>
                            <th>Thanh toán</th>
                            <th>Tổng tiền</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="resultsBody">
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="results-pagination" id="paginationWrap"></div>
        </div>
    </div>

    {{-- ══════ DETAIL PANEL (Slide from right) ══════ --}}
    <div class="detail-overlay" id="detailOverlay" style="display:none;" onclick="BookingLookup.closeDetail()"></div>
    <div class="detail-panel" id="detailPanel">
        <div class="detail-header">
            <h3 id="detailTitle">Chi tiết Booking</h3>
            <button class="detail-close" onclick="BookingLookup.closeDetail()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="detail-body" id="detailBody">
            {{-- Dynamically populated by JS --}}
            <div class="detail-loading">
                <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
            </div>
        </div>
    </div>
</div>

<style>
/* ══════ LOOKUP SEARCH CARD ══════ */
.lookup-search-card {
    background: var(--staff-surface); border: 1px solid var(--staff-border);
    border-radius: 14px; padding: 20px 24px; margin-bottom: 16px;
}
.search-row {
    display: flex; gap: 10px; align-items: center;
}
.search-type-wrap {}
.search-type-select {
    background: var(--staff-bg); color: var(--staff-text); border: 1px solid var(--staff-border);
    border-radius: 8px; padding: 10px 14px; font-size: 13px; min-width: 150px;
    cursor: pointer; outline: none; height: 44px;
}
.search-type-select:focus { border-color: var(--staff-primary); }
.search-input-wrap {
    flex: 1; position: relative;
}
.search-icon {
    position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
    color: var(--staff-text-muted); font-size: 16px;
}
.search-input {
    width: 100%; background: var(--staff-bg); color: var(--staff-text);
    border: 1px solid var(--staff-border); border-radius: 8px;
    padding: 10px 14px 10px 40px; font-size: 14px; outline: none; height: 44px;
    transition: border-color 0.2s;
}
.search-input:focus { border-color: var(--staff-primary); box-shadow: 0 0 0 3px rgba(139,92,246,0.15); }
.search-input::placeholder { color: var(--staff-text-muted); }

.btn-search {
    background: var(--staff-primary); color: #fff; border: none; border-radius: 8px;
    padding: 10px 20px; font-size: 14px; font-weight: 600; cursor: pointer;
    transition: all 0.2s; white-space: nowrap; height: 44px;
    display: flex; align-items: center; gap: 6px;
}
.btn-search:hover:not(:disabled) { background: var(--staff-primary-hover); transform: translateY(-1px); }
.btn-search:disabled { opacity: 0.5; cursor: not-allowed; }

.validation-error {
    margin-top: 8px; padding: 8px 14px; background: rgba(239,68,68,0.1);
    border: 1px solid rgba(239,68,68,0.3); border-radius: 8px;
    color: var(--staff-danger); font-size: 13px;
}

.quick-filters {
    display: flex; gap: 8px; margin-top: 14px; flex-wrap: wrap;
}
.qf-btn {
    background: var(--staff-bg); color: var(--staff-text-muted); border: 1px solid var(--staff-border);
    border-radius: 20px; padding: 6px 14px; font-size: 12px; cursor: pointer;
    display: flex; align-items: center; gap: 5px; transition: all 0.2s;
}
.qf-btn:hover { border-color: var(--staff-primary); color: var(--staff-primary); }
.qf-btn.active { background: rgba(139,92,246,0.15); border-color: var(--staff-primary); color: var(--staff-primary); font-weight: 600; }

/* ══════ RECENT SEARCHES DROPDOWN ══════ */
.recent-searches-dropdown {
    position: absolute; top: calc(100% + 4px); left: 0; right: 0;
    background: var(--staff-surface); border: 1px solid var(--staff-border);
    border-radius: 8px; max-height: 200px; overflow-y: auto; z-index: 60;
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
}
.recent-search-item {
    padding: 8px 14px; cursor: pointer; font-size: 13px; color: var(--staff-text-muted);
    display: flex; align-items: center; gap: 8px; transition: background 0.15s;
}
.recent-search-item:hover { background: var(--staff-surface-hover); color: var(--staff-text); }

/* ══════ FILTER PANEL ══════ */
.lookup-filter-card {
    background: var(--staff-surface); border: 1px solid var(--staff-border);
    border-radius: 14px; padding: 16px 24px; margin-bottom: 16px;
    animation: slideDown 0.3s ease;
}
@keyframes slideDown { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }

.filter-row { display: flex; gap: 12px; flex-wrap: wrap; }
.filter-item { flex: 1; min-width: 160px; }
.filter-item label { font-size: 11px; text-transform: uppercase; color: var(--staff-text-muted); letter-spacing: 0.5px; margin-bottom: 4px; display: block; font-weight: 500; }
.filter-select, .filter-input {
    width: 100%; background: var(--staff-bg); color: var(--staff-text);
    border: 1px solid var(--staff-border); border-radius: 6px;
    padding: 8px 10px; font-size: 13px; outline: none;
}
.filter-select:focus, .filter-input:focus { border-color: var(--staff-primary); }

.filter-actions { margin-top: 12px; display: flex; gap: 8px; justify-content: flex-end; }
.btn-filter-apply {
    background: var(--staff-primary); color: #fff; border: none; border-radius: 6px;
    padding: 7px 16px; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 5px;
}
.btn-filter-apply:hover { background: var(--staff-primary-hover); }
.btn-filter-clear {
    background: transparent; color: var(--staff-text-muted); border: 1px solid var(--staff-border);
    border-radius: 6px; padding: 7px 16px; font-size: 13px; cursor: pointer;
    display: flex; align-items: center; gap: 5px;
}
.btn-filter-clear:hover { color: var(--staff-text); border-color: var(--staff-text-muted); }

/* ══════ STATE CARDS ══════ */
.state-card {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 60px 20px; text-align: center;
    background: var(--staff-surface); border: 1px solid var(--staff-border);
    border-radius: 14px;
}
.state-card h3 { font-size: 18px; margin: 16px 0 6px; color: var(--staff-text); }
.state-card p { color: var(--staff-text-muted); font-size: 14px; max-width: 400px; margin: 0; }
.shortcut-hints { margin-top: 16px; display: flex; gap: 12px; flex-wrap: wrap; }
.shortcut-badge { font-size: 12px; color: var(--staff-text-muted); }
.shortcut-badge kbd {
    background: var(--staff-bg); border: 1px solid var(--staff-border);
    border-radius: 4px; padding: 2px 6px; font-size: 11px; font-family: inherit;
}
.btn-retry {
    margin-top: 16px; background: var(--staff-primary); color: #fff; border: none;
    border-radius: 8px; padding: 10px 20px; font-size: 14px; cursor: pointer;
    display: flex; align-items: center; gap: 6px;
}
.btn-retry:hover { background: var(--staff-primary-hover); }

/* ══════ RESULTS TABLE ══════ */
.results-header {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 12px;
}
.results-info { display: flex; align-items: center; gap: 12px; font-size: 14px; color: var(--staff-text-muted); }
.results-info #resultCount { font-weight: 700; color: var(--staff-text); font-size: 16px; }
.btn-toggle-filter {
    background: transparent; border: 1px solid var(--staff-border); color: var(--staff-text-muted);
    border-radius: 6px; padding: 4px 10px; font-size: 12px; cursor: pointer;
    display: flex; align-items: center; gap: 4px;
}
.btn-toggle-filter:hover { border-color: var(--staff-primary); color: var(--staff-primary); }
.sort-select {
    background: var(--staff-surface); color: var(--staff-text); border: 1px solid var(--staff-border);
    border-radius: 6px; padding: 6px 10px; font-size: 12px; outline: none;
}

.results-table-wrap {
    background: var(--staff-surface); border: 1px solid var(--staff-border);
    border-radius: 14px; overflow: hidden;
}
.results-table { width: 100%; border-collapse: collapse; }
.results-table thead th {
    background: var(--staff-bg); padding: 12px 14px; font-size: 11px;
    text-transform: uppercase; letter-spacing: 0.8px; color: var(--staff-text-muted);
    font-weight: 600; text-align: left; border-bottom: 1px solid var(--staff-border);
    white-space: nowrap;
}
.results-table tbody td {
    padding: 12px 14px; font-size: 13px; border-bottom: 1px solid rgba(51,65,85,0.5);
    vertical-align: middle;
}
.results-table tbody tr { transition: background 0.15s; cursor: pointer; }
.results-table tbody tr:hover { background: var(--staff-surface-hover); }
.results-table tbody tr:last-child td { border-bottom: none; }

/* Badges */
.badge-status {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap;
}
.badge-PAID { background: rgba(16,185,129,0.15); color: #10b981; }
.badge-PENDING { background: rgba(245,158,11,0.15); color: #f59e0b; }
.badge-CANCELLED { background: rgba(239,68,68,0.15); color: #ef4444; }
.badge-EXPIRED { background: rgba(148,163,184,0.15); color: #94a3b8; }
.badge-UNPAID { background: rgba(245,158,11,0.15); color: #f59e0b; }
.badge-FAILED { background: rgba(239,68,68,0.15); color: #ef4444; }
.badge-REFUNDED { background: rgba(59,130,246,0.15); color: #3b82f6; }

.booking-code-link {
    color: var(--staff-primary); font-weight: 600; font-family: 'Courier New', monospace; font-size: 12px;
}

.btn-detail {
    background: rgba(139,92,246,0.1); color: var(--staff-primary); border: 1px solid rgba(139,92,246,0.3);
    border-radius: 6px; padding: 5px 12px; font-size: 12px; cursor: pointer; white-space: nowrap;
    transition: all 0.2s;
}
.btn-detail:hover { background: var(--staff-primary); color: #fff; }

/* ══════ PAGINATION ══════ */
.results-pagination {
    display: flex; justify-content: center; align-items: center; gap: 4px;
    margin-top: 16px;
}
.page-btn {
    background: var(--staff-surface); color: var(--staff-text-muted); border: 1px solid var(--staff-border);
    border-radius: 6px; padding: 6px 12px; font-size: 13px; cursor: pointer; transition: all 0.2s;
}
.page-btn:hover { border-color: var(--staff-primary); color: var(--staff-primary); }
.page-btn.active { background: var(--staff-primary); color: #fff; border-color: var(--staff-primary); }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }

/* ══════ DETAIL PANEL ══════ */
.detail-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5); z-index: 200; backdrop-filter: blur(2px);
}
.detail-panel {
    position: fixed; top: 0; right: -520px; width: 500px; max-width: 90vw; height: 100vh;
    background: var(--staff-surface); border-left: 1px solid var(--staff-border);
    z-index: 210; display: flex; flex-direction: column;
    transition: right 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: -8px 0 30px rgba(0,0,0,0.3);
}
.detail-panel.open { right: 0; }
.detail-header {
    display: flex; justify-content: space-between; align-items: center;
    padding: 16px 20px; border-bottom: 1px solid var(--staff-border);
}
.detail-header h3 { font-size: 16px; font-weight: 700; margin: 0; }
.detail-close {
    background: transparent; border: none; color: var(--staff-text-muted);
    font-size: 18px; cursor: pointer; padding: 4px 8px; border-radius: 6px;
}
.detail-close:hover { background: var(--staff-surface-hover); color: var(--staff-text); }
.detail-body { flex: 1; overflow-y: auto; padding: 20px; }
.detail-loading { display: flex; justify-content: center; padding: 40px; }

/* Detail sections */
.detail-section { margin-bottom: 20px; }
.detail-section-title {
    font-size: 12px; text-transform: uppercase; letter-spacing: 1px;
    color: var(--staff-primary); font-weight: 700; margin-bottom: 10px;
    padding-bottom: 6px; border-bottom: 1px solid var(--staff-border);
    display: flex; align-items: center; gap: 6px;
}
.detail-row {
    display: flex; justify-content: space-between; padding: 5px 0;
    font-size: 13px;
}
.detail-label { color: var(--staff-text-muted); }
.detail-value { color: var(--staff-text); font-weight: 500; text-align: right; max-width: 60%; word-break: break-word; }

/* Seat tags in detail */
.seat-tag {
    display: inline-flex; align-items: center; gap: 3px;
    background: var(--staff-bg); border: 1px solid var(--staff-border);
    border-radius: 4px; padding: 2px 8px; font-size: 12px; margin: 2px;
}
.seat-tag.vip { border-color: #eab308; color: #eab308; }
.seat-tag.sweetbox { border-color: #ec4899; color: #ec4899; }

/* Timeline */
.timeline-list { list-style: none; padding: 0; margin: 0; position: relative; }
.timeline-list::before {
    content: ''; position: absolute; left: 14px; top: 4px; bottom: 4px;
    width: 2px; background: var(--staff-border);
}
.timeline-item {
    display: flex; gap: 12px; padding: 8px 0; position: relative;
}
.timeline-icon {
    width: 30px; height: 30px; border-radius: 50%; background: var(--staff-bg);
    border: 2px solid var(--staff-border); display: flex; align-items: center;
    justify-content: center; font-size: 14px; flex-shrink: 0; z-index: 1;
}
.timeline-content { flex: 1; }
.timeline-desc { font-size: 13px; color: var(--staff-text); }
.timeline-time { font-size: 11px; color: var(--staff-text-muted); }

/* Ticket cards in detail */
.ticket-card {
    background: var(--staff-bg); border: 1px solid var(--staff-border);
    border-radius: 10px; padding: 12px; margin-bottom: 8px;
    display: flex; justify-content: space-between; align-items: center;
}
.ticket-card .ticket-info { flex: 1; }
.ticket-code { font-family: 'Courier New', monospace; font-size: 12px; font-weight: 600; color: var(--staff-primary); }
.ticket-seat { font-size: 12px; color: var(--staff-text-muted); margin-top: 2px; }
.ticket-checkin { font-size: 11px; color: var(--staff-text-muted); margin-top: 2px; }
</style>

@push('scripts')
<script>
const BookingLookup = (() => {
    // ── Config ──
    const TAB_TOKEN    = new URLSearchParams(window.location.search).get('tab_token') || '';
    const API_BASE     = '{{ url("staff/api") }}';
    const API_URL      = (path) => {
        const separator = path.includes('?') ? '&' : '?';
        return API_BASE + path + (TAB_TOKEN ? separator + 'tab_token=' + TAB_TOKEN : '');
    };
    const CSRF_TOKEN   = document.querySelector('meta[name="csrf-token"]').content;
    const RECENT_KEY   = 'mz_staff_recent_searches';
    const MAX_RECENT   = 5;

    // ── State ──
    let currentPage    = 1;
    let lastCriteria   = null;
    let debounceTimer  = null;

    // ── DOM refs ──
    const searchType   = document.getElementById('searchType');
    const searchValue  = document.getElementById('searchValue');
    const btnSearch    = document.getElementById('btnSearch');
    const filterPanel  = document.getElementById('filterPanel');

    // ── Placeholders ──
    const PLACEHOLDERS = {
        booking_code: 'Nhập mã booking, VD: BKXM7QP9RWBF',
        ticket_code:  'Nhập mã vé hoặc quét QR, VD: TK3QNH65UJP8H8',
        phone:        'Nhập số điện thoại, VD: 0912345678',
        email:        'Nhập email khách hàng',
    };

    // ── Validation Regex ──
    const VALIDATORS = {
        booking_code: { regex: /^BK[A-Z2-9]{10}$/, msg: 'Mã booking phải có dạng BK + 10 ký tự [A-Z2-9]' },
        ticket_code:  { regex: /^TK[A-Z2-9]{12}$/, msg: 'Mã vé phải có dạng TK + 12 ký tự [A-Z2-9]' },
        phone:        { regex: /^(0|\+84)(3|5|7|8|9)\d{8}$/, msg: 'Số điện thoại không hợp lệ (VD: 0912345678)' },
        email:        { regex: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, msg: 'Email không đúng định dạng' },
    };

    // ══════ INIT ══════
    function init() {
        // Search type change
        searchType.addEventListener('change', () => {
            searchValue.placeholder = PLACEHOLDERS[searchType.value];
            searchValue.value = '';
            btnSearch.disabled = true;
            hideValidationError();
        });

        // Input change
        searchValue.addEventListener('input', () => {
            btnSearch.disabled = searchValue.value.trim().length === 0;
            hideValidationError();
        });

        // Focus → show recent
        searchValue.addEventListener('focus', showRecentSearches);
        searchValue.addEventListener('blur', () => {
            setTimeout(() => document.getElementById('recentSearchesDropdown').style.display = 'none', 200);
        });

        // Enter → search
        searchValue.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); search(); }
        });

        // Ctrl+K → focus
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                searchValue.focus();
                searchValue.select();
            }
            if (e.key === 'Escape') closeDetail();
        });

        // Search button
        btnSearch.addEventListener('click', () => search());

        // Quick filters
        document.querySelectorAll('.qf-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.qf-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                applyQuickFilter(btn.dataset.filter);
            });
        });
    }

    // ══════ SEARCH ══════
    async function search(page = 1) {
        const type  = searchType.value;
        const value = searchValue.value.trim();

        if (!value) return;

        // Client-side validation
        const validator = VALIDATORS[type];
        if (validator && !validator.regex.test(value)) {
            showValidationError(validator.msg);
            return;
        }
        hideValidationError();

        currentPage = page;

        // Build criteria
        const criteria = {
            search_type:  type,
            search_value: value,
            page:         page,
            sort_by:      document.getElementById('sortBy')?.value || 'created_at',
            sort_dir:     'desc',
        };

        // Filters
        const bs = document.getElementById('filterBookingStatus')?.value;
        const ps = document.getElementById('filterPaymentStatus')?.value;
        const df = document.getElementById('filterDateFrom')?.value;
        const dt = document.getElementById('filterDateTo')?.value;
        const ci = document.getElementById('filterCinema')?.value;
        if (bs) criteria.booking_status = bs;
        if (ps) criteria.payment_status = ps;
        if (df) criteria.showtime_date_from = df;
        if (dt) criteria.showtime_date_to = dt;
        if (ci) criteria.cinema_id = ci;

        lastCriteria = criteria;

        // Save recent search
        saveRecentSearch(type, value);

        // UI: Show loading
        showState('loading');

        try {
            const params = new URLSearchParams(criteria);
            const resp   = await fetch(API_URL(`/bookings/search?${params}`), {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            });

            if (!resp.ok) {
                const err = await resp.json().catch(() => null);
                throw new Error(err?.error?.message || `Server trả về lỗi ${resp.status}`);
            }

            const json = await resp.json();

            if (!json.success || !json.data.items || json.data.items.length === 0) {
                document.getElementById('noResultMessage').textContent =
                    json.message || 'Không có booking nào phù hợp với tiêu chí tìm kiếm.';
                showState('noResult');
                return;
            }

            renderResults(json.data.items, json.data.pagination);
            showState('results');

        } catch (err) {
            console.error('Search error:', err);
            document.getElementById('errorMessage').textContent = err.message || 'Không thể kết nối đến server.';
            showState('error');
        }
    }

    // ══════ RENDER RESULTS ══════
    function renderResults(items, pagination) {
        const tbody = document.getElementById('resultsBody');
        tbody.innerHTML = '';

        document.getElementById('resultCount').textContent = pagination.total;

        items.forEach((item, idx) => {
            const startOffset = (pagination.current_page - 1) * pagination.per_page;
            const row = document.createElement('tr');
            row.onclick = () => openDetail(item.id);

            const showDate = item.showtime?.start_time
                ? new Date(item.showtime.start_time).toLocaleDateString('vi-VN', { day:'2-digit', month:'2-digit' })
                  + ' ' + new Date(item.showtime.start_time).toLocaleTimeString('vi-VN', { hour:'2-digit', minute:'2-digit' })
                : 'N/A';

            const seats = (item.seats || []).join(', ') || 'N/A';
            const amount = item.final_amount
                ? Number(item.final_amount).toLocaleString('vi-VN') + 'đ'
                : '0đ';

            row.innerHTML = `
                <td>${startOffset + idx + 1}</td>
                <td><span class="booking-code-link">${item.booking_code || 'N/A'}</span></td>
                <td>
                    <div style="font-weight:500;">${item.customer?.name || 'N/A'}</div>
                    <div style="font-size:11px; color:var(--staff-text-muted);">${item.customer?.phone || ''}</div>
                </td>
                <td style="max-width:160px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${item.movie?.title || 'N/A'}</td>
                <td><span style="white-space:nowrap;">${showDate}</span><br><small style="color:var(--staff-text-muted);">${item.showtime?.cinema_name || ''}</small></td>
                <td><small>${seats}</small></td>
                <td><span class="badge-status badge-${item.status}">${statusLabel(item.status)}</span></td>
                <td><span class="badge-status badge-${item.payment_status}">${paymentLabel(item.payment_status)}</span></td>
                <td style="font-weight:600; white-space:nowrap;">${amount}</td>
                <td><button class="btn-detail" onclick="event.stopPropagation(); BookingLookup.openDetail(${item.id})"><i class="bi bi-eye"></i> Chi tiết</button></td>
            `;
            tbody.appendChild(row);
        });

        renderPagination(pagination);
    }

    // ══════ PAGINATION ══════
    function renderPagination(pg) {
        const wrap = document.getElementById('paginationWrap');
        if (pg.last_page <= 1) { wrap.innerHTML = ''; return; }

        let html = `<button class="page-btn" ${pg.current_page <= 1 ? 'disabled' : ''} onclick="BookingLookup.search(${pg.current_page - 1})">◀</button>`;

        for (let i = 1; i <= pg.last_page; i++) {
            if (pg.last_page > 7 && i > 3 && i < pg.last_page - 2 && Math.abs(i - pg.current_page) > 1) {
                if (i === 4) html += `<span style="color:var(--staff-text-muted);padding:0 4px;">…</span>`;
                continue;
            }
            html += `<button class="page-btn ${i === pg.current_page ? 'active' : ''}" onclick="BookingLookup.search(${i})">${i}</button>`;
        }

        html += `<button class="page-btn" ${pg.current_page >= pg.last_page ? 'disabled' : ''} onclick="BookingLookup.search(${pg.current_page + 1})">▶</button>`;
        wrap.innerHTML = html;
    }

    // ══════ DETAIL PANEL ══════
    async function openDetail(bookingId) {
        const overlay = document.getElementById('detailOverlay');
        const panel   = document.getElementById('detailPanel');
        const body    = document.getElementById('detailBody');

        overlay.style.display = 'block';
        panel.classList.add('open');
        body.innerHTML = '<div class="detail-loading"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

        try {
            const resp = await fetch(API_URL(`/bookings/${bookingId}`), {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            });

            if (!resp.ok) throw new Error('Không thể tải chi tiết booking.');

            const json = await resp.json();
            if (!json.success) throw new Error('Dữ liệu không hợp lệ.');

            renderDetail(json.data);
        } catch (err) {
            body.innerHTML = `<div class="state-card"><i class="bi bi-exclamation-octagon" style="font-size:48px;color:var(--staff-danger);"></i><h3>Lỗi</h3><p>${err.message}</p></div>`;
        }
    }

    function closeDetail() {
        document.getElementById('detailOverlay').style.display = 'none';
        document.getElementById('detailPanel').classList.remove('open');
    }

    function renderDetail(d) {
        const body = document.getElementById('detailBody');
        document.getElementById('detailTitle').textContent = `Booking: ${d.booking_code}`;

        // Helper functions
        const fmtDate = (v) => v ? new Date(v).toLocaleString('vi-VN') : 'N/A';
        const fmtMoney = (v) => v ? Number(v).toLocaleString('vi-VN') + 'đ' : '0đ';

        let html = '';

        // Section 1: Customer
        html += `<div class="detail-section">
            <div class="detail-section-title"><i class="bi bi-person"></i> Thông tin khách hàng</div>
            <div class="detail-row"><span class="detail-label">Họ tên</span><span class="detail-value">${d.customer?.name || 'N/A'}</span></div>
            <div class="detail-row"><span class="detail-label">Email</span><span class="detail-value">${d.customer?.email || 'N/A'}</span></div>
            <div class="detail-row"><span class="detail-label">SĐT</span><span class="detail-value">${d.customer?.phone || 'N/A'}</span></div>
        </div>`;

        // Section 2: Movie & Showtime
        html += `<div class="detail-section">
            <div class="detail-section-title"><i class="bi bi-film"></i> Phim & Suất chiếu</div>
            <div class="detail-row"><span class="detail-label">Tên phim</span><span class="detail-value">${d.movie?.title || 'N/A'}</span></div>
            <div class="detail-row"><span class="detail-label">Định dạng</span><span class="detail-value">${d.showtime?.format || 'N/A'}</span></div>
            <div class="detail-row"><span class="detail-label">Ngôn ngữ</span><span class="detail-value">${d.showtime?.language_type || 'N/A'}</span></div>
            <div class="detail-row"><span class="detail-label">Giờ chiếu</span><span class="detail-value">${fmtDate(d.showtime?.start_time)}</span></div>
            <div class="detail-row"><span class="detail-label">Kết thúc</span><span class="detail-value">${fmtDate(d.showtime?.end_time)}</span></div>
        </div>`;

        // Section 3: Cinema & Room
        html += `<div class="detail-section">
            <div class="detail-section-title"><i class="bi bi-building"></i> Rạp & Phòng chiếu</div>
            <div class="detail-row"><span class="detail-label">Rạp</span><span class="detail-value">${d.cinema?.name || 'N/A'}</span></div>
            <div class="detail-row"><span class="detail-label">Địa chỉ</span><span class="detail-value">${d.cinema?.address || 'N/A'}</span></div>
            <div class="detail-row"><span class="detail-label">Phòng</span><span class="detail-value">${d.room?.name || 'N/A'} (${d.room?.room_type || ''})</span></div>
        </div>`;

        // Section 4: Seats
        if (d.seats && d.seats.length > 0) {
            const seatTags = d.seats.map(s => {
                const cls = s.seat_type === 'vip' ? 'vip' : s.seat_type === 'sweetbox' ? 'sweetbox' : '';
                return `<span class="seat-tag ${cls}">${s.seat_code} <small>${fmtMoney(s.price)}</small></span>`;
            }).join('');
            html += `<div class="detail-section">
                <div class="detail-section-title"><i class="bi bi-grid-3x3-gap"></i> Ghế đã đặt (${d.seats.length})</div>
                <div style="display:flex;flex-wrap:wrap;gap:2px;">${seatTags}</div>
            </div>`;
        }

        // Section 5: Combos
        if (d.combos && d.combos.length > 0) {
            let comboHtml = d.combos.map(c => `<div class="detail-row">
                <span class="detail-label">${c.name} x${c.quantity}</span>
                <span class="detail-value">${fmtMoney(c.total_price)}</span>
            </div>`).join('');
            html += `<div class="detail-section">
                <div class="detail-section-title"><i class="bi bi-cup-straw"></i> Combo</div>
                ${comboHtml}
            </div>`;
        }

        // Section 6: Voucher
        if (d.voucher) {
            const discountText = d.voucher.discount_type === 'PERCENT'
                ? `${d.voucher.discount_value}%` : fmtMoney(d.voucher.discount_value);
            html += `<div class="detail-section">
                <div class="detail-section-title"><i class="bi bi-percent"></i> Voucher</div>
                <div class="detail-row"><span class="detail-label">Mã</span><span class="detail-value">${d.voucher.code}</span></div>
                <div class="detail-row"><span class="detail-label">Giảm giá</span><span class="detail-value">${discountText}</span></div>
            </div>`;
        }

        // Section 7: Pricing + Payment
        html += `<div class="detail-section">
            <div class="detail-section-title"><i class="bi bi-credit-card"></i> Thanh toán</div>
            <div class="detail-row"><span class="detail-label">Tổng vé</span><span class="detail-value">${fmtMoney(d.pricing?.total_ticket_amount)}</span></div>
            <div class="detail-row"><span class="detail-label">Tổng combo</span><span class="detail-value">${fmtMoney(d.pricing?.total_combo_amount)}</span></div>
            <div class="detail-row"><span class="detail-label">Giảm giá</span><span class="detail-value" style="color:var(--staff-success);">-${fmtMoney(d.pricing?.discount_amount)}</span></div>
            <div class="detail-row" style="font-weight:700;font-size:15px;padding-top:8px;border-top:1px dashed var(--staff-border);">
                <span class="detail-label">Tổng thanh toán</span><span class="detail-value" style="color:var(--staff-primary);">${fmtMoney(d.pricing?.final_amount)}</span>
            </div>`;

        if (d.payment) {
            html += `<div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--staff-border);">
                <div class="detail-row"><span class="detail-label">Phương thức</span><span class="detail-value">${d.payment.payment_method || 'N/A'}</span></div>
                <div class="detail-row"><span class="detail-label">Mã giao dịch</span><span class="detail-value" style="font-family:monospace;">${d.payment.transaction_code || 'N/A'}</span></div>
                <div class="detail-row"><span class="detail-label">Trạng thái</span><span class="detail-value"><span class="badge-status badge-${d.payment.status}">${paymentLabel(d.payment.status)}</span></span></div>
                <div class="detail-row"><span class="detail-label">Thời gian</span><span class="detail-value">${fmtDate(d.payment.paid_at)}</span></div>
            </div>`;
        }
        html += `</div>`;

        // Section 8: Tickets/QR
        if (d.tickets && d.tickets.length > 0) {
            let ticketHtml = d.tickets.map(t => {
                return `<div class="ticket-card">
                    <div class="ticket-info">
                        <div class="ticket-code">${t.ticket_code}</div>
                        <div class="ticket-seat">Ghế: ${t.seat_code || 'N/A'}</div>
                    </div>
                </div>`;
            }).join('');
            html += `<div class="detail-section">
                <div class="detail-section-title"><i class="bi bi-qr-code"></i> Vé (${d.tickets.length})</div>
                ${ticketHtml}
            </div>`;
        }

        // Section 9: Timeline
        if (d.timeline && d.timeline.length > 0) {
            let timelineHtml = d.timeline.map(t => `
                <li class="timeline-item">
                    <div class="timeline-icon">${t.icon}</div>
                    <div class="timeline-content">
                        <div class="timeline-desc">${t.description}</div>
                        <div class="timeline-time">${fmtDate(t.timestamp)}</div>
                    </div>
                </li>
            `).join('');
            html += `<div class="detail-section">
                <div class="detail-section-title"><i class="bi bi-clock-history"></i> Timeline</div>
                <ul class="timeline-list">${timelineHtml}</ul>
            </div>`;
        }

        // Section 10: Status summary
        html += `<div class="detail-section">
            <div class="detail-section-title"><i class="bi bi-info-circle"></i> Trạng thái</div>
            <div class="detail-row"><span class="detail-label">Booking</span><span class="detail-value"><span class="badge-status badge-${d.status}">${statusLabel(d.status)}</span></span></div>
            <div class="detail-row"><span class="detail-label">Thanh toán</span><span class="detail-value"><span class="badge-status badge-${d.payment_status}">${paymentLabel(d.payment_status)}</span></span></div>
            <div class="detail-row"><span class="detail-label">Tạo lúc</span><span class="detail-value">${fmtDate(d.created_at)}</span></div>
        </div>`;

        body.innerHTML = html;
    }

    // ══════ HELPERS ══════
    function showState(name) {
        ['stateEmpty','stateLoading','stateNoResult','stateError','stateResults'].forEach(id => {
            document.getElementById(id).style.display = 'none';
        });
        const el = document.getElementById('state' + name.charAt(0).toUpperCase() + name.slice(1));
        if (el) el.style.display = name === 'results' ? 'block' : 'flex';
    }

    function showValidationError(msg) {
        const el = document.getElementById('validationError');
        el.textContent = '⚠️ ' + msg;
        el.style.display = 'block';
    }

    function hideValidationError() {
        document.getElementById('validationError').style.display = 'none';
    }

    function statusLabel(s) {
        return { PAID:'Đã thanh toán', PENDING:'Chờ thanh toán', CANCELLED:'Đã hủy', EXPIRED:'Hết hạn' }[s] || s || 'N/A';
    }

    function paymentLabel(s) {
        return { PAID:'Đã thanh toán', UNPAID:'Chưa thanh toán', FAILED:'Thất bại', REFUNDED:'Hoàn tiền' }[s] || s || 'N/A';
    }

    function toggleFilters() {
        const fp = document.getElementById('filterPanel');
        fp.style.display = fp.style.display === 'none' ? 'block' : 'none';
    }

    function applyFilters() { search(1); }

    function clearFilters() {
        document.getElementById('filterBookingStatus').value = '';
        document.getElementById('filterPaymentStatus').value = '';
        document.getElementById('filterDateFrom').value = '';
        document.getElementById('filterDateTo').value = '';
        document.getElementById('filterCinema').value = '';
        search(1);
    }

    function applyQuickFilter(filter) {
        // Reset filters first
        document.getElementById('filterBookingStatus').value = '';
        document.getElementById('filterPaymentStatus').value = '';
        document.getElementById('filterDateFrom').value = '';
        document.getElementById('filterDateTo').value = '';

        const today = new Date().toISOString().split('T')[0];

        switch(filter) {
            case 'today':
                document.getElementById('filterDateFrom').value = today;
                document.getElementById('filterDateTo').value = today;
                break;
            case 'issue':
                document.getElementById('filterPaymentStatus').value = 'FAILED';
                break;
            case 'upcoming':
                document.getElementById('filterDateFrom').value = today;
                break;
        }

        if (searchValue.value.trim()) search(1);
    }

    function focusSearch() { searchValue.focus(); searchValue.select(); }

    function retrySearch() { if (lastCriteria) search(currentPage); else focusSearch(); }

    // ── Recent Searches ──
    function saveRecentSearch(type, value) {
        let recent = JSON.parse(sessionStorage.getItem(RECENT_KEY) || '[]');
        recent = recent.filter(r => !(r.type === type && r.value === value));
        recent.unshift({ type, value, time: Date.now() });
        if (recent.length > MAX_RECENT) recent.pop();
        sessionStorage.setItem(RECENT_KEY, JSON.stringify(recent));
    }

    function showRecentSearches() {
        const dropdown = document.getElementById('recentSearchesDropdown');
        const recent = JSON.parse(sessionStorage.getItem(RECENT_KEY) || '[]');
        if (recent.length === 0) { dropdown.style.display = 'none'; return; }

        dropdown.innerHTML = recent.map(r => {
            const icon = { booking_code:'🎫', ticket_code:'🎟️', phone:'📞', email:'📧' }[r.type] || '🔍';
            return `<div class="recent-search-item" onclick="BookingLookup.useRecent('${r.type}','${r.value}')">${icon} ${r.value}</div>`;
        }).join('');
        dropdown.style.display = 'block';
    }

    function useRecent(type, value) {
        searchType.value = type;
        searchValue.value = value;
        searchValue.placeholder = PLACEHOLDERS[type];
        btnSearch.disabled = false;
        document.getElementById('recentSearchesDropdown').style.display = 'none';
        search();
    }

    // ── Init on DOM ready ──
    document.addEventListener('DOMContentLoaded', init);

    // ── Public API ──
    return { search, openDetail, closeDetail, toggleFilters, applyFilters, clearFilters, focusSearch, retrySearch, useRecent };
})();
</script>
@endpush
@endsection
