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

/* ── QR/Manual Wrappers ── */
#qrReaderWrap {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
}

#manualFormWrap {
    width: 100%;
    max-width: 400px;
    min-height: 300px;
    display: flex;
    align-items: center;
}

#manualFormWrap .manual-form {
    width: 100%;
}

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
                <h3 id="scannerTitle"><i class="bi bi-qr-code-scan"></i> Quét mã QR</h3>
                <div class="scanner-status" id="scannerStatus">
                    <span class="dot"></span> Đang chờ quét...
                </div>
            </div>
            <div class="scanner-body">
                {{-- QR Reader (shown by default) --}}
                <div id="qrReaderWrap">
                    <div id="qr-reader">
                        <div class="scanner-overlay">
                            <div class="scan-frame"></div>
                        </div>
                    </div>
                </div>

                {{-- Manual Form (hidden by default) --}}
                <div id="manualFormWrap" style="display:none; padding: 16px 0;">
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

                <div class="scanner-actions">
                    <button class="btn-scan btn-scan-primary" id="btnStartScan" onclick="toggleScanner()">
                        <i class="bi bi-camera-video"></i> Bắt đầu quét
                    </button>
                    <button class="btn-scan btn-scan-secondary" id="btnToggleManual" onclick="toggleManualInput()">
                        <i class="bi bi-keyboard"></i> Nhập mã
                    </button>
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

let isManualMode = false;

