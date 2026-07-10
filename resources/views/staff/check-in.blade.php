@extends('layout.staff')

@section('title', 'Check-in Vé QR')
@section('page-title', 'Check-in Vé QR')

@section('styles')
/* ══════ CHECK-IN PAGE ══════ */
.checkin-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    max-width: 1400px;
}

/* ── Scanner Panel ── */
.scanner-panel {
    background: var(--staff-surface);
    border: 1px solid var(--staff-border);
    border-radius: 16px;
    overflow: hidden;
}

.scanner-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--staff-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.scanner-header h3 {
    font-size: 15px;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.scanner-body {
    padding: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
}

/* Camera preview */
#qr-reader {
    width: 100%;
    max-width: 400px;
    aspect-ratio: 1;
    background: #0a0a0a;
    border-radius: 12px;
    overflow: hidden;
    position: relative;
    border: 2px solid var(--staff-border);
}

#qr-reader video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.scanner-overlay {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}

.scan-frame {
    width: 200px;
    height: 200px;
    border: 3px solid var(--staff-primary);
    border-radius: 16px;
    animation: scanPulse 2s ease-in-out infinite;
    box-shadow: 0 0 0 9999px rgba(0,0,0,0.4);
}

@keyframes scanPulse {
    0%, 100% { border-color: var(--staff-primary); }
    50% { border-color: var(--staff-success); }
}

.scanner-status {
    font-size: 13px;
    color: var(--staff-text-muted);
    text-align: center;
    display: flex;
    align-items: center;
    gap: 6px;
}

.scanner-status .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--staff-success);
    animation: blink 1.5s infinite;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

/* Buttons */
.scanner-actions {
    display: flex;
    gap: 10px;
    width: 100%;
    max-width: 400px;
}

.btn-scan {
    flex: 1;
    padding: 12px;
    border-radius: 10px;
    border: none;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;
}

.btn-scan-primary {
    background: var(--staff-primary);
    color: #fff;
}

.btn-scan-primary:hover { background: var(--staff-primary-hover); }

.btn-scan-secondary {
    background: var(--staff-surface-hover);
    color: var(--staff-text);
    border: 1px solid var(--staff-border);
}

.btn-scan-secondary:hover { background: var(--staff-border); }

/* ── Manual Input ── */
.manual-input-panel {
    background: var(--staff-surface);
    border: 1px solid var(--staff-border);
    border-radius: 16px;
    padding: 20px;
    display: none;
}

.manual-input-panel.show { display: block; }

.manual-form {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.manual-form select, .manual-form input {
    background: var(--staff-bg);
    border: 1px solid var(--staff-border);
    border-radius: 8px;
    padding: 10px 14px;
    color: var(--staff-text);
    font-size: 14px;
}

.manual-form select:focus, .manual-form input:focus {
    outline: none;
    border-color: var(--staff-primary);
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.15);
}

/* ── Result Panel (Right Side) ── */
.result-panel {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* Confirmation Card */
.confirm-card {
    background: var(--staff-surface);
    border: 1px solid var(--staff-border);
    border-radius: 16px;
    overflow: hidden;
    display: none;
    animation: slideUp 0.3s ease;
}

.confirm-card.show { display: block; }

@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.confirm-card-header {
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    font-size: 15px;
}

.confirm-card-header.valid {
    background: rgba(16, 185, 129, 0.1);
    color: var(--staff-success);
    border-bottom: 1px solid rgba(16, 185, 129, 0.2);
}

.confirm-card-header.invalid {
    background: rgba(239, 68, 68, 0.1);
    color: var(--staff-danger);
    border-bottom: 1px solid rgba(239, 68, 68, 0.2);
}

.confirm-card-header.warning {
    background: rgba(245, 158, 11, 0.1);
    color: var(--staff-warning);
    border-bottom: 1px solid rgba(245, 158, 11, 0.2);
}

.confirm-card-body { padding: 20px; }

.movie-info {
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
}

.movie-poster {
    width: 80px;
    height: 120px;
    border-radius: 8px;
    background: var(--staff-bg);
    object-fit: cover;
    flex-shrink: 0;
}

.movie-details h4 {
    font-size: 16px;
    font-weight: 700;
    margin: 0 0 8px;
}

.info-row {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: var(--staff-text-muted);
    margin-bottom: 4px;
}

.info-row i { width: 16px; text-align: center; color: var(--staff-primary); }

.ticket-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--staff-border);
}

