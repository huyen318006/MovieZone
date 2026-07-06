@extends('layout.admin')

@section('title', 'Dashboard')

@section('content')
@php
    $dashboard = $dashboard ?? [];
    $metrics = $dashboard['metrics'] ?? [];
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
            <p class="mb-0">Tổng quan hệ thống (demo UI) • Bảng dữ liệu & biểu đồ tạm thời</p>
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
                    <div class="metric-label">Doanh thu hôm nay</div>
                    <div class="metric-value">—</div>
                </div>
                <div class="metric-icon metric-primary">
                    <i class="bi bi-cash-coin"></i>
                </div>
            </div>
            <div class="metric-meta">
                <span>So với hôm qua: <strong class="text-success">+0%</strong></span>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="metric-card metric-success">
            <div class="d-flex align-items-start justify-content-between gap-3">
                <div>
                    <div class="metric-label">Vé bán hôm nay</div>
                    <div class="metric-value">—</div>
                </div>
                <div class="metric-icon metric-success">
                    <i class="bi bi-ticket-perforated"></i>
                </div>
            </div>
            <div class="metric-meta">
                <span>Tổng đặt vé: <strong>—</strong></span>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="metric-card metric-warning">
            <div class="d-flex align-items-start justify-content-between gap-3">
                <div>
                    <div class="metric-label">Tỷ lệ lấp đầy</div>
                    <div class="metric-value">—</div>
                </div>
                <div class="metric-icon metric-warning">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
            </div>
            <div class="metric-meta">
                <span>Ước tính: <strong>—</strong></span>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="metric-card metric-danger">
            <div class="d-flex align-items-start justify-content-between gap-3">
                <div>
                    <div class="metric-label">Booking chờ thanh toán</div>
                    <div class="metric-value">—</div>
                </div>
                <div class="metric-icon metric-danger">
                    <i class="bi bi-clock-history"></i>
                </div>
            </div>
            <div class="metric-meta">
                <span>Đang chờ: <strong>—</strong></span>
            </div>
        </div>
    </div>

</div>

<div class="row g-4">

    {{-- Chart panel (bar chart demo via CSS) --}}
    <div class="col-12 col-xl-7">
        <section class="panel">
            <div class="panel-header">
                <div class="section-title">
                    <i class="bi bi-bar-chart"></i>
                    <div>
                        <h5 class="mb-0">Biểu đồ doanh thu (demo)</h5>
                        <p class="text-muted mb-0">Tạm thời chưa có dữ liệu thật</p>
                    </div>
                </div>
            </div>

            <div class="chart-bars" aria-label="Revenue chart (demo)">
                <div class="chart-column">
                    <span class="bar-42" title="Tháng 1"></span>
                    <div>Th1</div>
                </div>
                <div class="chart-column">
                    <span class="bar-58" title="Tháng 2"></span>
                    <div>Th2</div>
                </div>
                <div class="chart-column">
                    <span class="bar-51" title="Tháng 3"></span>
                    <div>Th3</div>
                </div>
                <div class="chart-column">
                    <span class="bar-72" title="Tháng 4"></span>
                    <div>Th4</div>
                </div>
                <div class="chart-column">
                    <span class="bar-66" title="Tháng 5"></span>
                    <div>Th5</div>
                </div>
                <div class="chart-column">
                    <span class="bar-83" title="Tháng 6"></span>
                    <div>Th6</div>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-3 mt-3">
                <div class="badge text-bg-primary">Demo</div>
                <div class="text-muted fw-bold">Sẽ thay bằng dữ liệu từ DB sau.</div>
            </div>
        </section>
    </div>

    {{-- Donut + activity (demo) --}}
    <div class="col-12 col-xl-5">
        <section class="panel">
            <div class="panel-header">
                <div class="section-title">
                    <i class="bi bi-pie-chart"></i>
                    <div>
                        <h5 class="mb-0">Trạng thái hệ thống (demo)</h5>
                        <p class="text-muted mb-0">Tạm thời chưa có dữ liệu thật</p>
                    </div>
                </div>
            </div>

            <div class="row g-4 align-items-center">
                <div class="col-12 col-sm-6">
                    <div class="donut-chart" role="img" aria-label="Filling rate (demo)">
                        <span>—%</span>
                    </div>
                </div>

                <div class="col-12 col-sm-6">
                    <div class="legend-list">
                        <div class="d-flex align-items-center gap-3">
                            <div class="legend-dot" style="background:#2563eb"></div>
                            <div class="flex-grow-1">Đang hoạt động</div>
                            <strong>—</strong>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="legend-dot" style="background:#0f766e"></div>
                            <div class="flex-grow-1">Ổn định</div>
                            <strong>—</strong>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="legend-dot" style="background:#d97706"></div>
                            <div class="flex-grow-1">Cảnh báo</div>
                            <strong>—</strong>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="legend-dot" style="background:#dc2626"></div>
                            <div class="flex-grow-1">Sự cố</div>
                            <strong>—</strong>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="border-2 my-4">

            <div class="activity-list">
                <div class="activity-item">
                    <div class="activity-dot" style="background:#2563eb"></div>
                    <div>
                        <div class="fw-bold">Cập nhật danh sách suất chiếu</div>
                        <div class="text-muted">Cách đây vài phút • Demo</div>
                    </div>
                </div>
                <div class="activity-item">
                    <div class="activity-dot" style="background:#0f766e"></div>
                    <div>
                        <div class="fw-bold">Thanh toán thành công</div>
                        <div class="text-muted">Cách đây 1 giờ • Demo</div>
                    </div>
                </div>
                <div class="activity-item">
                    <div class="activity-dot" style="background:#d97706"></div>
                    <div>
                        <div class="fw-bold">Booking chờ thanh toán</div>
                        <div class="text-muted">Cách đây 3 giờ • Demo</div>
                    </div>
                </div>
            </div>

        </section>
    </div>

