@extends('layout.staff')

@section('title', 'Staff Dashboard')
@section('page-title', 'Staff Dashboard')

@section('topbar-actions')
<a href="{{ route('staff.dashboard') }}" class="btn btn-sm btn-outline-light">
    <i class="bi bi-arrow-clockwise me-1"></i> Tải lại
</a>
@endsection

@section('styles')
.staff-dashboard-hero {
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(139, 92, 246, .24);
    border-radius: 24px;
    padding: 30px;
    background:
        radial-gradient(circle at 88% 8%, rgba(139, 92, 246, .34), transparent 32%),
        radial-gradient(circle at 18% 100%, rgba(59, 130, 246, .20), transparent 30%),
        linear-gradient(135deg, rgba(30, 41, 59, .98), rgba(15, 23, 42, .96));
    box-shadow: 0 24px 70px rgba(2, 6, 23, .34);
}

.staff-dashboard-hero::after {
    content: "";
    position: absolute;
    inset: auto -60px -120px auto;
    width: 280px;
    height: 280px;
    border-radius: 999px;
    background: rgba(59, 130, 246, .18);
    filter: blur(8px);
}

.staff-dashboard-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 11px;
    border-radius: 999px;
    color: #c4b5fd;
    background: rgba(139, 92, 246, .14);
    border: 1px solid rgba(196, 181, 253, .18);
    font-size: 12px;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.staff-dashboard-hero h1 {
    position: relative;
    z-index: 1;
    margin: 18px 0 10px;
    font-size: clamp(30px, 4vw, 46px);
    font-weight: 900;
    letter-spacing: -.05em;
}

.staff-dashboard-hero p {
    position: relative;
    z-index: 1;
    max-width: 760px;
    margin: 0;
    color: var(--staff-text-muted);
    font-size: 15px;
    line-height: 1.75;
}

.staff-dashboard-date {
    position: relative;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 18px;
    color: #dbeafe;
    font-weight: 700;
    font-size: 13px;
}

.staff-metric-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-top: 20px;
}

.staff-metric-card {
    position: relative;
    overflow: hidden;
    padding: 18px;
    border-radius: 20px;
    background: rgba(30, 41, 59, .78);
    border: 1px solid rgba(148, 163, 184, .15);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, .035), 0 16px 36px rgba(2, 6, 23, .18);
}

.staff-metric-card::after {
    content: "";
    position: absolute;
    inset: auto -28px -42px auto;
    width: 108px;
    height: 108px;
    border-radius: 999px;
    background: var(--metric-glow, rgba(139, 92, 246, .18));
}

.staff-metric-card .metric-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.staff-metric-card .metric-icon {
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    color: #fff;
    background: var(--metric-bg, linear-gradient(135deg, #8b5cf6, #2563eb));
}

.staff-metric-card span {
    display: block;
    color: var(--staff-text-muted);
    font-size: 12px;
    font-weight: 800;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.staff-metric-card strong {
    display: block;
    margin-top: 16px;
    color: var(--staff-text);
    font-size: 34px;
    line-height: 1;
}

.staff-quick-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    margin-top: 22px;
}

.staff-quick-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 18px;
    border-radius: 18px;
    color: var(--staff-text);
    text-decoration: none;
    background: rgba(30, 41, 59, .72);
    border: 1px solid rgba(148, 163, 184, .14);
    transition: transform .18s ease, border-color .18s ease, background .18s ease;
}

.staff-quick-card:hover {
    transform: translateY(-3px);
    color: #fff;
    border-color: rgba(139, 92, 246, .5);
    background: rgba(51, 65, 85, .9);
}

.staff-quick-card .quick-icon {
    width: 46px;
    height: 46px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    color: #fff;
    background: linear-gradient(135deg, var(--staff-primary), #2563eb);
    box-shadow: 0 14px 28px rgba(37, 99, 235, .22);
}

.staff-quick-card strong {
    display: block;
    font-size: 15px;
}

.staff-quick-card span {
    color: var(--staff-text-muted);
    font-size: 12px;
}

.staff-dashboard-panels {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
    margin-top: 22px;
}

.staff-panel {
    overflow: hidden;
    border-radius: 22px;
    background: rgba(30, 41, 59, .76);
    border: 1px solid rgba(148, 163, 184, .14);
    box-shadow: 0 18px 44px rgba(2, 6, 23, .20);
}

.staff-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 18px 20px;
    border-bottom: 1px solid rgba(148, 163, 184, .12);
}

.staff-panel-header h2 {
    margin: 0;
    font-size: 16px;
    font-weight: 800;
}

.staff-panel-link {
    color: #c4b5fd;
    text-decoration: none;
    font-size: 12px;
    font-weight: 800;
}

.staff-panel-list {
    display: grid;
}

.staff-list-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 15px 20px;
    color: var(--staff-text);
    text-decoration: none;
    border-bottom: 1px solid rgba(148, 163, 184, .10);
}