.ticket-info-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.ticket-info-label {
    font-size: 11px;
    color: var(--staff-text-muted);
    text-transform: uppercase;
    letter-spacing: 1px;
}

.ticket-info-value {
    font-size: 14px;
    font-weight: 600;
}

.confirm-card-actions {
    padding: 16px 20px;
    border-top: 1px solid var(--staff-border);
    display: flex;
    gap: 10px;
}

.btn-confirm {
    flex: 1;
    padding: 14px;
    border-radius: 10px;
    border: none;
    font-weight: 700;
    font-size: 15px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;
}

.btn-confirm-success {
    background: var(--staff-success);
    color: #fff;
}

.btn-confirm-success:hover { background: #059669; transform: translateY(-1px); }

.btn-confirm-cancel {
    background: var(--staff-surface-hover);
    color: var(--staff-text-muted);
}

/* ── Success Overlay ── */
.success-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    animation: fadeIn 0.2s;
}

.success-overlay.show { display: flex; }

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

.success-card {
    background: var(--staff-surface);
    border-radius: 20px;
    padding: 40px;
    text-align: center;
    max-width: 400px;
    animation: scaleIn 0.3s ease;
}

@keyframes scaleIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }

.success-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(16, 185, 129, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    font-size: 36px;
    color: var(--staff-success);
}

.success-title { font-size: 22px; font-weight: 700; margin-bottom: 8px; }
.success-detail { font-size: 13px; color: var(--staff-text-muted); margin-bottom: 4px; }

/* ── History Panel ── */
.history-panel {
    background: var(--staff-surface);
    border: 1px solid var(--staff-border);
    border-radius: 16px;
    overflow: hidden;
}

.history-list { max-height: 400px; overflow-y: auto; }

.history-item {
    padding: 12px 20px;
    border-bottom: 1px solid var(--staff-border);
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 13px;
}

.history-item:last-child { border-bottom: none; }

.history-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
}

.history-icon.success { background: rgba(16, 185, 129, 0.15); color: var(--staff-success); }
.history-icon.failed { background: rgba(239, 68, 68, 0.15); color: var(--staff-danger); }

.history-info { flex: 1; }
.history-code { font-weight: 600; color: var(--staff-text); }
.history-meta { font-size: 11px; color: var(--staff-text-muted); }

/* ── Batch tickets list ── */
.tickets-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 12px;
}

.ticket-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    background: var(--staff-bg);
    border-radius: 8px;
    border: 1px solid var(--staff-border);
}

.ticket-row.disabled { opacity: 0.5; }

.ticket-row .seat-badge {
    background: var(--staff-primary);
    color: #fff;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
}

