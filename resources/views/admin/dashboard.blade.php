@extends('layout.admin')

@section('title', 'Dashboard')

@section('content')
@php
    use Illuminate\Support\Str;
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
        'failed_payment' => 0,
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
    $showtimePerformance = $dashboard['showtime_performance'] ?? collect();
    $timeSlotPerformance = $dashboard['time_slot_performance'] ?? collect();
    $comboStats = $dashboard['combo_stats'] ?? [
        'top_combos' => collect(),
        'combo_quantity' => 0,
        'combo_revenue' => 0,
        'booking_with_combo_rate' => 0,
    ];
    $filters = $dashboard['filters'] ?? [];
    $filterOptions = $filterOptions ?? ['movies' => collect()];
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
            <p class="mb-0">Phân tích doanh thu, booking, suất chiếu, khung giờ, combo/voucher và hiệu quả khai thác phòng</p>
        </div>
    </div>

    <div class="heading-actions">
        <button class="btn btn-light" type="button" onclick="window.location.reload()">
            <i class="bi bi-arrow-clockwise"></i>
            Làm mới
        </button>
    </div>

</div>

<section class="panel mb-4">
    <div class="panel-header">
        <div class="section-title">
            <i class="bi bi-link-45deg"></i>
            <div>
                <h5 class="mb-0">Truy cập nhanh</h5>
                <p class="text-muted mb-0">Đi tới các trang quản lý chính ngay trên dashboard.</p>
            </div>
        </div>
    </div>
    <div class="panel-body">
        <div class="row g-2">
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ \App\Helpers\TabAuthHelper::route('admin.film') }}" class="btn btn-outline-secondary w-100 text-start">
                    <i class="bi bi-film me-2"></i> Quản lý phim
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ \App\Helpers\TabAuthHelper::route('admin.showtime') }}" class="btn btn-outline-secondary w-100 text-start">
                    <i class="bi bi-calendar3 me-2"></i> Quản lý suất chiếu
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ \App\Helpers\TabAuthHelper::route('admin.products.index') }}" class="btn btn-outline-secondary w-100 text-start">
                    <i class="bi bi-basket me-2"></i> Sản phẩm lẻ
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ \App\Helpers\TabAuthHelper::route('admin.combos.index') }}" class="btn btn-outline-secondary w-100 text-start">
                    <i class="bi bi-box-seam me-2"></i> Quản lý combo
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ \App\Helpers\TabAuthHelper::route('admin.promotions.index') }}" class="btn btn-outline-secondary w-100 text-start">
                    <i class="bi bi-tag me-2"></i> Khuyến mãi
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ \App\Helpers\TabAuthHelper::route('admin.banners.index') }}" class="btn btn-outline-secondary w-100 text-start">
                    <i class="bi bi-card-image me-2"></i> Banner
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ \App\Helpers\TabAuthHelper::route('admin.list_account') }}" class="btn btn-outline-secondary w-100 text-start">
                    <i class="bi bi-people me-2"></i> Tài khoản
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ \App\Helpers\TabAuthHelper::route('admin.bookings.index') }}" class="btn btn-outline-secondary w-100 text-start">
                    <i class="bi bi-receipt me-2"></i> Booking
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ \App\Helpers\TabAuthHelper::route('admin.articles.index') }}" class="btn btn-outline-secondary w-100 text-start">
                    <i class="bi bi-journal-text me-2"></i> Tin tức
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ \App\Helpers\TabAuthHelper::route('admin.vouchers.index') }}" class="btn btn-outline-secondary w-100 text-start">
                    <i class="bi bi-ticket-perforated me-2"></i> Voucher
                </a>
            </div>
        </div>
    </div>
