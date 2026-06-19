@extends('layout.admin')

@section('title', 'Quản lý suất chiếu')

@section('content')
    @php
        $statusMap = [
            'OPEN' => ['label' => 'Đang mở bán', 'class' => 'text-bg-success'],
            'CLOSED' => ['label' => 'Đã đóng', 'class' => 'text-bg-secondary'],
            'CANCELLED' => ['label' => 'Đã hủy', 'class' => 'text-bg-danger'],
        ];
    @endphp

    @if(session('success'))
        <div id="success-alert" class="alert alert-success alert-dismissible fade show shadow-sm">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div id="error-alert" class="alert alert-danger alert-dismissible fade show shadow-sm">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <h3 class="mb-1">Quản lý suất chiếu</h3>
            <p class="text-muted mb-0">Tạo, cập nhật, hủy và tra cứu suất chiếu trong rạp duy nhất của hệ thống.</p>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.showtime.add') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>
                Tạo suất chiếu
            </a>
            <a href="{{ route('admin.showtime') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-clockwise me-1"></i>
                Làm mới
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Tổng suất chiếu</div>
                    <div class="fs-3 fw-bold">{{ $showtimes->total() }}</div>
                    <div class="text-muted small">Kết quả sau khi lọc</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Phim đang dùng</div>
                    <div class="fs-3 fw-bold">{{ $movies->count() }}</div>
                    <div class="text-muted small">Phim khả dụng để tạo suất</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Phòng hoạt động</div>
                    <div class="fs-3 fw-bold">{{ $rooms->count() }}</div>
                    <div class="text-muted small">Phòng có thể xếp lịch</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Bộ lọc hiện tại</div>
                    <div class="fs-3 fw-bold">{{ collect($filters)->filter()->count() }}</div>
                    <div class="text-muted small">Số tiêu chí đang áp dụng</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <div class="fw-semibold">Bộ lọc suất chiếu</div>
                <small class="text-muted">Lọc theo phim, phòng, ngày chiếu và trạng thái.</small>
            </div>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('admin.showtime') }}" class="row g-3 align-items-end">
                <div class="col-12 col-md-6 col-xl-3">
                    <label class="form-label">Phim</label>
                    <select name="movie" class="form-select">
                        <option value="">Tất cả phim</option>
                        @foreach($movies as $movie)
                            <option value="{{ $movie->id }}" {{ request('movie') == $movie->id ? 'selected' : '' }}>
                                {{ $movie->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <label class="form-label">Phòng chiếu</label>
                    <select name="room" class="form-select">
                        <option value="">Tất cả phòng</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}" {{ request('room') == $room->id ? 'selected' : '' }}>
                                {{ $room->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <label class="form-label">Ngày chiếu</label>
                    <input type="date" name="date" class="form-control" value="{{ request('date') }}">
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <label class="form-label">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="OPEN" {{ request('status') === 'OPEN' ? 'selected' : '' }}>Đang mở bán</option>
                        <option value="CLOSED" {{ request('status') === 'CLOSED' ? 'selected' : '' }}>Đã đóng</option>
                        <option value="CANCELLED" {{ request('status') === 'CANCELLED' ? 'selected' : '' }}>Đã hủy</option>
                    </select>
                </div>

                <div class="col-12 d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-funnel me-1"></i>
                        Lọc danh sách
                    </button>
                    <a href="{{ route('admin.showtime') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i>
                        Xóa bộ lọc
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <div class="fw-semibold">Danh sách suất chiếu</div>
                <small class="text-muted">Các thao tác thay đổi đều được ghi log hệ thống.</small>
            </div>
            <div class="text-muted small">
                Hiển thị {{ $showtimes->count() }}/{{ $showtimes->total() }} bản ghi
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="min-width: 1200px;">
                    <thead>
                        <tr>
                            <th style="width: 70px;">#</th>
                            <th>Phim</th>
                            <th>Phòng</th>
                            <th>Rạp</th>
                            <th>Thời gian</th>
                            <th>Định dạng</th>
                            <th>Ngôn ngữ</th>
                            <th>Trạng thái</th>
                            <th style="width: 280px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($showtimes as $index => $showtime)
                            @php
                                $status = $statusMap[$showtime->status] ?? ['label' => $showtime->status, 'class' => 'text-bg-secondary'];
                            @endphp
                            <tr>
                                <td>{{ $showtimes->firstItem() + $index }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $showtime->movie?->title ?? 'N/A' }}</div>
                                    <small class="text-muted">#{{ $showtime->movie_id }}</small>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $showtime->room?->name ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $showtime->room?->room_type ?? '' }}</small>
                                </td>
                                <td>{{ $showtime->cinema?->name ?? 'N/A' }}</td>
                                <td>
                                    <div class="fw-semibold">{{ optional($showtime->start_time)->format('d/m/Y H:i') }}</div>
                                    <small class="text-muted">Đến {{ optional($showtime->end_time)->format('H:i') }}</small>
                                </td>
                                <td>
                                    <span class="badge text-bg-primary">{{ $showtime->format }}</span>
                                </td>
                                <td>{{ $showtime->language_type }}</td>
                                <td>
                                    <span class="badge {{ $status['class'] }}">{{ $status['label'] }}</span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a href="{{ route('detail.showtime', $showtime->id) }}" class="btn btn-outline-info btn-sm">
                                            <i class="bi bi-eye me-1"></i>
                                            Chi tiết
                                        </a>
                                        <a href="{{ route('admin.view.update.showtime', $showtime->id) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-pencil me-1"></i>
                                            Sửa
                                        </a>
                                        @if($showtime->status !== 'CANCELLED')
                                            <a href="{{ route('admin.showtime.confirm_cancel', $showtime->id) }}" class="btn btn-outline-warning btn-sm">
                                                <i class="bi bi-x-circle me-1"></i>
                                                Hủy
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">
                                    <i class="bi bi-calendar2-event fs-1 d-block mb-2"></i>
                                    Chưa có suất chiếu nào phù hợp với bộ lọc hiện tại.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3">
                <div class="text-muted">
                    Tổng số suất chiếu: {{ $showtimes->total() }}
                </div>
                {{ $showtimes->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
