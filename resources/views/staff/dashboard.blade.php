@extends('layout.staff')

@section('title', 'Staff Dashboard')
@section('page-title', 'Staff Dashboard')

@section('topbar-actions')
<form method="GET" action="{{ \App\Helpers\TabAuthHelper::route('staff.dashboard') }}" class="d-flex flex-wrap align-items-center gap-2">
    <input type="hidden" name="tab_token" value="{{ \App\Helpers\TabAuthHelper::gettoken() }}">
    <input type="date"
           name="start_date"
           value="{{ request('start_date', now()->toDateString()) }}"
           class="form-control form-control-sm"
           aria-label="Ngày bắt đầu"
           style="width: 145px;">
    <span class="text-white-50 small">đến</span>
    <input type="date"
           name="end_date"
           value="{{ request('end_date', now()->toDateString()) }}"
           class="form-control form-control-sm"
           aria-label="Ngày kết thúc"
           style="width: 145px;">
    <button type="submit" class="btn btn-sm btn-outline-light">
        <i class="bi bi-funnel me-1"></i> Lọc
    </button>
    <a href="{{ \App\Helpers\TabAuthHelper::route('staff.dashboard') }}" class="btn btn-sm btn-outline-light" title="Hôm nay">
        Hôm nay
    </a>
    <a href="{{ \App\Helpers\TabAuthHelper::route('staff.dashboard', ['start_date' => now()->subDays(6)->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]) }}" class="btn btn-sm btn-outline-light">
        7 ngày
    </a>
    <a href="{{ \App\Helpers\TabAuthHelper::route('staff.dashboard', ['start_date' => now()->startOfMonth()->format('Y-m-d'), 'end_date' => now()->endOfMonth()->format('Y-m-d')]) }}" class="btn btn-sm btn-outline-light">
        Tháng này
    </a>
</form>
@endsection

@push('styles')
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

@media (max-width: 1100px) {
    .staff-metric-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .staff-dashboard-panels { grid-template-columns: 1fr; }
}

@media (max-width: 960px) {
    .staff-quick-grid,
    .staff-metric-grid { grid-template-columns: 1fr; }
}
@endpush

@section('content')
@php
    $metrics = $dashboard['metrics'] ?? [];
    $recentCheckins = $dashboard['recent_checkins'] ?? collect();
    $recentCashPayments = $dashboard['recent_cash_payments'] ?? collect();
    $recentLatePayments = $dashboard['recent_late_payments'] ?? collect();
    $dashboardStartDate = $dashboard['start_date'] ?? now()->startOfDay();
    $dashboardEndDate = $dashboard['end_date'] ?? now()->endOfDay();
    $isSingleDay = $dashboardStartDate->isSameDay($dashboardEndDate);
@endphp

@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm mb-3">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        {{ $errors->first() }}
    </div>
@endif

@if (!empty($dashboardError))
    <div class="alert alert-warning border-0 shadow-sm mb-3">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        {{ $dashboardError }}
    </div>
@endif

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
        @if ($isSingleDay)
            Dữ liệu ngày {{ $dashboardStartDate->format('d/m/Y') }}
        @else
            Dữ liệu từ {{ $dashboardStartDate->format('d/m/Y') }} đến {{ $dashboardEndDate->format('d/m/Y') }}
        @endif
    </div>
</section>

<div class="staff-metric-grid">
    <div class="staff-metric-card" style="--metric-bg:linear-gradient(135deg,#10b981,#059669);--metric-glow:rgba(16,185,129,.18);">
        <div class="metric-head">
            <span>Vé đã check-in</span>
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
            <span>Booking mới</span>
            <div class="metric-icon"><i class="bi bi-ticket-perforated"></i></div>
        </div>
        <strong>{{ number_format($metrics['new_bookings'] ?? 0) }}</strong>
    </div>

    <div class="staff-metric-card" style="--metric-bg:linear-gradient(135deg,#ef4444,#be123c);--metric-glow:rgba(239,68,68,.18);">
        <div class="metric-head">
            <span>Thanh toán muộn (chưa hoàn)</span>
            <div class="metric-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
        </div>
        <strong>{{ number_format($metrics['late_payment_alerts'] ?? 0) }}</strong>
    </div>
</div>