.ticket-row .status-badge {
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

.status-badge.unused { background: rgba(16, 185, 129, 0.15); color: var(--staff-success); }
.status-badge.used { background: rgba(245, 158, 11, 0.15); color: var(--staff-warning); }
.status-badge.cancelled { background: rgba(239, 68, 68, 0.15); color: var(--staff-danger); }

/* ── Responsive ── */
@media (max-width: 1200px) {
    .checkin-grid { grid-template-columns: 1fr; }
}
@endsection

@section('content')
<div class="checkin-grid">
    <!-- LEFT: Scanner -->
    <div>
        <div class="scanner-panel">
            <div class="scanner-header">
                <h3><i class="bi bi-qr-code-scan"></i> Quét mã QR</h3>
                <div class="scanner-status" id="scannerStatus">
                    <span class="dot"></span> Đang chờ quét...
                </div>
            </div>
            <div class="scanner-body">
                <div id="qr-reader">
                    <div class="scanner-overlay">
                        <div class="scan-frame"></div>
                    </div>
                </div>

                <div class="scanner-actions">
                    <button class="btn-scan btn-scan-primary" id="btnStartScan" onclick="toggleScanner()">
                        <i class="bi bi-camera-video"></i> Bắt đầu quét
                    </button>
                    <button class="btn-scan btn-scan-secondary" onclick="toggleManualInput()">
                        <i class="bi bi-keyboard"></i> Nhập mã
                    </button>
                </div>
            </div>
        </div>

        <!-- Manual Input -->
        <div class="manual-input-panel" id="manualPanel">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                <h4 style="font-size:15px; font-weight:600; margin:0; display:flex; align-items:center; gap:8px;">
                    <i class="bi bi-keyboard"></i> Nhập mã thủ công
                </h4>
                <button onclick="toggleManualInput()" style="background:transparent; border:none; color:var(--staff-primary); font-size:13px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:4px;">
                    <i class="bi bi-qr-code-scan"></i> Quét QR
                </button>
            </div>
            <div class="manual-form">
                <select id="manualType">
                    <option value="booking_code">Mã booking (BK...)</option>
                    <option value="ticket_code">Mã vé (TK...)</option>
                </select>
                <input type="text" id="manualCode" placeholder="VD: BKXM7QP9RWBF hoặc TK3QNH65UJP8H8"
                       onkeydown="if(event.key==='Enter') lookupManual()">
                <button class="btn-scan btn-scan-primary" onclick="lookupManual()" id="btnManualLookup">
                    <i class="bi bi-search"></i> Tra cứu
                </button>
            </div>
        </div>

        <!-- Recent History -->
        <div class="history-panel" style="margin-top: 16px;">
            <div class="scanner-header">
                <h3><i class="bi bi-clock-history"></i> Check-in gần đây</h3>
            </div>
            <div class="history-list" id="historyList">
                <div style="padding: 24px; text-align: center; color: var(--staff-text-muted); font-size: 13px;">
                    Chưa có check-in nào
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT: Result -->
    <div class="result-panel" id="resultPanel">
        <!-- Confirm Card (populated by JS) -->
        <div class="confirm-card" id="confirmCard"></div>

        <!-- Batch tickets panel (for manual booking lookup) -->
        <div class="confirm-card" id="batchPanel"></div>
    </div>
</div>

<!-- Success Overlay -->
<div class="success-overlay" id="successOverlay">
    <div class="success-card">
        <div class="success-icon"><i class="bi bi-check-lg"></i></div>
        <div class="success-title">CHECK-IN THÀNH CÔNG</div>
        <div class="success-detail" id="successTicketCode"></div>
        <div class="success-detail" id="successSeat"></div>
        <div class="success-detail" id="successTime"></div>
        <div style="display:flex; gap:10px; margin-top:20px; width:100%;">
            <button class="btn-scan btn-scan-primary" style="flex:1;" onclick="closeSuccess()">
                <i class="bi bi-qr-code-scan"></i> Quét vé tiếp theo
            </button>
            <button class="btn-scan btn-scan-secondary" style="flex:1;" id="btnPrintPDF" onclick="downloadPDF()">
                <i class="bi bi-file-earmark-pdf"></i> In PDF
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- html5-qrcode CDN -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
const API_BASE = '{{ url("staff/api/check-in") }}';
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

let html5QrCode = null;
let isScanning = false;
let scanCooldown = false;
const recentHistory = [];
let lastCheckedBookingId = null;

// ══════ QR SCANNER ══════

async function toggleScanner() {
    const btn = document.getElementById('btnStartScan');
    const status = document.getElementById('scannerStatus');

    if (isScanning) {
        await stopScanner();
        btn.innerHTML = '<i class="bi bi-camera-video"></i> Bắt đầu quét';
        status.innerHTML = '<span class="dot" style="background:var(--staff-warning);"></span> Camera tắt';
        return;
    }

    try {
        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode("qr-reader");
        }

        await html5QrCode.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: { width: 200, height: 200 }, aspectRatio: 1 },
            onQRSuccess,
            () => {} // ignore errors during scanning
        );

        isScanning = true;
        btn.innerHTML = '<i class="bi bi-stop-circle"></i> Dừng quét';
        status.innerHTML = '<span class="dot"></span> Đang quét...';

    } catch (err) {
        console.error('Camera error:', err);
        status.innerHTML = '<span style="color:var(--staff-danger);">❌ Lỗi camera: ' + err.message + '</span>';
        alert('Không thể mở camera. Sử dụng chức năng "Nhập mã" thay thế.');
    }
}

async function stopScanner() {
    if (html5QrCode && isScanning) {
        try {
            await html5QrCode.stop();
        } catch (e) {
            // ignore
        }
        isScanning = false;
    }
}

