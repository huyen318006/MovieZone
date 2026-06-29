@extends('layout.staff')

@section('title', 'Staff Dashboard')
@section('page-title', 'Staff Dashboard')

@section('styles')
.staff-dashboard-hero {
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(139, 92, 246, .22);
    border-radius: 22px;
    padding: 28px;
    background:
        radial-gradient(circle at top right, rgba(139, 92, 246, .28), transparent 34%),
        linear-gradient(135deg, rgba(30, 41, 59, .96), rgba(15, 23, 42, .96));
    box-shadow: 0 24px 70px rgba(2, 6, 23, .32);
}

.staff-dashboard-hero::after {
    content: "";
    position: absolute;
    inset: auto -60px -120px auto;
    width: 260px;
    height: 260px;
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
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.staff-dashboard-hero h1 {
    margin: 18px 0 10px;
    font-size: clamp(28px, 4vw, 44px);
    font-weight: 800;
    letter-spacing: -.04em;
}

.staff-dashboard-hero p {
    max-width: 720px;
    margin: 0;
    color: var(--staff-text-muted);
    font-size: 15px;
    line-height: 1.7;
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

.staff-placeholder-panel {
    margin-top: 20px;
    border: 1px dashed rgba(148, 163, 184, .28);
    border-radius: 18px;
    padding: 20px;
    color: var(--staff-text-muted);
    background: rgba(15, 23, 42, .42);
}

.staff-metric-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-top: 20px;
}

.staff-metric-card {
    padding: 18px;
    border-radius: 18px;
    background: rgba(30, 41, 59, .72);
    border: 1px solid rgba(148, 163, 184, .14);
}

.staff-metric-card span {
    display: block;
    color: var(--staff-text-muted);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.staff-metric-card strong {
    display: block;
    margin-top: 8px;
    color: var(--staff-text);
    font-size: 30px;
    line-height: 1;
}

@media (max-width: 960px) {
    .staff-quick-grid,
    .staff-metric-grid { grid-template-columns: 1fr; }
}
@endsection

@section('content')
@php
    $metrics = $dashboard['metrics'] ?? [];
@endphp
<section class="staff-dashboard-hero">
    <span class="staff-dashboard-eyebrow">
        <i class="bi bi-speedometer2"></i>
        UC-STAFF-05
    </span>
    <h1>Staff Dashboard</h1>
    <p>
        Trang tổng quan cho nhân viên rạp theo dõi nhanh check-in, booking cần xử lý,
        thanh toán tiền mặt và các tác vụ hỗ trợ trong ca làm việc.
    </p>
</section>

<div class="staff-metric-grid">
    <div class="staff-metric-card">
        <span>Vé check-in hôm nay</span>
        <strong>{{ number_format($metrics['checked_in_tickets'] ?? 0) }}</strong>
    </div>
    <div class="staff-metric-card">
        <span>Booking chờ tiền mặt</span>
        <strong>{{ number_format($metrics['pending_cash_bookings'] ?? 0) }}</strong>
    </div>
    <div class="staff-metric-card">
        <span>Booking mới hôm nay</span>
        <strong>{{ number_format($metrics['new_bookings'] ?? 0) }}</strong>
    </div>
    <div class="staff-metric-card">
        <span>Sự cố cần hỗ trợ</span>
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

<div class="staff-placeholder-panel">
    <i class="bi bi-info-circle me-1"></i>
    Dữ liệu tổng quan đã được tải cho ngày {{ ($dashboard['date'] ?? now())->format('d/m/Y') }}.
    Giao diện bảng chi tiết check-in và thanh toán gần đây sẽ được hoàn thiện ở commit tiếp theo.
</div>
@endsection
