@extends('layout.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<div class="ticket-history-container">

    <div class="profile-actions-wrapper">
        <div class="profile-actions">
            <a href="{{ route('home') }}" class="action-btn">
                <i class="bi bi-house-door-fill"></i>
                <span>Trang chủ</span>
            </a>
            <a href="{{ route('profile') }}" class="action-btn">
                <i class="bi bi-person-badge-fill"></i>
                <span>Hồ sơ cá nhân</span>
            </a>
        </div>
    </div>

    <div class="history-card">
        <div class="card-header-title">
            <span>Danh sách giao dịch</span>
            <span class="total-count">Tổng số: {{ $bookings->count() }}</span>
        </div>

        <table class="history-table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Mã booking</th>
                    <th>Tên phim</th>
                    <th>Ngôn ngữ</th>
                    <th>Ngày chiếu</th>
                    <th>Suất chiếu</th>
                    <th>Phòng</th>
                    <th>Số vé</th>
                    <th>Số ghế</th>
                    <th>Ngày đặt</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái đặt</th>
                    <th>Thanh toán</th>
                </tr>
            </thead>

            <tbody>
            @forelse($bookings as $key=>$booking)
                <tr>
                    <td>{{ $key+1 }}</td>
                    <td>
                        <strong class="booking-code">{{ $booking->booking_code }}</strong>
                    </td>

                    {{-- tên phim --}}
                    <td>
                        <strong style="color: #ffffff;">
                            {{ $booking->showtime->movie->title ?? 'Không tìm thấy tên phim' }}
                        </strong>
                    </td>

                    {{-- ngôn ngữ --}}
                    <td>
                        @if($booking->showtime && $booking->showtime->movie)
                            <span class="badge-status badge-count">
                                {{ $booking->showtime->format }} {{ $booking->showtime->movie->language }}
                            </span>
                        @else
                            <span class="text-soft">N/A</span>
                        @endif
                    </td>

                    {{-- ngày chiếu --}}
                    <td class="text-soft">
                        {{ $booking->showtime ? $booking->showtime->start_time->format('d/m/Y') : 'N/A' }}
                    </td>

                    {{-- suất chiếu --}}
                    <td class="text-highlight">
                        @if($booking->showtime)
                            <strong>{{ $booking->showtime->start_time->format('H:i') }}</strong> 
                            <span style="color: #64748b;">-</span> 
                            <strong>{{ $booking->showtime->end_time->format('H:i') }}</strong>
                        @else
                            <span class="text-soft">N/A</span>
                        @endif
                    </td>

                    {{-- phòng chiếu --}}
                    <td>
                        <span class="badge-status badge-count">
                            {{ $booking->showtime->room->name ?? 'N/A' }}
                        </span>
                    </td>

                    {{-- slg vé --}}
                    <td>
                        <span class="badge-status badge-count">
                            {{ $booking->bookingSeats ? $booking->bookingSeats->count() : 0 }} vé
                        </span>
                    </td>

                    {{--Số ghế --}}
                    <td style="color: #ffffff; font-weight: 600;">
                        @if($booking->bookingSeats && $booking->bookingSeats->isNotEmpty())
                            {{ $booking->bookingSeats->pluck('seat_code')->implode(', ') }}
                        @else
                            <span style="color: #64748b;">N/A</span>
                        @endif
                    </td>
                    
                    {{--  ngày đặt --}}
                    <td class="text-soft">
                        {{ $booking->created_at->format('d/m/Y H:i') }}
                    </td>

                    {{--  tổng tiền --}}
                    <td class="price">
                        {{ number_format($booking->final_amount) }} VNĐ
                    </td>

                    {{--trạng thái đặt --}}
                    <td>
                        @if($booking->status == 'PAID')
                            <span class="status-badge status-paid">Vé hợp lệ</span>
                        @elseif($booking->status == 'PENDING')
                            <span class="status-badge status-pending">Chờ thanh toán</span>
                        @elseif($booking->status == 'EXPIRED')
                            <span class="status-badge status-expired">Hết hạn giữ chỗ</span>
                        @elseif($booking->status == 'CANCELLED')
                            @if($booking->payment_status == 'PAID')
                                <span class="status-badge status-cancelled">Đã hủy (Chờ hoàn)</span>
                            @elseif($booking->payment_status == 'REFUNDED')
                                <span class="status-badge status-refunded-status">Đã hủy (Đã hoàn)</span>
                            @else
                                <span class="status-badge status-cancelled">Đã hủy</span>
                            @endif
                        @else
                            <span class="status-badge status-default">{{ $booking->status }}</span>
                        @endif
                    </td>

                    {{--trạng thái thanh toán--}}
                    <td>
                        @switch($booking->payment_status)
                            @case('PAID')
                                <span class="payment-badge payment-paid">Đã thanh toán</span>
                                @break
                            @case('UNPAID')
                                <span class="payment-badge payment-unpaid">Chưa thanh toán</span>
                                @break
                            @case('REFUNDED')
                                <span class="payment-badge payment-refunded">Đã hoàn tiền</span>
                                @break
                            @case('FAILED')
                                <span class="payment-badge payment-failed">Thanh toán lỗi</span>
                                @break
                            @default
                                <span class="payment-badge payment-default">{{ $booking->payment_status }}</span>
                        @endswitch
                    </td>
            @empty
                <tr>
                    <td colspan="13" class="empty-row">
                        <i class="bi bi-inbox" style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
                        Chưa có giao dịch nào được thực hiện
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- MODAL CHI TIẾT VÉ --}}
<div class="modal-overlay" id="detailModalOverlay" onclick="closeDetailModal(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header-custom">
            <h3><i class="bi bi-ticket-detailed"></i> Chi tiết giao dịch</h3>
            <button class="modal-close" onclick="closeDetailModal()"><i class="bi bi-x-lg"></i></button>
        </div>

        <div class="modal-body-custom" id="detailModalBody">
            <div class="loading-spinner">
                <i class="bi bi-arrow-repeat spin"></i> Đang tải...
            </div>
        </div>
    </div>