async function onQRSuccess(decodedText) {
    if (scanCooldown) return;
    scanCooldown = true;

    // Play beep sound
    playBeep();

    // Pause scanning briefly
    const status = document.getElementById('scannerStatus');
    status.innerHTML = '<span class="dot" style="background:var(--staff-info);"></span> Đang xử lý...';

    try {
        const res = await fetch(`${API_BASE}/scan`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
            body: JSON.stringify({ qr_content: decodedText }),
        });

        const data = await res.json();
        
        if (data.tickets) {
            showBatchPanel(data);
        } else {
            showConfirmCard(data);
        }
        
        status.innerHTML = '<span class="dot"></span> Đang quét...';

    } catch (err) {
        showError('Lỗi kết nối. Vui lòng kiểm tra mạng.');
        status.innerHTML = '<span class="dot" style="background:var(--staff-danger);"></span> Lỗi kết nối';
    }

    // Cooldown 2 giây chống quét trùng
    setTimeout(() => { scanCooldown = false; }, 2000);
}

// ══════ MANUAL LOOKUP ══════

function toggleManualInput() {
    const panel = document.getElementById('manualPanel');
    const scannerPanel = document.querySelector('.scanner-panel');
    panel.classList.toggle('show');
    if (panel.classList.contains('show')) {
        // Ẩn mục quét QR khi hiện panel nhập mã
        scannerPanel.style.display = 'none';
        document.getElementById('manualCode').focus();
    } else {
        // Hiện lại mục quét QR khi ẩn panel nhập mã
        scannerPanel.style.display = 'block';
    }
}