<div class="staff-quick-grid">
    <a class="staff-quick-card" href="{{ \App\Helpers\TabAuthHelper::route('staff.check-in') }}">
        <div class="quick-icon"><i class="bi bi-qr-code-scan"></i></div>
        <div>
            <strong>Xác nhận & In vé</strong>
            <span>Quét QR hoặc nhập mã vé thủ công</span>
        </div>
    </a>

    <a class="staff-quick-card" href="{{ \App\Helpers\TabAuthHelper::route('staff.booking-lookup') }}">
        <div class="quick-icon"><i class="bi bi-search"></i></div>
        <div>
            <strong>Tra cứu Booking/Vé</strong>
            <span>Tìm booking, vé và thông tin khách hàng</span>
        </div>
    </a>

    <a class="staff-quick-card" href="{{ \App\Helpers\TabAuthHelper::route('staff.issue-support') }}">
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
            <a class="staff-panel-link" href="{{ \App\Helpers\TabAuthHelper::route('staff.check-in') }}">Mở check-in</a>
        </div>

        <div class="staff-panel-list">
            @forelse ($recentCheckins as $item)
                @php
                    $ticket = $item instanceof \App\Models\CheckInLog ? $item->ticket : $item;
                    $booking = $item instanceof \App\Models\CheckInLog ? ($item->booking ?? $item->ticket?->booking) : $item->booking;
                    $time = $item instanceof \App\Models\CheckInLog ? $item->created_at : $item->checked_in_at;
                    $staffName = $item instanceof \App\Models\CheckInLog ? $item->staff?->name : $item->checkedInByUser?->name;
                @endphp
                <a class="staff-list-item" href="{{ \App\Helpers\TabAuthHelper::route('staff.booking-lookup') }}">
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
            <h2><i class="bi bi-clock-history me-2"></i>Lịch Sử Booking Gần Đây</h2>
            <a class="staff-panel-link" href="{{ \App\Helpers\TabAuthHelper::route('staff.booking-lookup') }}">Tra cứu booking</a>
        </div>

        <div class="staff-panel-list">
            @forelse ($recentCashPayments as $booking)
                @php
                    $bCode = $booking->booking_code ?? ($booking->booking?->booking_code ?? 'BK-UNK');
                    $custName = $booking->user?->name ?? ($booking->customer_name ?? 'Khách hàng');
                    $movieTitle = $booking->showtime?->movie?->title ?? '';
                    $amount = $booking->final_amount ?? ($booking->total_price ?? ($booking->amount ?? 0));
                    $createdAt = $booking->created_at ? $booking->created_at->format('H:i') : '--:--';
                    $payMethod = $booking->payment?->payment_method ?? ($booking->payment_method ?? 'ONLINE');
                @endphp
                <a class="staff-list-item" href="{{ \App\Helpers\TabAuthHelper::route('staff.booking-lookup') }}">
                    <div class="staff-list-main">
                        <strong>{{ $bCode }} <span class="badge bg-secondary ms-1 small fw-normal">{{ $payMethod }}</span></strong>
                        <span>{{ $custName }}{{ $movieTitle ? ' · ' . $movieTitle : '' }} · <strong class="text-warning">{{ number_format($amount) }}đ</strong></span>
                    </div>
                    <div class="staff-list-meta">
                        {{ $createdAt }}
                    </div>
                </a>
            @empty
                <div class="staff-empty-state">
                    <i class="bi bi-receipt"></i>
                    <div>Chưa có đơn hàng nào thanh toán thành công trong ngày.</div>
                </div>
            @endforelse
        </div>
    </section>
</div>

{{-- Late Payment Alert Panel (hiển thị khi có thanh toán muộn) --}}
@if ($recentLatePayments->isNotEmpty())
<div class="staff-panel" style="margin-top: 18px; border-color: rgba(239,68,68,.35);">
    <div class="staff-panel-header" style="background: rgba(239,68,68,.08);">
        <h2 style="color:#f87171;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            Cảnh báo: Thanh toán muộn cần hoàn tiền
        </h2>
        <span class="badge" style="background:rgba(239,68,68,.2);color:#f87171;font-size:11px;padding:5px 10px;border-radius:999px;"
              title="Khách đã chuyển khoản sau khi đơn hết hạn 5 phút">
            {{ $recentLatePayments->count() }} trường hợp
        </span>
    </div>

    <div class="staff-panel-list">
        @foreach ($recentLatePayments as $lp)
            @php
                $lpBooking   = $lp->booking;
                $lpUser      = $lpBooking?->user;
                $lpMovie     = $lpBooking?->showtime?->movie;
                $lpNotes     = $lp->notes ?? [];
                $lpAmount    = number_format($lpNotes['transaction_amount'] ?? 0);
                $lpOrderCode = $lpNotes['order_code'] ?? '—';
                $lpTxDate    = $lpNotes['transaction_date'] ?? '';
            @endphp
            <div class="staff-list-item" style="flex-wrap:wrap; gap:8px;">
                <div class="staff-list-main" style="flex:1; min-width:200px;">
                    <strong style="color:#fca5a5;">
                        {{ $lpBooking?->booking_code ?? 'N/A' }}
                        <span style="font-size:11px;font-weight:400;margin-left:6px;color:#f87171;">⚠️ Thanh toán muộn</span>
                    </strong>
                    <span>
                        {{ $lpUser?->name ?? 'Khách vãng lai' }}
                        @if ($lpMovie) · {{ $lpMovie->title }} @endif
                    </span>
                    <span style="font-size:11px;color:#94a3b8;">
                        Mã đơn: {{ $lpOrderCode }} · GD: {{ $lpTxDate ? \Carbon\Carbon::parse($lpTxDate)->format('d/m H:i') : '—' }}
                    </span>
                </div>
                <div class="staff-list-meta" style="text-align:right;">
                    <span style="color:#fbbf24;font-weight:700;">{{ $lpAmount }}đ</span>
                    <span style="display:block;font-size:11px;color:#f87171;">Chờ hoàn tiền</span>
                </div>
            </div>
        @endforeach
    </div>

    <div style="padding: 12px 20px; border-top: 1px solid rgba(239,68,68,.2); font-size:12px; color:#94a3b8;">
        <i class="bi bi-info-circle me-1"></i>
        Liên hệ Admin để xử lý hoàn tiền. Admin có thể xem đầy đủ trong <strong style="color:#c4b5fd;">Quản lý đặt vé → Lịch sử Payment</strong>.
    </div>
</div>
@endif


@endsection