</section>

    <form method="GET" action="{{ \App\Helpers\TabAuthHelper::route('admin.dashboard') }}" class="row g-3 align-items-end">
        <input type="hidden" name="tab_token" value="{{ \App\Helpers\TabAuthHelper::gettoken() }}">
        <div class="col-md-4">
            <label class="form-label fw-bold">Ngày bắt đầu</label>
            <input type="date" name="start_date" value="{{ $startDateValue }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-bold">Ngày kết thúc</label>
            <input type="date" name="end_date" value="{{ $endDateValue }}" class="form-control">
        </div>
        <div class="col-md-4">

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
        <div class="col-12 d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ \App\Helpers\TabAuthHelper::route('admin.dashboard', ['start_date' => now()->format('Y-m-d'), 'end_date' => now()->format('Y-m-d'), 'movie_id' => $filters['movie_id'] ?? null]) }}" class="btn btn-outline-primary">
                    Hôm nay
                </a>
                <a href="{{ \App\Helpers\TabAuthHelper::route('admin.dashboard', ['start_date' => now()->subDays(6)->format('Y-m-d'), 'end_date' => now()->format('Y-m-d'), 'movie_id' => $filters['movie_id'] ?? null]) }}" class="btn btn-outline-primary">
                    7 ngày gần nhất
                </a>
                <a href="{{ \App\Helpers\TabAuthHelper::route('admin.dashboard', ['start_date' => now()->startOfMonth()->format('Y-m-d'), 'end_date' => now()->endOfMonth()->format('Y-m-d'), 'movie_id' => $filters['movie_id'] ?? null]) }}" class="btn btn-outline-primary">
                    Tháng hiện tại
                </a>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ \App\Helpers\TabAuthHelper::route('admin.dashboard') }}" class="btn btn-light">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Đặt lại
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel me-1"></i> Lọc thống kê toàn bộ
                </button>
            </div>
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
                <span>Doanh thu từ thanh toán thành công</span>
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
            <i class="bi bi-film"></i>
            <div>
                <h5 class="mb-0">Top phim bán chạy</h5>
                <p class="text-muted mb-0">Hiển thị poster và doanh thu theo phim trong bộ lọc.</p>
            </div>
        </div>
    </div>

    @if ($topMovies->isEmpty())
        <div class="text-muted fw-bold py-3 px-3">
            <i class="bi bi-inbox me-1"></i> Chưa có dữ liệu phim để hiển thị.
        </div>
    @else
        <div class="row g-3">
            @foreach ($topMovies as $movie)
                <div class="col-6 col-xl-3">
                    <div class="card h-100 shadow-sm overflow-hidden">
                        <div class="ratio ratio-3x4">
                            <img src="{{ $movie->poster }}" alt="Poster {{ $movie->title }}" class="object-fit-cover">
                        </div>
                        <div class="card-body py-3 px-3">
                            <h6 class="mb-2 text-truncate">{{ $movie->title }}</h6>
                            <div class="text-muted small">Vé bán: {{ number_format($movie->sold_tickets) }}</div>
                            <div class="text-muted small">Doanh thu: {{ number_format($movie->ticket_revenue) }}đ</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>

<div class="d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-bar-chart-line text-primary"></i>
    <h4 class="mb-0">Phân tích nâng cao</h4>
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
                <div class="text-muted small">Thanh toán thất bại</div>
                <div class="fs-4 fw-bold text-danger">{{ number_format($bookingStatusStats['failed_payment'] ?? 0) }}</div>
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

<section class="panel mb-4">
    <div class="panel-header">
        <div class="section-title">
            <i class="bi bi-calendar2-check"></i>
            <div>
                <h5 class="mb-0">Xếp hạng suất chiếu</h5>
                <p class="text-muted mb-0">Xếp hạng suất chiếu theo tỷ lệ lấp đầy và vé bán</p>
            </div>
        </div>
        @if ($showtimePerformance->isNotEmpty())
            <div class="badge text-bg-success">
                Tốt nhất: {{ number_format($showtimePerformance->first()->occupancy_rate, 1) }}%
            </div>
        @endif
    </div>

    @if ($showtimePerformance->isEmpty())
        <div class="text-muted fw-bold py-3">
            <i class="bi bi-inbox me-1"></i> Chưa có suất chiếu để thống kê trong bộ lọc.
        </div>
    @else
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Suất chiếu</th>
                        <th>Rạp / Phòng</th>
                        <th class="text-end">Vé bán</th>
                        <th class="text-end">Tổng ghế</th>
                        <th class="text-end">Doanh thu vé</th>
                        <th class="text-end">Lấp đầy</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($showtimePerformance as $showtime)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $showtime->movie_title }}</div>
                                <div class="text-muted small">{{ \Carbon\Carbon::parse($showtime->start_time)->format('d/m/Y H:i') }}</div>
                            </td>
                            <td>
                                <div>{{ $showtime->cinema_name }}</div>
                                <div class="text-muted small">{{ $showtime->room_name }}</div>
                            </td>
                            <td class="text-end">{{ number_format($showtime->sold_tickets) }}</td>
                            <td class="text-end">{{ number_format($showtime->total_seats) }}</td>
                            <td class="text-end">{{ number_format($showtime->ticket_revenue) }}đ</td>
                            <td class="text-end">
                                <span class="badge {{ $showtime->occupancy_rate >= 70 ? 'text-bg-success' : ($showtime->occupancy_rate >= 40 ? 'text-bg-warning' : 'text-bg-danger') }}">
                                    {{ number_format($showtime->occupancy_rate, 1) }}%
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>

