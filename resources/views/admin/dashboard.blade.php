@extends('layout.admin')

@section('title', 'Dashboard')

@section('content')
@php
    $dashboard = $dashboard ?? [];
    $metrics = $dashboard['metrics'] ?? [];
    $topMovies = $dashboard['top_movies'] ?? collect();
    $recentBookings = $dashboard['recent_bookings'] ?? collect();
    $roomPerformance = $dashboard['room_performance'] ?? collect();
    $leastEffectiveRoom = $dashboard['least_effective_room'] ?? null;
    $bookingStatusStats = $dashboard['booking_status_stats'] ?? [
        'total' => 0,
        'paid' => 0,
        'pending' => 0,
        'cancelled' => 0,
        'expired' => 0,
        'success_rate' => 0,
    ];
    $revenueBreakdown = $dashboard['revenue_breakdown'] ?? [
        'ticket_revenue' => 0,
        'combo_revenue' => 0,
        'product_revenue' => 0,
        'concession_revenue' => 0,
        'total_revenue' => 0,
    ];
    $voucherStats = $dashboard['voucher_stats'] ?? [
        'usage_count' => 0,
        'discount_amount' => 0,
        'top_vouchers' => collect(),
    ];
    $filters = $dashboard['filters'] ?? [];
    $filterOptions = $filterOptions ?? ['cinemas' => collect(), 'movies' => collect()];
    $startDateValue = isset($filters['start_date']) ? $filters['start_date']->format('Y-m-d') : request('start_date');
    $endDateValue = isset($filters['end_date']) ? $filters['end_date']->format('Y-m-d') : request('end_date');
@endphp

@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm mb-4">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        {{ $errors->first() }}
    </div>
@endif

@if (!empty($dashboardError))
    <div class="alert alert-warning border-0 shadow-sm mb-4">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        {{ $dashboardError }}
    </div>
@endif
<div class="page-heading">

    <div class="page-heading-copy">
        <div class="page-icon">
            <i class="bi bi-speedometer2"></i>
        </div>

        <div>
            <h2 class="mb-1">Admin Dashboard</h2>
            <p class="mb-0">Theo dõi doanh thu, vé bán, tỷ lệ lấp đầy, phim bán chạy và hiệu suất phòng chiếu</p>
        </div>
    </div>

    <div class="heading-actions">
        <button class="btn btn-light" type="button">
            <i class="bi bi-arrow-clockwise"></i>
            Làm mới
        </button>
        <button class="btn btn-primary" type="button">
            <i class="bi bi-download"></i>
            Export
        </button>
    </div>

</div>