</div>

{{-- Table panel --}}
<div class="row g-4 mt-1">
    <div class="col-12">
        <section class="panel">
            <div class="panel-header">
                <div class="section-title">
                    <i class="bi bi-table"></i>
                    <div>
                        <h5 class="mb-0">Bảng dữ liệu (demo)</h5>
                        <p class="text-muted mb-0">Chỉ dùng HTML/CSS/Bootstrap, chưa nối dữ liệu thật</p>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <input class="form-control table-search" type="search" placeholder="Tìm kiếm..." aria-label="Search">
                    <button class="btn btn-light" type="button"><i class="bi bi-filter"></i> Lọc</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Loại</th>
                            <th>Người thực hiện</th>
                            <th>Thời gian</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $rows = [
                                ['type' => 'Booking', 'actor' => 'Admin', 'time' => 'Hôm nay 09:12', 'status' => 'Thành công', 'badge' => 'text-bg-success'],
                                ['type' => 'Payment', 'actor' => 'User #12', 'time' => 'Hôm nay 08:45', 'status' => 'Đã thanh toán', 'badge' => 'text-bg-primary'],
                                ['type' => 'Showtime', 'actor' => 'Staff', 'time' => 'Hôm qua 19:30', 'status' => 'Đang xử lý', 'badge' => 'text-bg-warning'],
                                ['type' => 'Voucher', 'actor' => 'System', 'time' => 'Hôm qua 16:05', 'status' => 'Hết hạn', 'badge' => 'text-bg-danger'],
                                ['type' => 'Review', 'actor' => 'User #7', 'time' => '2 ngày trước', 'status' => 'Chờ duyệt', 'badge' => 'text-bg-secondary'],
                            ];
                        @endphp

                        @foreach ($rows as $i => $r)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td class="fw-bold">{{ $r['type'] }}</td>
                                <td>{{ $r['actor'] }}</td>
                                <td class="text-muted">{{ $r['time'] }}</td>
                                <td>
                                    <span class="badge {{ $r['badge'] }}">{{ $r['status'] }}</span>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-light btn-sm" type="button">Xem</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex align-items-center justify-content-between mt-3">
                <div class="text-muted">Hiển thị 5/50 • Demo</div>
                <nav aria-label="Pagination">
                    <ul class="pagination mb-0">
                        <li class="page-item disabled"><a class="page-link" href="#">Trước</a></li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item"><a class="page-link" href="#">Sau</a></li>
                    </ul>
                </nav>
            </div>

        </section>
    </div>
</div>

@endsection

