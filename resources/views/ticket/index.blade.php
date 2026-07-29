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
            <span class="total-count">Tổng số: {{ $bookings->total() }}</span>
        </div>

        <table class="history-table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Mã booking</th>
                    <th>Tên phim</th>
                    <th>Lịch chiếu</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái đặt</th>
                    <th>Thanh toán</th>
                    <th>Trạng thái vé</th>
                    <th style="text-align: center;">Hành động</th>
                </tr>
            </thead>

            <tbody>
            @forelse($bookings as $key => $booking)
                @php
                    // Công thức tính STT liên tục tăng tiến qua các trang phân trang
                    $stt = ($bookings->currentPage() - 1) * $bookings->perPage() + $loop->iteration;
                @endphp
                <tr>
                    <td>{{ $stt }}</td>
                    <td>
                        <strong class="booking-code">{{ $booking->booking_code }}</strong>
                    </td>

                    {{-- tên phim --}}
                    <td>
                        <strong style="color: #ffffff;">
                            {{ $booking->showtime->movie->title ?? 'Không tìm thấy tên phim' }}
                        </strong>
                    </td>

                    {{-- lịch chiếu gộp ngày & suất chiếu --}}
                    <td>
                        @if($booking->showtime)
                            <div style="color: #ffffff; font-weight: 500;">
                                {{ $booking->showtime->start_time->format('d/m/Y') }}
                            </div>
                            <div style="font-size: 13px; color: #94a3b8; margin-top: 2px;">
                                <strong>{{ $booking->showtime->start_time->format('H:i') }}</strong> - <strong>{{ $booking->showtime->end_time->format('H:i') }}</strong>
                            </div>
                        @else
                            <span class="text-soft">N/A</span>
                        @endif
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

                    {{-- trạng thái vé check-in --}}
                    <td>
                        @php
                            // Tổng số vé và số vé đã sử dụng trong đơn
                            $totalTickets = $booking->tickets->count();
                            $usedTickets = $booking->tickets->where('status', 'USED')->count();
                        @endphp
                        @if($booking->status == 'PAID')
                            @if($totalTickets > 0 && $usedTickets == $totalTickets)
                                <span class="status-badge status-pending">Đã dùng ({{ $usedTickets }}/{{ $totalTickets }})</span>
                            @elseif($usedTickets > 0)
                                <span class="status-badge status-pending">Đã dùng {{ $usedTickets }}/{{ $totalTickets }} vé</span>
                            @else
                                <span class="status-badge status-paid">Chưa sử dụng (0/{{ $totalTickets }})</span>
                            @endif
                        @elseif($booking->status == 'CANCELLED')
                            <span class="status-badge status-cancelled">Đã hủy</span>
                        @else
                            <span class="status-badge status-expired">Chưa xuất vé</span>
                        @endif
                    </td>

                    {{-- Hành động chuyển hướng sang trang chi tiết --}}
                    <td style="text-align: center;">
                        <a href="{{ route('my-tickets.show', $booking->id) }}" class="btn-detail-custom" style="text-decoration: none;">
                            <i class="bi bi-eye"></i> Chi tiết
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="empty-row">
                        <i class="bi bi-inbox" style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
                        Chưa có giao dịch nào được thực hiện
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- phân trang --}}
<div class="pagination-container">
    {{ $bookings->links() }}
</div>

<style>
/* --- ĐỊNH DẠNG LAYOUT TỔNG THỂ (DARK MODE) --- */
body {
    background-color: #0b0f19 !important;
}

.ticket-history-container {
    max-width: 1500px;
    margin: 50px auto;
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

.price {
    color: #38bdf8;
    font-weight: 700;
}

/* --- NÚT CHI TIẾT CUSTOM --- */
.btn-detail-custom {
    background: rgba(13, 110, 253, 0.15);
    color: #0d6efd;
    border: 1px solid rgba(13, 110, 253, 0.4);
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-detail-custom:hover {
    background: #0d6efd;
    color: #ffffff;
    border-color: #0d6efd;
    box-shadow: 0 0 10px rgba(13, 110, 253, 0.5);
}

.empty-row {
    text-align: center;
    padding: 40px !important;
    color: #64748b !important;
    font-size: 14px;
}

/* Style cho Badges */
.status-badge, .payment-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 20px;
    min-width: 140px;
    text-align: center;
}

.status-paid { background-color: #0b6655; color: #2ecc71; }
.status-pending { background-color: #7d5004; color: #f1c40f; }
.status-cancelled { background-color: #721c24; color: #f8d7da; }
.status-refunded-status { background-color: #1b4f72; color: #aed6f1; }
.status-expired { background-color: #2c3e50; color: #bdc3c7; }
.status-default { background-color: #2d3748; color: #a0aec0; }

.payment-paid { border: 1px solid #2ecc71; color: #2ecc71; }
.payment-unpaid { border: 1px solid #f1c40f; color: #f1c40f; }
.payment-refunded { border: 1px solid #3498db; color: #3498db; }
.payment-failed { border: 1px solid #e74c3c; color: #e74c3c; }
.payment-default { border: 1px solid #718096; color: #a0aec0; }

@media(max-width: 768px) {
    .ticket-history-container { margin-top: 40px; }
    .profile-actions-wrapper { justify-content: center; }
    .profile-actions { width: 100%; }
    .action-btn { flex: 1; justify-content: center; }
    .history-table { display: block; overflow-x: auto; }
}

/* --- ĐỊNH DẠNG PHÂN TRANG (PAGINATION) --- */
.pagination-container {
    margin-top: 30px;
    margin-bottom: 30px;
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
}

.pagination-container nav {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
    width: 100%;
}

/* Sửa lỗi text Showing bị tối màu đen */
.pagination-container nav div:first-child,
.pagination-container nav div:first-child *, 
.pagination-container .text-muted,
.pagination-container .text-muted * {
    color: #ffffff !important; /* Giữ lại !important ở đây để thắng text-muted của bootstrap */
    font-size: 14px !important;
    font-weight: 500;
}

.pagination-container nav div:first-child strong,
.pagination-container nav div:first-child span {
    color: #3b82f6 !important; 
    font-weight: 700;
}

.pagination-container .pagination {
    display: flex;
    padding-left: 0;
    list-style: none;
    gap: 8px;
}

/* Định hình chung nút trang */
.pagination-container .page-link {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #111827; 
    border: 1px solid rgba(255, 255, 255, 0.15); 
    color: #ffffff; 
    min-width: 40px;
    height: 40px;
    padding: 0 12px;
    font-size: 14px;
    font-weight: 600;
    border-radius: 8px;
    transition: all 0.2s ease-in-out;
    text-decoration: none;
}

.pagination-container .page-link:hover {
    background-color: #1f2937;
    color: #3b82f6; 
    border-color: #3b82f6;
}

/* Trang hiện tại (Active) */
.pagination-container .page-item.active .page-link {
    background-color: #3b82f6; 
    border-color: #3b82f6;
    color: #ffffff; 
    box-shadow: 0 0 12px rgba(59, 130, 246, 0.45); 
}

/* Ô bị vô hiệu hóa (Disabled) */
.pagination-container .page-item.disabled .page-link {
    background-color: rgba(17, 24, 39, 0.8); 
    border: 1px solid rgba(255, 255, 255, 0.1); 
    color: #4b5563;
    cursor: not-allowed;
    opacity: 0.6; 
}

.pagination-container .page-link:focus {
    box-shadow: none;
}
</style>
@endsection