</div>

<style>
/* --- ĐỊNH DẠNG LAYOUT TỔNG THỂ (DARK MODE) --- */
body {
    background-color: #0b0f19 !important;
}

.ticket-history-container {
    max-width: 1500px;
    margin: 50px auto 50px auto;
    padding: 0 20px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}

/* --- KHỐI ĐIỀU HƯỚNG/NÚT HÀNH ĐỘNG --- */
.profile-actions-wrapper {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 15px;
}

.profile-actions {
    display: flex;
    gap: 10px;
}

.action-btn {
    text-decoration: none !important;
    color: #ffffff !important;
    background: #3b82f6;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: none;
    transition: background 0.2s ease;
}

.action-btn:hover {
    background: #2563eb;
}

/* --- TIÊU ĐỀ TRANG --- */
.history-header {
    margin-bottom: 25px;
}

.history-header h2 {
    color: #ffffff;
    font-size: 26px;
    font-weight: 700;
    margin: 0 0 6px 0;
}

.history-header p {
    color: #94a3b8;
    font-size: 14px;
    margin: 0;
}

/* --- THIẾT KẾ KHỐI CARD & BẢNG --- */
.history-card {
    background: #111827;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.05);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.card-header-title {
    padding: 16px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    color: #ffffff;
    font-weight: 600;
    font-size: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header-title .total-count {
    font-size: 13px;
    color: #94a3b8;
    font-weight: 400;
}

.history-table {
    width: 100%;
    border-collapse: collapse;
}

.history-table thead th {
    color: #94a3b8;
    text-align: left;
    padding: 14px 20px;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .5px;
    font-weight: 600;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.history-table tbody tr {
    border-bottom: 1px solid rgba(255, 255, 255, 0.03);
    transition: background 0.2s ease;
}

.history-table tbody tr:hover {
    background: rgba(255, 255, 255, 0.02);
}

.history-table tbody td {
    color: #94a3b8;
    padding: 16px 20px;
    font-size: 14px;
    vertical-align: middle;
}

.booking-code {
    color: #ffffff;
    font-weight: 600;
}

.text-soft {
    color: #94a3b8;
}

.font-bold {
    font-weight: 600;
}

.price {
    color: #38bdf8;
    font-weight: 700;
}

/* Badge trạng thái */
.status {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.status-paid {
    background: rgba(16, 185, 129, 0.15);
    color: #10b981;
}

.status-pending {
    background: rgba(251, 191, 36, 0.15);
    color: #fbbf24;
}

.status-cancelled {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}

.status-expired {
    background: rgba(107, 114, 128, 0.15);
    color: #6b7280;
}

.status-refunded {
    background: rgba(139, 92, 246, 0.15);
    color: #8b5cf6;
}

.status-default {
    background: rgba(255, 255, 255, 0.08);
    color: #ffffff;
}

/* Nút Chi tiết */
.btn-detail {
    background: rgba(59, 130, 246, 0.15);
    color: #60a5fa;
    border: 1px solid rgba(59, 130, 246, 0.3);
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.btn-detail:hover {
    background: rgba(59, 130, 246, 0.3);
    border-color: #3b82f6;
    color: #ffffff;
}

/* Hàng trống */
.empty-row {
    text-align: center;
    padding: 40px !important;
    color: #64748b !important;
    font-size: 14px;
}

/* --- MODAL OVERLAY --- */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
    z-index: 9999;
    justify-content: center;
    align-items: flex-start;
    padding-top: 80px;
    overflow-y: auto;
}

.modal-overlay.active {
    display: flex;
}

.modal-content {
    background: #111827;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    width: 100%;
    max-width: 680px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    animation: slideIn 0.3s ease;
    margin-bottom: 40px;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

.modal-header-custom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.modal-header-custom h3 {
    color: #ffffff;
    font-size: 18px;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.modal-close {
    background: rgba(255, 255, 255, 0.05);
    border: none;
    color: #94a3b8;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.modal-close:hover {
    background: rgba(239, 68, 68, 0.2);
    color: #f87171;
}

.modal-body-custom {
    padding: 24px;
    color: #e2e8f0;
}

/* Loading */
.loading-spinner {
    text-align: center;
    padding: 40px;
    color: #64748b;
    font-size: 15px;
}

.spin {
    display: inline-block;
    animation: spinAnim 1s linear infinite;
}

@keyframes spinAnim {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Detail sections */
.detail-section {
    margin-bottom: 20px;
}

.detail-section-title {
    font-size: 13px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.detail-item {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    padding: 12px 14px;
}

.detail-item .label {
    font-size: 11px;
    color: #64748b;
    margin-bottom: 4px;
}

.detail-item .value {
    font-size: 14px;
    color: #f1f5f9;
    font-weight: 500;
}

.detail-item .value.price-highlight {
    color: #38bdf8;
    font-weight: 700;
}

/* Seat list */
.seat-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.seat-badge {
    background: linear-gradient(135deg, #312e81, #4c1d95);
    color: #c4b5fd;
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
}

.seat-badge .seat-code {
    color: #ffffff;
    font-size: 15px;
}

.seat-badge .seat-price {
    font-size: 11px;
    color: #a78bfa;
}

/* Combo list */
.combo-item {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    padding: 10px 14px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.combo-item .combo-name {
    color: #e2e8f0;
    font-weight: 500;
}

.combo-item .combo-qty {
    color: #94a3b8;
    font-size: 13px;
}

/* Ticket list */
.ticket-item {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    padding: 10px 14px;
    margin-bottom: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ticket-item .ticket-code {
    font-family: monospace;
    color: #fbbf24;
    font-weight: 600;
    font-size: 13px;
}

.ticket-item .ticket-seat {
    color: #94a3b8;
    font-size: 13px;
}

.ticket-item .ticket-status {
    font-size: 11px;
}

/* Divider */
.detail-divider {
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.08), transparent);
    margin: 16px 0;
}

/* Responsive */
@media(max-width: 768px) {
    .ticket-history-container {
        margin-top: 40px;
    }
    .profile-actions-wrapper {
        justify-content: center;
    }
    .profile-actions {
        width: 100%;
    }
    .action-btn {
        flex: 1;
        justify-content: center;
    }
    .history-table {
        display: block;
        overflow-x: auto;
    }
    .detail-grid {
        grid-template-columns: 1fr;
    }
    .modal-content {
        margin: 0 10px 40px 10px;
    }
}
/* Style chung cho các Badge */
.status-badge, .payment-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 20px; /* Bo tròn viền mềm mại */
    min-width: 140px; /* Đảm bảo các nút có độ rộng bằng nhau */
    text-align: center;
    line-height: 1.2;
}

/* ==========================================================================
   CSS CHO CỘT TRẠNG THÁI (Nền đặc - Solid style)
   ========================================================================== */
.status-paid {
    background-color: #0b6655; /* Xanh lá đậm */
    color: #2ecc71;
}

.status-pending {
    background-color: #7d5004; /* Cam đất/nâu vàng */
    color: #f1c40f;
}

.status-cancelled {
    background-color: #721c24; /* Đỏ mận tối */
    color: #f8d7da;
}

.status-refunded-status {
    background-color: #1b4f72; /* Xanh dương tối */
    color: #aed6f1;
}

.status-expired {
    background-color: #2c3e50; /* Xám đen */
    color: #bdc3c7;
}

.status-default {
    background-color: #2d3748;
    color: #a0aec0;
}

/* ==========================================================================
   CSS CHO CỘT THANH TOÁN (Viền rỗng - Outline style)
   ========================================================================== */
.payment-paid {
    background-color: transparent;
    border: 1px solid #2ecc71; /* Viền xanh lá tươi */
    color: #2ecc71;
}

.payment-unpaid {
    background-color: transparent;
    border: 1px solid #f1c40f; /* Viền vàng */
    color: #f1c40f;
}

.payment-refunded {
    background-color: transparent;
    border: 1px solid #3498db; /* Viền xanh dương */
    color: #3498db;
}

.payment-failed {
    background-color: transparent;
    border: 1px solid #e74c3c; /* Viền đỏ tươi */
    color: #e74c3c;
}

.payment-default {
    background-color: transparent;
    border: 1px solid #718096;
    color: #a0aec0;
}
</style>

<script>
function showBookingDetail(id) {
    const overlay = document.getElementById('detailModalOverlay');
    const body = document.getElementById('detailModalBody');
    overlay.classList.add('active');
    body.innerHTML = '<div class="loading-spinner"><i class="bi bi-arrow-repeat spin"></i> Đang tải...</div>';

    fetch(`/my-tickets/${id}`)
        .then(res => res.json())
        .then(res => {
            if (!res.success) {
                body.innerHTML = '<p style="color: #f87171;">Không thể tải dữ liệu.</p>';
                return;
            }
            const b = res.booking;
            const showtime = b.showtime;
            const movie = showtime?.movie;
            const room = showtime?.room;
            const cinema = showtime?.cinema;
            const payment = b.payment;
            const seats = b.booking_seats || [];
            const combos = b.booking_combos || [];
            const tickets = b.tickets || [];

            let html = '';

            // Thông tin phim & suất chiếu
            html += `<div class="detail-section">
                <div class="detail-section-title"><i class="bi bi-film"></i> Thông tin suất chiếu</div>
                <div class="detail-grid">
                    <div class="detail-item"><div class="label">Phim</div><div class="value">${movie?.title || 'N/A'}</div></div>
                    <div class="detail-item"><div class="label">Rạp</div><div class="value">${cinema?.name || 'N/A'}</div></div>
                    <div class="detail-item"><div class="label">Phòng</div><div class="value">${room?.name || 'N/A'}</div></div>
                    <div class="detail-item"><div class="label">Suất chiếu</div><div class="value">${showtime?.start_time ? formatDT(showtime.start_time) : 'N/A'}</div></div>
                </div>
            </div>`;

            // Ghế đã đặt
            if (seats.length > 0) {
                html += `<div class="detail-divider"></div>
                <div class="detail-section">
                    <div class="detail-section-title"><i class="bi bi-grid-3x3-gap"></i> Ghế đã đặt (${seats.length})</div>
                    <div class="seat-list">`;
                seats.forEach(s => {
                    const typeLabel = s.seat_type === 'VIP' ? '👑 VIP' : (s.seat_type === 'COUPLE' ? '💕 Couple' : '🎬 Thường');
                    html += `<div class="seat-badge">
                        <span class="seat-code">${s.seat_code}</span>
                        <span class="seat-price">${typeLabel} — ${formatPrice(s.price)}</span>
                    </div>`;
                });
                html += `</div></div>`;
            }

            // Combo
            if (combos.length > 0) {
                html += `<div class="detail-divider"></div>
                <div class="detail-section">
                    <div class="detail-section-title"><i class="bi bi-basket3"></i> Combo</div>`;
                combos.forEach(c => {
                    html += `<div class="combo-item">
                        <span class="combo-name">${c.combo?.name || 'Combo'}</span>
                        <span class="combo-qty">x${c.quantity} — ${formatPrice(c.total_price)}</span>
                    </div>`;
                });
                html += `</div>`;
            }

            // Thanh toán
            html += `<div class="detail-divider"></div>
            <div class="detail-section">
                <div class="detail-section-title"><i class="bi bi-credit-card"></i> Thanh toán</div>
                <div class="detail-grid">
                    <div class="detail-item"><div class="label">Tiền vé</div><div class="value">${formatPrice(b.total_ticket_amount)}</div></div>
                    <div class="detail-item"><div class="label">Combo</div><div class="value">${formatPrice(b.total_combo_amount)}</div></div>
                    <div class="detail-item"><div class="label">Giảm giá</div><div class="value" style="color:#10b981;">-${formatPrice(b.discount_amount)}</div></div>
                    <div class="detail-item"><div class="label">Tổng cộng</div><div class="value price-highlight">${formatPrice(b.final_amount)}</div></div>
                </div>
            </div>`;

            // Danh sách mã vé
            if (tickets.length > 0) {
                html += `<div class="detail-divider"></div>
                <div class="detail-section">
                    <div class="detail-section-title"><i class="bi bi-qr-code"></i> Mã vé (${tickets.length})</div>`;
                tickets.forEach(t => {
                    const statusCls = t.status === 'USED' ? 'status-paid' : (t.status === 'CANCELLED' ? 'status-cancelled' : 'status-pending');
                    const seatCode = t.booking_seat?.seat_code || '';
                    html += `<div class="ticket-item">
                        <span class="ticket-code">${t.ticket_code}</span>
                        <span class="ticket-seat">${seatCode}</span>
                        <span class="ticket-status status ${statusCls}">${t.status}</span>
                    </div>`;
                });
                html += `</div>`;
            }

            body.innerHTML = html;
        })
        .catch(err => {
            body.innerHTML = '<p style="color: #f87171;">Lỗi kết nối. Vui lòng thử lại.</p>';
        });
}

function closeDetailModal(event) {
    if (event && event.target !== event.currentTarget) return;
    document.getElementById('detailModalOverlay').classList.remove('active');
}

function formatDT(dt) {
    if (!dt) return 'N/A';
    const d = new Date(dt);
    const pad = n => String(n).padStart(2, '0');
    return `${pad(d.getDate())}/${pad(d.getMonth()+1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function formatPrice(val) {
    if (!val && val !== 0) return '0 VNĐ';
    return Number(val).toLocaleString('vi-VN') + ' VNĐ';
}

// Close with ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeDetailModal();
});
</script>
@endsection