function toggleManualInput() {
    const qrWrap = document.getElementById('qrReaderWrap');
    const manualWrap = document.getElementById('manualFormWrap');
    const title = document.getElementById('scannerTitle');
    const status = document.getElementById('scannerStatus');
    const btnScan = document.getElementById('btnStartScan');
    const btnManual = document.getElementById('btnToggleManual');

    isManualMode = !isManualMode;

    if (isManualMode) {
        // Switch to manual mode
        if (isScanning) stopScanner();
        qrWrap.style.display = 'none';
        manualWrap.style.display = 'block';
        btnScan.style.display = 'none';
        btnManual.innerHTML = '<i class="bi bi-qr-code-scan"></i> Quét QR';
        btnManual.className = 'btn-scan btn-scan-primary';
        title.innerHTML = '<i class="bi bi-keyboard"></i> Nhập mã thủ công';
        status.innerHTML = '';
        document.getElementById('manualCode').focus();
    } else {
        // Switch to QR mode
        manualWrap.style.display = 'none';
        qrWrap.style.display = 'block';
        btnScan.style.display = '';
        btnManual.innerHTML = '<i class="bi bi-keyboard"></i> Nhập mã';
        btnManual.className = 'btn-scan btn-scan-secondary';
        title.innerHTML = '<i class="bi bi-qr-code-scan"></i> Quét mã QR';
        status.innerHTML = '<span class="dot"></span> Đang chờ quét...';
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
    const scannedTicketCode = data.scanned_ticket_code || null;

    // Lưu lại booking_code để có thể refresh sau khi check-in
    if (booking?.booking_code) {
        lastBatchBookingCode = booking.booking_code;
    }

    const checkableTickets = data.can_checkin ? tickets.filter(t => t.can_checkin) : [];
    const headerClass = checkableTickets.length > 0 ? 'valid' : (tickets.length > 0 ? 'warning' : 'invalid');
    let headerText;
    if (checkableTickets.length > 0) {
        headerText = `Booking tìm thấy — ${checkableTickets.length}/${tickets.length} vé có thể check-in`;
    } else if (data.error?.message) {
        headerText = data.error.message;
    } else {
        headerText = 'Không thể check-in';
    }

    let ticketsHtml = tickets.map(t => {
        const isScanned = scannedTicketCode && t.ticket_code === scannedTicketCode;
        const canCheck = data.can_checkin && t.can_checkin;
        return `
        <div class="ticket-row" style="opacity:${canCheck ? '1' : '0.7'}; ${isScanned ? 'border-color: var(--staff-primary); background: rgba(139,92,246,0.08); box-shadow: 0 0 0 1px var(--staff-primary);' : ''}">
            <input type="checkbox" class="batch-ticket-cb" value="${t.id}" ${canCheck ? 'checked' : 'disabled'}
                   style="accent-color:var(--staff-primary); width:18px; height:18px;">
            <span class="seat-badge">${t.seat_code}</span>
            <span style="flex:1; font-size:13px; min-width:0;">
                ${t.ticket_code}
                ${isScanned ? '<i class="bi bi-arrow-left-short" style="color:var(--staff-primary); font-weight:700;" title="Vé vừa quét"></i>' : ''}
            </span>
            <span class="status-badge ${t.status.toLowerCase()}">${t.status}</span>
            ${t.checked_in_at ? `<span style="font-size:11px; color:var(--staff-text-muted);">${formatDateTime(t.checked_in_at)}</span>` : ''}
            <button onclick="event.stopPropagation(); printTicket('${booking?.booking_code}', '${t.ticket_code}', ${t.id}, ${canCheck})"
                    style="background:transparent; border:1px solid var(--staff-border); color:var(--staff-text-muted); border-radius:6px; padding:4px 8px; cursor:pointer; font-size:12px; display:flex; align-items:center; gap:3px; flex-shrink:0; transition:all 0.2s;"
                    onmouseover="this.style.borderColor='var(--staff-primary)';this.style.color='var(--staff-primary)'"
                    onmouseout="this.style.borderColor='var(--staff-border)';this.style.color='var(--staff-text-muted)'"
                    title="In & check-in vé ${t.seat_code}">
                <i class="bi bi-printer"></i>
            </button>
        </div>
    `}).join('');

    const posterSrc = booking?.poster_url ? `/storage/${booking.poster_url}` : '';
    const posterImg = posterSrc
        ? `<img src="${posterSrc}" style="width:60px; height:90px; border-radius:8px; object-fit:cover; flex-shrink:0;" alt="poster">`
        : `<div style="width:60px; height:90px; border-radius:8px; background:var(--staff-bg); display:flex; align-items:center; justify-content:center; flex-shrink:0; color:var(--staff-text-muted); font-size:20px;"><i class="bi bi-film"></i></div>`;

    panel.innerHTML = `
        <div class="confirm-card-header ${headerClass}">
            <i class="bi bi-ticket-detailed"></i> ${headerText}
        </div>
        <div class="confirm-card-body">
            <div style="display:flex; gap:14px; margin-bottom:16px;">
                ${posterImg}
                <div style="flex:1; min-width:0;">
                    <div style="font-weight:700; font-size:15px; margin-bottom:6px;">${booking?.movie_title || 'N/A'}</div>
                    <div style="font-size:13px; color:var(--staff-text-muted); display:flex; flex-direction:column; gap:3px;">
                        <span><i class="bi bi-calendar3" style="margin-right:4px; color:var(--staff-primary);"></i>${formatDateTime(booking?.start_time)}</span>
                        <span><i class="bi bi-building" style="margin-right:4px; color:var(--staff-primary);"></i>${booking?.cinema_name || 'N/A'}</span>
                        <span><i class="bi bi-door-open" style="margin-right:4px; color:var(--staff-primary);"></i>${booking?.room_name || 'N/A'} ${booking?.room_type ? `(${booking.room_type})` : ''}</span>
                        <span><i class="bi bi-person" style="margin-right:4px; color:var(--staff-primary);"></i>${booking?.customer_name || 'N/A'}</span>
                    </div>
                </div>
                <div style="text-align:right; flex-shrink:0;">
                    <div style="font-size:11px; color:var(--staff-text-muted); text-transform:uppercase; letter-spacing:1px;">Booking</div>
                    <div style="font-weight:700; font-size:13px; font-family:monospace;">${booking?.booking_code || 'N/A'}</div>
                </div>
            </div>
            ${tickets.length > 0 ? `
                <div style="font-size:12px; color:var(--staff-text-muted); margin-bottom:6px; display:flex; justify-content:space-between; align-items:center;">
                    <span><i class="bi bi-ticket-perforated"></i> ${tickets.length} vé trong booking</span>
                    <button onclick="printBill('${booking?.booking_code}')" style="background:transparent; border:1px solid var(--staff-border); color:var(--staff-text-muted); border-radius:6px; padding:3px 10px; cursor:pointer; font-size:11px; display:flex; align-items:center; gap:4px;"
                            onmouseover="this.style.borderColor='var(--staff-primary)';this.style.color='var(--staff-primary)'"
                            onmouseout="this.style.borderColor='var(--staff-border)';this.style.color='var(--staff-text-muted)'">
                        <i class="bi bi-printer"></i> In tất cả
                    </button>
                </div>
            ` : ''}
            <div class="tickets-list">${ticketsHtml}</div>
        </div>
        ${checkableTickets.length > 0 ? `
            <div class="confirm-card-actions">
                <button class="btn-confirm btn-confirm-success" onclick="confirmBatch(${booking?.id})">
                    <i class="bi bi-check-all"></i> Check-in đã chọn
                </button>
                <button class="btn-confirm btn-confirm-cancel" onclick="cancelConfirm()">
                    <i class="bi bi-x-lg"></i> Đóng
                </button>
            </div>
        ` : `
            <div class="confirm-card-actions">
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
            // Refresh batch panel nếu đang hiển thị
            refreshBatchPanel();
        } else {
            showError(data.error?.message || 'Check-in thất bại.');
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
            // Refresh batch panel để cập nhật trạng thái vé
            refreshBatchPanel();
        } else {
            showError('Check-in hàng loạt thất bại.');
        }

    } catch (err) {
        showError('Lỗi kết nối.');
    }

    btn.disabled = false;
}