.staff-list-item:last-child {
    border-bottom: 0;
}

.staff-list-item:hover {
    color: #fff;
    background: rgba(51, 65, 85, .55);
}

.staff-list-main strong {
    display: block;
    font-size: 14px;
}

.staff-list-main span,
.staff-list-meta {
    color: var(--staff-text-muted);
    font-size: 12px;
}

.staff-list-meta {
    white-space: nowrap;
    text-align: right;
}

.staff-empty-state {
    display: grid;
    place-items: center;
    gap: 10px;
    min-height: 176px;
    padding: 28px;
    text-align: center;
    color: var(--staff-text-muted);
}

.staff-empty-state i {
    font-size: 30px;
    color: #64748b;
}

.staff-note-panel {
    margin-top: 20px;
    border: 1px dashed rgba(148, 163, 184, .28);
    border-radius: 18px;
    padding: 18px 20px;
    color: var(--staff-text-muted);
    background: rgba(15, 23, 42, .42);
}

@media (max-width: 1100px) {
    .staff-metric-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .staff-dashboard-panels { grid-template-columns: 1fr; }
}

@media (max-width: 960px) {
    .staff-quick-grid,
    .staff-metric-grid { grid-template-columns: 1fr; }
}
@endsection

@section('content')
@php
    $metrics = $dashboard['metrics'] ?? [];
    $recentCheckins = $dashboard['recent_checkins'] ?? collect();
    $recentCashPayments = $dashboard['recent_cash_payments'] ?? collect();
    $dashboardDate = $dashboard['date'] ?? now();
@endphp

<section class="staff-dashboard-hero">
    <span class="staff-dashboard-eyebrow">
        <i class="bi bi-speedometer2"></i>
        UC-STAFF-05
    </span>
    <h1>Staff Dashboard</h1>
    <p>
        Theo dõi nhanh tình hình trong ca làm việc: check-in, booking cần xử lý,
        thanh toán tiền mặt và các tác vụ hỗ trợ khách hàng tại rạp.
    </p>
    <div class="staff-dashboard-date">
        <i class="bi bi-calendar2-check"></i>
        Dữ liệu ngày {{ $dashboardDate->format('d/m/Y') }}
    </div>
</section>

<div class="staff-metric-grid">
    <div class="staff-metric-card" style="--metric-bg:linear-gradient(135deg,#10b981,#059669);--metric-glow:rgba(16,185,129,.18);">
        <div class="metric-head">
            <span>Vé check-in hôm nay</span>
            <div class="metric-icon"><i class="bi bi-qr-code-scan"></i></div>
        </div>
        <strong>{{ number_format($metrics['checked_in_tickets'] ?? 0) }}</strong>
    </div>

    <div class="staff-metric-card" style="--metric-bg:linear-gradient(135deg,#f59e0b,#d97706);--metric-glow:rgba(245,158,11,.18);">
        <div class="metric-head">
            <span>Booking chờ tiền mặt</span>
            <div class="metric-icon"><i class="bi bi-cash-coin"></i></div>
        </div>
        <strong>{{ number_format($metrics['pending_cash_bookings'] ?? 0) }}</strong>
    </div>

    <div class="staff-metric-card" style="--metric-bg:linear-gradient(135deg,#3b82f6,#2563eb);--metric-glow:rgba(59,130,246,.18);">
        <div class="metric-head">
            <span>Booking mới hôm nay</span>
            <div class="metric-icon"><i class="bi bi-ticket-perforated"></i></div>
        </div>
        <strong>{{ number_format($metrics['new_bookings'] ?? 0) }}</strong>
    </div>

    <div class="staff-metric-card" style="--metric-bg:linear-gradient(135deg,#ef4444,#be123c);--metric-glow:rgba(239,68,68,.18);">
        <div class="metric-head">
            <span>Sự cố cần hỗ trợ</span>
            <div class="metric-icon"><i class="bi bi-life-preserver"></i></div>
        </div>
        <strong>{{ number_format($metrics['support_issues'] ?? 0) }}</strong>
    </div>