async function lookupManual() {
    const code = document.getElementById('manualCode').value.trim();
    const type = document.getElementById('manualType').value;
    const btn = document.getElementById('btnManualLookup');

    if (!code) {
        alert('Vui lòng nhập mã.');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Đang tra cứu...';

    try {
        const res = await fetch(`${API_BASE}/manual`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
            body: JSON.stringify({ code, type }),
        });

        const data = await res.json();

        if (data.tickets) {
            // Booking lookup → show batch panel
            showBatchPanel(data);
        } else {
            // Single ticket lookup
            showConfirmCard(data);
        }

    } catch (err) {
        showError('Lỗi kết nối. Vui lòng thử lại.');
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-search"></i> Tra cứu';
}

// ══════ CONFIRM CARD ══════

function showConfirmCard(data) {
    const card = document.getElementById('confirmCard');
    const ticket = data.data;

    if (!ticket && data.error) {
        card.innerHTML = `
            <div class="confirm-card-header invalid">
                <i class="bi bi-x-circle-fill"></i> ${data.error.message}
            </div>
        `;
        card.classList.add('show');
        return;
    }

    const headerClass = data.can_checkin ? 'valid' : (data.error?.code === 'TICKET_ALREADY_CHECKED_IN' ? 'warning' : 'invalid');
    const headerIcon = data.can_checkin ? 'bi-check-circle-fill' : (data.error?.code === 'TICKET_ALREADY_CHECKED_IN' ? 'bi-exclamation-triangle-fill' : 'bi-x-circle-fill');
    const headerText = data.can_checkin ? 'Vé hợp lệ — Sẵn sàng check-in' : (data.error?.message || 'Không thể check-in');

    const posterSrc = ticket.movie?.poster_url ? `/storage/${ticket.movie.poster_url}` : '';
    const posterImg = posterSrc ? `<img src="${posterSrc}" class="movie-poster" alt="poster">` : `<div class="movie-poster" style="display:flex;align-items:center;justify-content:center;color:var(--staff-text-muted);font-size:24px;"><i class="bi bi-film"></i></div>`;

    card.innerHTML = `
        <div class="confirm-card-header ${headerClass}">
            <i class="bi ${headerIcon}"></i> ${headerText}
        </div>
        <div class="confirm-card-body">
            <div class="movie-info">
                ${posterImg}
                <div class="movie-details">
                    <h4>${ticket.movie?.title || 'N/A'}</h4>
                    <div class="info-row"><i class="bi bi-calendar3"></i> ${formatDateTime(ticket.showtime?.start_time)}</div>
                    <div class="info-row"><i class="bi bi-building"></i> ${ticket.cinema?.name || 'N/A'}</div>
                    <div class="info-row"><i class="bi bi-door-open"></i> ${ticket.room?.name || 'N/A'} ${ticket.room?.room_type ? `(${ticket.room.room_type})` : ''}</div>
                    <div class="info-row"><i class="bi bi-person-badge"></i> ${ticket.booking?.customer_name || 'N/A'}</div>
                </div>
            </div>
            <div class="ticket-info-grid">
                <div class="ticket-info-item">
                    <span class="ticket-info-label">Mã booking</span>
                    <span class="ticket-info-value">${ticket.booking?.booking_code || 'N/A'}</span>
                </div>
                <div class="ticket-info-item">
                    <span class="ticket-info-label">Mã vé</span>
                    <span class="ticket-info-value">${ticket.ticket_code || 'N/A'}</span>
                </div>
                <div class="ticket-info-item">
                    <span class="ticket-info-label">Ghế</span>
                    <span class="ticket-info-value">${ticket.seat_code || 'N/A'} (${ticket.seat_type || ''})</span>
                </div>
                <div class="ticket-info-item">
                    <span class="ticket-info-label">Trạng thái</span>
                    <span class="ticket-info-value status-badge ${ticket.status?.toLowerCase()}">${ticket.status}</span>
                </div>
            </div>
            ${ticket.checked_in_at ? `
                <div style="margin-top:12px; padding:10px 14px; background:rgba(245,158,11,0.1); border-radius:8px; font-size:13px;">
                    <i class="bi bi-info-circle"></i> Đã check-in lúc <strong>${formatDateTime(ticket.checked_in_at)}</strong> bởi <strong>${ticket.checked_in_by_name || 'N/A'}</strong>
                </div>
            ` : ''}
        </div>
        ${data.can_checkin ? `
            <div class="confirm-card-actions">
                <button class="btn-confirm btn-confirm-success" onclick="confirmCheckIn(${ticket.id})">
                    <i class="bi bi-check-circle"></i> Xác nhận Check-in
                </button>
                <button class="btn-confirm btn-scan-secondary" style="flex: 0.5;" onclick="printBill('${ticket.booking?.booking_code}')">
                    <i class="bi bi-printer"></i> In
                </button>
                <button class="btn-confirm btn-confirm-cancel" onclick="cancelConfirm()">
                    <i class="bi bi-x-lg"></i> Hủy
                </button>
            </div>
        ` : `
            <div class="confirm-card-actions">
                <button class="btn-confirm btn-scan-secondary" onclick="printBill('${ticket.booking?.booking_code}')">
                    <i class="bi bi-printer"></i> In Hoá Đơn
                </button>
                <button class="btn-confirm btn-confirm-cancel" onclick="cancelConfirm()" style="flex:1;">
                    <i class="bi bi-qr-code-scan"></i> Quét vé khác
                </button>
            </div>
        `}
    `;

    card.classList.add('show');
    document.getElementById('batchPanel').classList.remove('show');
}

// ══════ BATCH PANEL ══════

function showBatchPanel(data) {
    const panel = document.getElementById('batchPanel');
    const booking = data.booking;
    const tickets = data.tickets || [];

    const checkableTickets = tickets.filter(t => t.can_checkin);
    const headerClass = data.can_checkin ? 'valid' : 'warning';
    const headerText = data.can_checkin
        ? `Booking tìm thấy — ${checkableTickets.length}/${tickets.length} vé có thể check-in`
        : (data.error?.message || 'Không thể check-in');

    let ticketsHtml = tickets.map(t => `
        <div class="ticket-row ${t.can_checkin ? '' : 'disabled'}">
            <input type="checkbox" class="batch-ticket-cb" value="${t.id}" ${t.can_checkin ? 'checked' : 'disabled'}
                   style="accent-color:var(--staff-primary); width:18px; height:18px;">
            <span class="seat-badge">${t.seat_code}</span>
            <span style="flex:1; font-size:13px;">${t.ticket_code}</span>
            <span class="status-badge ${t.status.toLowerCase()}">${t.status}</span>
            ${t.checked_in_at ? `<span style="font-size:11px; color:var(--staff-text-muted);">${formatDateTime(t.checked_in_at)}</span>` : ''}
        </div>
    `).join('');

    panel.innerHTML = `
        <div class="confirm-card-header ${headerClass}">
            <i class="bi bi-ticket-detailed"></i> ${headerText}
        </div>
        <div class="confirm-card-body">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
                <div>
                    <div style="font-size:13px; color:var(--staff-text-muted);">Booking</div>
                    <div style="font-weight:700;">${booking?.booking_code || 'N/A'}</div>
                </div>
                <div>
                    <div style="font-size:13px; color:var(--staff-text-muted);">Phim</div>
                    <div style="font-weight:600;">${booking?.movie_title || 'N/A'}</div>
                </div>
                <div>
                    <div style="font-size:13px; color:var(--staff-text-muted);">Suất</div>
                    <div style="font-weight:600;">${formatDateTime(booking?.start_time)}</div>
                </div>
            </div>
            <div class="tickets-list">${ticketsHtml}</div>
        </div>
        ${checkableTickets.length > 0 ? `
            <div class="confirm-card-actions">
                <button class="btn-confirm btn-confirm-success" onclick="confirmBatch(${booking?.id})">
                    <i class="bi bi-check-all"></i> Check-in đã chọn
                </button>
                <button class="btn-confirm btn-scan-secondary" style="flex: 0.5;" onclick="printBill('${booking?.booking_code}')">
                    <i class="bi bi-printer"></i> In
                </button>
                <button class="btn-confirm btn-confirm-cancel" onclick="cancelConfirm()">
                    <i class="bi bi-x-lg"></i> Đóng
                </button>
            </div>
        ` : `
            <div class="confirm-card-actions">
                <button class="btn-confirm btn-scan-secondary" onclick="printBill('${booking?.booking_code}')">
                    <i class="bi bi-printer"></i> In Hoá Đơn
                </button>
                <button class="btn-confirm btn-confirm-cancel" style="flex:1;" onclick="cancelConfirm()">
                    <i class="bi bi-x-lg"></i> Đóng
                </button>
            </div>
        `}
    `;

    panel.classList.add('show');
    document.getElementById('confirmCard').classList.remove('show');
}

// ══════ CONFIRM CHECK-IN ══════

async function confirmCheckIn(ticketId) {
    const btn = event.target.closest('button');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Đang xử lý...';

    try {
        const res = await fetch(`${API_BASE}/confirm`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
            body: JSON.stringify({ ticket_id: ticketId }),
        });

        const data = await res.json();

        if (data.success) {
            showSuccess(data.data);
            addToHistory(data.data, true);
        } else {
            showError(data.error?.message || 'Check-in thất bại.');
            addToHistory({ ticket_code: 'N/A', ...data.error }, false);
        }

    } catch (err) {
        showError('Lỗi kết nối.');
    }

    btn.disabled = false;
}