// ══════ REFRESH BATCH PANEL ══════

let lastBatchBookingCode = null;

async function refreshBatchPanel() {
    if (!lastBatchBookingCode) return;

    try {
        const res = await fetch(`${API_BASE}/manual`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
            body: JSON.stringify({ code: lastBatchBookingCode, type: 'booking_code' }),
        });

        const data = await res.json();

        if (data.tickets) {
            showBatchPanel(data);
        }
    } catch (e) {
        console.warn('Refresh batch panel failed:', e);
    }
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

        // Auto check-in tất cả vé sau khi tải PDF
        autoCheckInAllUnused();
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

    iframe.src = `/staff/print-bill/${code}?print=true`;

    // Auto check-in tất cả vé UNUSED trong batch panel
    autoCheckInAllUnused();
}

function printTicket(bookingCode, ticketCode, ticketId, canCheckin) {
    if (!bookingCode || !ticketCode) return;

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

    iframe.src = `/staff/print-bill/${bookingCode}?print=true&ticket=${ticketCode}`;

    // Luôn auto check-in khi in vé (staff in = xác nhận vé)
    if (ticketId) {
        autoCheckIn(ticketId, ticketCode);
    }
}

async function autoCheckIn(ticketId, ticketCode) {
    try {
        const res = await fetch(`${API_BASE}/confirm`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                ticket_id: ticketId,
                scan_method: 'MANUAL',
            }),
        });

        const data = await res.json();

        if (data.success) {
            playBeepSuccess();
            // Refresh lại toàn bộ batch panel từ server để đảm bảo đồng bộ
            refreshBatchPanel();
        }
    } catch (e) {
        // Print still works, just skip auto check-in silently
        console.warn('Auto check-in failed:', e);
    }
}

/**
 * Auto check-in tất cả vé UNUSED trong batch panel hiện tại.
 * Gọi khi staff bấm "In tất cả" hoặc "Tải PDF".
 */
async function autoCheckInAllUnused() {
    const checkboxes = document.querySelectorAll('.batch-ticket-cb:not(:disabled)');
    const ticketIds = Array.from(checkboxes).map(cb => parseInt(cb.value)).filter(id => !isNaN(id));

    if (ticketIds.length === 0) return;

    // Lấy booking_id từ batch panel nếu có
    const batchPanel = document.getElementById('batchPanel');
    const batchBtn = batchPanel?.querySelector('[onclick*="confirmBatch"]');
    const bookingIdMatch = batchBtn?.getAttribute('onclick')?.match(/confirmBatch\((\d+)\)/);
    const bookingId = bookingIdMatch ? parseInt(bookingIdMatch[1]) : null;

    if (!bookingId) {
        // Fallback: check-in từng vé
        for (const id of ticketIds) {
            await autoCheckIn(id, '');
        }
        return;
    }

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
            playBeepSuccess();
        }
    } catch (e) {
        console.warn('Auto batch check-in failed:', e);
    }

    refreshBatchPanel();
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