<section class="panel mb-4">
    <form method="GET" action="{{ route('admin.dashboard') }}" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label fw-bold">Ngày bắt đầu</label>
            <input type="date" name="start_date" value="{{ $startDateValue }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold">Ngày kết thúc</label>
            <input type="date" name="end_date" value="{{ $endDateValue }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold">Rạp</label>
            <select name="cinema_id" class="form-select">
                <option value="">Tất cả rạp</option>
                @foreach ($filterOptions['cinemas'] as $cinema)
                    <option value="{{ $cinema->id }}" @selected((string) ($filters['cinema_id'] ?? '') === (string) $cinema->id)>
                        {{ $cinema->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold">Phim</label>
            <select name="movie_id" class="form-select">
                <option value="">Tất cả phim</option>
                @foreach ($filterOptions['movies'] as $movie)
                    <option value="{{ $movie->id }}" @selected((string) ($filters['movie_id'] ?? '') === (string) $movie->id)>
                        {{ $movie->title }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-12 d-flex gap-2 justify-content-end">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-light">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Đặt lại
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-funnel me-1"></i> Lọc thống kê
            </button>
        </div>
    </form>
</section>

{{-- Metric cards --}}
<div class="row g-4 mb-4">

    <div class="col-sm-6 col-lg-3">
        <div class="metric-card metric-primary">
            <div class="d-flex align-items-start justify-content-between gap-3">
                <div>
                    <div class="metric-label">Tổng doanh thu</div>
                    <div class="metric-value">{{ number_format($metrics['revenue'] ?? 0) }}đ</div>
                </div>
                <div class="metric-icon metric-primary">
                    <i class="bi bi-cash-coin"></i>
                </div>
            </div>
            <div class="metric-meta">
                <span>Payment SUCCESS trong bộ lọc</span>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="metric-card metric-success">
            <div class="d-flex align-items-start justify-content-between gap-3">
                <div>
                    <div class="metric-label">Vé đã bán</div>
                    <div class="metric-value">{{ number_format($metrics['sold_tickets'] ?? 0) }}</div>
                </div>
                <div class="metric-icon metric-success">
                    <i class="bi bi-ticket-perforated"></i>
                </div>
            </div>
            <div class="metric-meta">
                <span>Ghế thuộc booking đã thanh toán</span>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="metric-card metric-warning">
            <div class="d-flex align-items-start justify-content-between gap-3">
                <div>
                    <div class="metric-label">Tỷ lệ lấp đầy</div>
                    <div class="metric-value">{{ number_format($metrics['occupancy_rate'] ?? 0, 1) }}%</div>
                </div>
                <div class="metric-icon metric-warning">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
            </div>
            <div class="metric-meta">
                <span>Ghế bán / tổng ghế suất chiếu</span>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="metric-card metric-danger">
            <div class="d-flex align-items-start justify-content-between gap-3">
                <div>
                    <div class="metric-label">Booking mới</div>
                    <div class="metric-value">{{ number_format($metrics['new_bookings'] ?? 0) }}</div>
                </div>
                <div class="metric-icon metric-danger">
                    <i class="bi bi-clock-history"></i>
                </div>
            </div>
            <div class="metric-meta">
                <span>Booking tạo trong bộ lọc</span>
            </div>
        </div>
    </div>

</div>

<section class="panel mb-4">
    <div class="panel-header">
        <div class="section-title">
            <i class="bi bi-pie-chart"></i>
            <div>
                <h5 class="mb-0">Trạng thái booking</h5>
                <p class="text-muted mb-0">Theo dõi chất lượng booking trong bộ lọc</p>
            </div>
        </div>
        <div class="badge text-bg-primary">
            Thành công: {{ number_format($bookingStatusStats['success_rate'] ?? 0, 1) }}%
        </div>
    </div>

    <div class="row g-3">
        <div class="col-6 col-lg-2">
            <div class="border rounded-3 p-3 h-100">
                <div class="text-muted small">Tổng booking</div>
                <div class="fs-4 fw-bold">{{ number_format($bookingStatusStats['total'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="border rounded-3 p-3 h-100">
                <div class="text-muted small">Đã thanh toán</div>
                <div class="fs-4 fw-bold text-success">{{ number_format($bookingStatusStats['paid'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="border rounded-3 p-3 h-100">
                <div class="text-muted small">Chờ thanh toán</div>
                <div class="fs-4 fw-bold text-warning">{{ number_format($bookingStatusStats['pending'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="border rounded-3 p-3 h-100">
                <div class="text-muted small">Đã hủy</div>
                <div class="fs-4 fw-bold text-danger">{{ number_format($bookingStatusStats['cancelled'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="border rounded-3 p-3 h-100">
                <div class="text-muted small">Hết hạn</div>
                <div class="fs-4 fw-bold text-secondary">{{ number_format($bookingStatusStats['expired'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="border rounded-3 p-3 h-100">
                <div class="text-muted small">Tỷ lệ thành công</div>
                <div class="fs-4 fw-bold text-primary">{{ number_format($bookingStatusStats['success_rate'] ?? 0, 1) }}%</div>
            </div>
        </div>
    </div>
</section>

<section class="panel mb-4">
    <div class="panel-header">
        <div class="section-title">
            <i class="bi bi-cash-stack"></i>
            <div>
                <h5 class="mb-0">Cơ cấu doanh thu</h5>
                <p class="text-muted mb-0">Tách doanh thu vé và bắp nước trong bộ lọc</p>
            </div>
        </div>
        <div class="badge text-bg-success">
            Tổng: {{ number_format($revenueBreakdown['total_revenue'] ?? 0) }}đ
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6 col-xl-3">
            <div class="border rounded-3 p-3 h-100">
                <div class="text-muted small">Doanh thu vé</div>
                <div class="fs-4 fw-bold text-primary">{{ number_format($revenueBreakdown['ticket_revenue'] ?? 0) }}đ</div>
                <div class="text-muted small">Từ ghế đã bán</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="border rounded-3 p-3 h-100">
                <div class="text-muted small">Doanh thu combo</div>
                <div class="fs-4 fw-bold text-success">{{ number_format($revenueBreakdown['combo_revenue'] ?? 0) }}đ</div>
                <div class="text-muted small">Combo bắp nước</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="border rounded-3 p-3 h-100">
                <div class="text-muted small">Doanh thu sản phẩm lẻ</div>
                <div class="fs-4 fw-bold text-warning">{{ number_format($revenueBreakdown['product_revenue'] ?? 0) }}đ</div>
                <div class="text-muted small">Bắp/nước/snack bán lẻ</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="border rounded-3 p-3 h-100">
                <div class="text-muted small">Doanh thu bắp nước</div>
                <div class="fs-4 fw-bold text-danger">{{ number_format($revenueBreakdown['concession_revenue'] ?? 0) }}đ</div>
                <div class="text-muted small">Combo + sản phẩm lẻ</div>
            </div>
        </div>
    </div>
</section>

<section class="panel mb-4">
    <div class="panel-header">
        <div class="section-title">
            <i class="bi bi-ticket-detailed"></i>
            <div>
                <h5 class="mb-0">Voucher và giảm giá</h5>
                <p class="text-muted mb-0">Theo dõi mức độ sử dụng ưu đãi trong bộ lọc</p>
            </div>
        </div>
        <div class="badge text-bg-warning">
            Đã giảm: {{ number_format($voucherStats['discount_amount'] ?? 0) }}đ
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="border rounded-3 p-3 h-100">
                <div class="text-muted small">Lượt dùng voucher</div>
                <div class="fs-4 fw-bold text-primary">{{ number_format($voucherStats['usage_count'] ?? 0) }}</div>
                <div class="text-muted small">Từ các booking hợp lệ</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="border rounded-3 p-3 h-100">
                <div class="text-muted small">Tổng tiền giảm</div>
                <div class="fs-4 fw-bold text-warning">{{ number_format($voucherStats['discount_amount'] ?? 0) }}đ</div>
                <div class="text-muted small">Tổng discount_amount</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="border rounded-3 p-3 h-100">
                <div class="text-muted small">Voucher nổi bật</div>
                @if (($voucherStats['top_vouchers'] ?? collect())->isNotEmpty())
                    @foreach ($voucherStats['top_vouchers']->take(2) as $voucher)
                        <div class="d-flex justify-content-between gap-2 mt-2">
                            <span class="fw-bold">{{ $voucher->code }}</span>
                            <span class="text-muted">{{ number_format($voucher->usage_count) }} lượt</span>
                        </div>
                    @endforeach
                @else
                    <div class="fs-6 fw-bold text-muted mt-2">Chưa có dữ liệu voucher</div>
                @endif
            </div>
        </div>
    </div>
</section>

<div class="row g-4">

    {{-- Top movies --}}
    <div class="col-12 col-xl-7">
        <section class="panel">
            <div class="panel-header">
                <div class="section-title">
                    <i class="bi bi-trophy"></i>
                    <div>
                        <h5 class="mb-0">Phim bán chạy</h5>
                        <p class="text-muted mb-0">Xếp hạng theo số vé bán trong bộ lọc</p>
                    </div>
                </div>
            </div>

            <div class="activity-list">
                @forelse ($topMovies as $index => $movie)
                    <div class="activity-item">
                        <div class="activity-dot" style="background:{{ $index === 0 ? '#f59e0b' : '#2563eb' }}"></div>
                        <div class="flex-grow-1">
                            <div class="fw-bold">#{{ $index + 1 }} {{ $movie->title }}</div>
                            <div class="text-muted">
                                {{ number_format($movie->sold_tickets) }} vé · {{ number_format($movie->ticket_revenue) }}đ doanh thu vé
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-muted fw-bold py-3">
                        <i class="bi bi-inbox me-1"></i> Chưa có dữ liệu phim bán chạy trong bộ lọc.
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    {{-- Recent bookings --}}
    <div class="col-12 col-xl-5">
        <section class="panel">
            <div class="panel-header">
                <div class="section-title">
                    <i class="bi bi-receipt"></i>
                    <div>
                        <h5 class="mb-0">Booking mới</h5>
                        <p class="text-muted mb-0">Các booking mới nhất trong bộ lọc</p>
                    </div>
                </div>
            </div>

            <div class="activity-list">
                @forelse ($recentBookings as $booking)
                    <a class="activity-item text-decoration-none" href="{{ route('admin.bookings.index') }}" title="Mở quản lý booking">
                        <div class="activity-dot" style="background:#0f766e"></div>
                        <div class="flex-grow-1">
                            <div class="fw-bold text-body">{{ $booking->booking_code }}</div>
                            <div class="text-muted">
                                {{ $booking->user?->name ?? 'Khách hàng' }} · {{ $booking->showtime?->movie?->title ?? 'Không rõ phim' }}
                            </div>
                            <div class="text-muted small">
                                {{ optional($booking->created_at)->format('d/m/Y H:i') }} · {{ number_format($booking->final_amount) }}đ
                            </div>
                        </div>
                        <span class="badge text-bg-secondary">{{ $booking->status }}</span>
                    </a>
                @empty
                    <div class="text-muted fw-bold py-3">
                        <i class="bi bi-inbox me-1"></i> Chưa có booking mới trong bộ lọc.
                    </div>
                @endforelse
            </div>
        </section>
    </div>

</div>

<div class="row g-4 mt-1">
    <div class="col-12">
        <section class="panel">
            <div class="panel-header">
                <div class="section-title">
                    <i class="bi bi-door-open"></i>
                    <div>
                        <h5 class="mb-0">Hiệu suất phòng chiếu</h5>
                        <p class="text-muted mb-0">Phòng có suất chiếu nhưng tỷ lệ lấp đầy thấp nhất trong bộ lọc</p>
                    </div>
                </div>

                @if ($leastEffectiveRoom)
                    <div class="badge text-bg-warning">
                        Kém hiệu quả nhất: {{ $leastEffectiveRoom->room_name }} · {{ number_format($leastEffectiveRoom->occupancy_rate, 1) }}%
                    </div>
                @endif
            </div>

            @if ($roomPerformance->isEmpty())
                <div class="text-muted fw-bold py-3">
                    <i class="bi bi-inbox me-1"></i> Chưa có suất chiếu để thống kê hiệu suất phòng trong bộ lọc.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Phòng</th>
                                <th class="text-end">Suất chiếu</th>
                                <th class="text-end">Ghế đã bán</th>
                                <th class="text-end">Tổng ghế</th>
                                <th class="text-end">Tỷ lệ lấp đầy</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($roomPerformance as $room)
                                <tr>
                                    <td class="fw-bold">{{ $room->room_name }}</td>
                                    <td class="text-end">{{ number_format($room->showtime_count) }}</td>
                                    <td class="text-end">{{ number_format($room->sold_seats) }}</td>
                                    <td class="text-end">{{ number_format($room->total_seats) }}</td>
                                    <td class="text-end">
                                        <span class="badge {{ $room->occupancy_rate < 30 ? 'text-bg-danger' : ($room->occupancy_rate < 60 ? 'text-bg-warning' : 'text-bg-success') }}">
                                            {{ number_format($room->occupancy_rate, 1) }}%
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</div>


@endsection