async function confirmBatch(bookingId) {
    const checkboxes = document.querySelectorAll('.batch-ticket-cb:checked:not(:disabled)');
    const ticketIds = Array.from(checkboxes).map(cb => parseInt(cb.value));

    if (ticketIds.length === 0) {
        alert('Vui lòng chọn ít nhất 1 vé.');
        return;
    }

    const btn = event.target.closest('button');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Đang xử lý...';

    try {
        const res = await fetch(`${API_BASE}/confirm-batch`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
            body: JSON.stringify({ booking_id: bookingId, ticket_ids: ticketIds }),
        });

        const data = await res.json();

        if (data.success) {
            const d = data.data;
            showSuccess({
                ticket_code: `${d.checked_in} vé`,
                seat_code: `Thành công: ${d.checked_in}, Thất bại: ${d.failed}`,
                checked_in_at: new Date().toLocaleString('vi-VN'),
            });
        } else {
            showError('Check-in hàng loạt thất bại.');
        }

    } catch (err) {
        showError('Lỗi kết nối.');
    }

    btn.disabled = false;
}

// ══════ UI HELPERS ══════

function showSuccess(data) {
    document.getElementById('successTicketCode').textContent = `Vé: ${data.ticket_code}`;
    document.getElementById('successSeat').textContent = `Ghế: ${data.seat_code} | ${data.room_name || ''}`;
    document.getElementById('successTime').textContent = `Thời gian: ${data.checked_in_at}`;
    // Lưu booking_id để in PDF
    if (data.booking_id) {
        lastCheckedBookingId = data.booking_id;
        document.getElementById('btnPrintPDF').style.display = 'inline-flex';
    } else {
        document.getElementById('btnPrintPDF').style.display = 'none';
    }
    document.getElementById('successOverlay').classList.add('show');
    cancelConfirm();
    playBeepSuccess();
}