</div>

<div class="staff-quick-grid">
    <a class="staff-quick-card" href="{{ route('staff.check-in') }}">
        <div class="quick-icon"><i class="bi bi-qr-code-scan"></i></div>
        <div>
            <strong>Check-in vé</strong>
            <span>Quét QR hoặc nhập mã vé thủ công</span>
        </div>
    </a>

    <a class="staff-quick-card" href="{{ route('staff.booking-lookup') }}">
        <div class="quick-icon"><i class="bi bi-search"></i></div>
        <div>
            <strong>Tra cứu Booking/Vé</strong>
            <span>Tìm booking, vé và thông tin khách hàng</span>
        </div>
    </a>

    <a class="staff-quick-card" href="{{ route('staff.issue-support') }}">
        <div class="quick-icon"><i class="bi bi-life-preserver"></i></div>
        <div>
            <strong>Hỗ trợ sự cố</strong>
            <span>Chẩn đoán và xử lý vấn đề đặt vé</span>
        </div>
    </a>
</div>

<div class="staff-dashboard-panels">
    <section class="staff-panel">
        <div class="staff-panel-header">
            <h2><i class="bi bi-clock-history me-2"></i>Check-in gần đây</h2>
            <a class="staff-panel-link" href="{{ route('staff.check-in') }}">Mở check-in</a>
        </div>

        <div class="staff-panel-list">
            @forelse ($recentCheckins as $item)
                @php
                    $ticket = $item instanceof \App\Models\CheckInLog ? $item->ticket : $item;
                    $booking = $item instanceof \App\Models\CheckInLog ? ($item->booking ?? $item->ticket?->booking) : $item->booking;
                    $time = $item instanceof \App\Models\CheckInLog ? $item->created_at : $item->checked_in_at;
                    $staffName = $item instanceof \App\Models\CheckInLog ? $item->staff?->name : $item->checkedInByUser?->name;
                @endphp
                <a class="staff-list-item" href="{{ route('staff.booking-lookup') }}">
                    <div class="staff-list-main">
                        <strong>{{ $ticket?->ticket_code ?? 'Vé không xác định' }}</strong>
                        <span>{{ $booking?->booking_code ?? 'Booking không xác định' }} · {{ $staffName ?? 'Staff' }}</span>
                    </div>
                    <div class="staff-list-meta">
                        {{ optional($time)->format('H:i') ?? '--:--' }}
                    </div>
                </a>
            @empty
                <div class="staff-empty-state">
                    <i class="bi bi-inbox"></i>
                    <div>Chưa có check-in nào trong ngày.</div>
                </div>
            @endforelse
        </div>
    </section>

    <section class="staff-panel">
        <div class="staff-panel-header">
            <h2><i class="bi bi-cash-stack me-2"></i>Thanh toán tiền mặt gần đây</h2>
            <a class="staff-panel-link" href="{{ route('staff.booking-lookup') }}">Tra cứu booking</a>
        </div>

        <div class="staff-panel-list">
            @forelse ($recentCashPayments as $payment)
                <a class="staff-list-item" href="{{ route('staff.booking-lookup') }}">
                    <div class="staff-list-main">
                        <strong>{{ $payment->booking?->booking_code ?? 'Booking không xác định' }}</strong>
                        <span>{{ $payment->booking?->user?->name ?? 'Khách hàng' }} · {{ number_format($payment->amount) }}đ</span>
                    </div>
                    <div class="staff-list-meta">
                        {{ optional($payment->paid_at)->format('H:i') ?? '--:--' }}
                    </div>
                </a>
            @empty
                <div class="staff-empty-state">
                    <i class="bi bi-wallet2"></i>
                    <div>Chưa có thanh toán tiền mặt thành công trong ngày.</div>
                </div>
            @endforelse
        </div>
    </section>
</div>

<div class="staff-note-panel">
    <i class="bi bi-info-circle me-1"></i>
    Dashboard chỉ dùng để theo dõi nhanh. Khi xác nhận thanh toán hoặc check-in,
    nhân viên vẫn cần kiểm tra chi tiết booking/vé ở chức năng tương ứng.
</div>
@endsection