<section class="panel mb-4">
    <div class="panel-header">
        <div class="section-title">
            <i class="bi bi-clock-history"></i>
            <div>
                <h5 class="mb-0">Khung giờ hiệu quả</h5>
                <p class="text-muted mb-0">So sánh sáng, chiều, tối theo vé bán và tỷ lệ lấp đầy</p>
            </div>
        </div>
        @if ($timeSlotPerformance->isNotEmpty())
            <div class="badge text-bg-info">
                Tốt nhất: {{ $timeSlotPerformance->first()->slot_label }}
            </div>
        @endif
    </div>

    @if ($timeSlotPerformance->isEmpty())
        <div class="text-muted fw-bold py-3">
            <i class="bi bi-inbox me-1"></i> Chưa có dữ liệu khung giờ trong bộ lọc.
        </div>
    @else
        <div class="row g-3">
            @foreach ($timeSlotPerformance as $slot)
                <div class="col-md-4">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="fw-bold fs-5">{{ $slot->slot_label }}</div>
                            <span class="badge {{ $slot->occupancy_rate >= 70 ? 'text-bg-success' : ($slot->occupancy_rate >= 40 ? 'text-bg-warning' : 'text-bg-danger') }}">
                                {{ number_format($slot->occupancy_rate, 1) }}%
                            </span>
                        </div>
                        <div class="text-muted small">Suất chiếu: {{ number_format($slot->showtime_count) }}</div>
                        <div class="text-muted small">Vé bán: {{ number_format($slot->sold_tickets) }} / {{ number_format($slot->total_seats) }} ghế</div>
                        <div class="fw-bold mt-2">{{ number_format($slot->ticket_revenue) }}đ</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>

<section class="panel mb-4">
    <div class="panel-header">
        <div class="section-title">
            <i class="bi bi-cup-straw"></i>
            <div>
                <h5 class="mb-0">Combo bán chạy</h5>
                <p class="text-muted mb-0">Theo dõi doanh thu bắp nước và combo được mua nhiều</p>
            </div>
        </div>
        <div class="badge text-bg-success">
            {{ number_format($comboStats['booking_with_combo_rate'] ?? 0, 1) }}% booking có combo
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="border rounded-3 p-3 h-100">
                <div class="text-muted small">Số lượng combo bán</div>
                <div class="fs-4 fw-bold text-primary">{{ number_format($comboStats['combo_quantity'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="border rounded-3 p-3 h-100">
                <div class="text-muted small">Doanh thu combo</div>
                <div class="fs-4 fw-bold text-success">{{ number_format($comboStats['combo_revenue'] ?? 0) }}đ</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="border rounded-3 p-3 h-100">
                <div class="text-muted small">Tỷ lệ booking mua combo</div>
                <div class="fs-4 fw-bold text-warning">{{ number_format($comboStats['booking_with_combo_rate'] ?? 0, 1) }}%</div>
            </div>
        </div>
    </div>

    @if (($comboStats['top_combos'] ?? collect())->isEmpty())
        <div class="text-muted fw-bold py-3">
            <i class="bi bi-inbox me-1"></i> Chưa có dữ liệu combo trong bộ lọc.
        </div>
    @else
        <div class="activity-list">
            @foreach ($comboStats['top_combos'] as $index => $combo)
                <div class="activity-item">
                    <div class="activity-dot" style="background:{{ $index === 0 ? '#f59e0b' : '#0f766e' }}"></div>
                    <div class="flex-grow-1">
                        <div class="fw-bold">#{{ $index + 1 }} {{ $combo->name }}</div>
                        <div class="text-muted">
                            {{ number_format($combo->quantity) }} combo · {{ number_format($combo->revenue) }}đ doanh thu
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>


<div class="row g-4 mt-1">
    <div class="col-12">
        <section class="panel">
            <div class="panel-header">
                <div class="section-title">
                    <i class="bi bi-door-open"></i>
                    <div>
                        <h5 class="mb-0">Hiệu quả khai thác phòng</h5>
                        <p class="text-muted mb-0">Gộp nhiều suất chiếu để xem phòng nào khai thác chưa tốt</p>
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