async function downloadPDF() {
    if (!lastCheckedBookingId) {
        alert('Không tìm thấy thông tin booking để in PDF.');
        return;
    }
    const btn = document.getElementById('btnPrintPDF');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Đang tạo PDF...';
    try {
        const res = await fetch(`${API_BASE}/${lastCheckedBookingId}/download-pdf`);
        if (!res.ok) {
            const errData = await res.json().catch(() => null);
            alert(errData?.message || 'Không thể tạo PDF. Vui lòng thử lại.');
            return;
        }
        const blob = await res.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `ticket_${lastCheckedBookingId}.pdf`;
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);
    } catch (err) {
        alert('Lỗi kết nối khi tải PDF.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-file-earmark-pdf"></i> In PDF';
    }
}

function closeSuccess() {
    document.getElementById('successOverlay').classList.remove('show');
}

function showError(message) {
    const card = document.getElementById('confirmCard');
    card.innerHTML = `
        <div class="confirm-card-header invalid">
            <i class="bi bi-x-circle-fill"></i> ${message}
        </div>
        <div class="confirm-card-actions">
            <button class="btn-confirm btn-confirm-cancel" style="flex:1;" onclick="cancelConfirm()">
                <i class="bi bi-qr-code-scan"></i> Quét lại
            </button>
        </div>
    `;
    card.classList.add('show');
}

function cancelConfirm() {
    document.getElementById('confirmCard').classList.remove('show');
    document.getElementById('batchPanel').classList.remove('show');
}

function addToHistory(data, success) {
    recentHistory.unshift({ ...data, success, time: new Date() });
    if (recentHistory.length > 10) recentHistory.pop();
    renderHistory();
}

function renderHistory() {
    const list = document.getElementById('historyList');
    if (recentHistory.length === 0) {
        list.innerHTML = '<div style="padding:24px; text-align:center; color:var(--staff-text-muted); font-size:13px;">Chưa có check-in nào</div>';
        return;
    }

    list.innerHTML = recentHistory.map(item => `
        <div class="history-item">
            <div class="history-icon ${item.success ? 'success' : 'failed'}">
                <i class="bi ${item.success ? 'bi-check-lg' : 'bi-x-lg'}"></i>
            </div>
            <div class="history-info">
                <div class="history-code">${item.ticket_code || 'N/A'}</div>
                <div class="history-meta">${item.seat_code || ''} ${item.movie_title ? '| ' + item.movie_title : ''}</div>
            </div>
            <div style="font-size:11px; color:var(--staff-text-muted);">
                ${item.time.toLocaleTimeString('vi-VN')}
            </div>
        </div>
    `).join('');
}

function formatDateTime(dt) {
    if (!dt) return 'N/A';
    const d = new Date(dt);
    if (isNaN(d)) return dt;
    return d.toLocaleString('vi-VN', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit', year: 'numeric' });
}

function printBill(code) {
    if (!code || code === 'undefined') return;

    let iframe = document.getElementById('print-iframe-mz');
    if (!iframe) {
        iframe = document.createElement('iframe');
        iframe.id = 'print-iframe-mz';
        iframe.style.position = 'absolute';
        iframe.style.width = '0px';
        iframe.style.height = '0px';
        iframe.style.border = 'none';
        document.body.appendChild(iframe);
    }

    iframe.src = `/booking/bill/${code}?print=true`;
}

// ══════ SOUND EFFECTS ══════

function playBeep() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.frequency.value = 1200;
        gain.gain.value = 0.1;
        osc.start();
        osc.stop(ctx.currentTime + 0.1);
    } catch (e) {}
}

function playBeepSuccess() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        [880, 1100, 1320].forEach((freq, i) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = freq;
            gain.gain.value = 0.08;
            osc.start(ctx.currentTime + i * 0.12);
            osc.stop(ctx.currentTime + i * 0.12 + 0.1);
        });
    } catch (e) {}
}

// ══════ KEYBOARD SHORTCUTS ══════

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeSuccess();
        cancelConfirm();
    }
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        toggleManualInput();
    }
});
</script>
@endpush
