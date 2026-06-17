@extends('layout.admin')

@section('title', 'Quản lý rạp chiếu')

@section('content')

{{-- Flash messages --}}
@if(session('success'))
    <div id="success-alert" class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle-fill me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div id="error-alert" class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

{{-- Header --}}
<div class="col-12">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h3 class="mb-1">Quản lý rạp chiếu</h3>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.cinemas.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i>
                Thêm rạp mới
            </a>
        </div>
    </div>
</div>

{{-- Table --}}
<div class="col-12 mt-3">
    <div class="card">
        <div class="card-header bg-transparent d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="fw-semibold">Danh sách rạp chiếu</div>
            <div class="d-flex gap-2">
                <form method="GET" action="{{ route('admin.cinemas.index') }}">
                    <div class="d-flex gap-2 align-items-center">

                        <h5 class="mb-0">Tìm kiếm</h5>

                        {{-- Search --}}
                        <input type="text"
                               name="search"
                               class="form-control form-control-sm"
                               style="width: 200px;"
                               placeholder="Tên rạp, thành phố..."
                               value="{{ request('search') }}">

                        {{-- Status --}}
                        <select name="status" class="form-select form-select-sm" style="width: 180px;"
                            onchange="this.form.submit()">
                            <option value="">Tất cả trạng thái</option>
                            <option value="ACTIVE" {{ request('status') == 'ACTIVE' ? 'selected' : '' }}>
                                Hoạt động
                            </option>
                            <option value="INACTIVE" {{ request('status') == 'INACTIVE' ? 'selected' : '' }}>
                                Tạm ngưng
                            </option>
                            <option value="MAINTENANCE" {{ request('status') == 'MAINTENANCE' ? 'selected' : '' }}>
                                Bảo trì
                            </option>
                        </select>

                        {{-- Submit search --}}
                        <button type="submit" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-search"></i>
                        </button>

                        {{-- Reset --}}
                        <a href="{{ route('admin.cinemas.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-clockwise"></i> Làm mới
                        </a>

                    </div>
                </form>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" style="min-width: 900px;">
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>Tên rạp</th>
                            <th>Thành phố</th>
                            <th>Quận / Huyện</th>
                            <th>Địa chỉ</th>
                            <th>Hotline</th>
                            <th>Số phòng</th>
                            <th>Trạng thái</th>
                            <th style="width: 200px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cinemas as $i => $cinema)
                            <tr>
                                <td>{{ $cinemas->firstItem() + $i }}</td>

                                <td>
                                    <div class="fw-semibold">{{ $cinema->name }}</div>
                                </td>

                                <td class="text-muted">{{ $cinema->city }}</td>

                                <td class="text-muted">{{ $cinema->district ?? '—' }}</td>

                                <td class="text-muted" style="max-width: 200px;">
                                    <span title="{{ $cinema->address }}">
                                        {{ Str::limit($cinema->address, 40) }}
                                    </span>
                                </td>

                                <td class="text-muted">
                                    @if($cinema->hotline)
                                        <i class="bi bi-telephone me-1"></i>{{ $cinema->hotline }}
                                    @else
                                        —
                                    @endif
                                </td>

                                <td>
                                    <span class="badge text-bg-info">
                                        <i class="bi bi-door-open me-1"></i>{{ $cinema->rooms_count }} phòng
                                    </span>
                                </td>

                                <td>
                                    @php
                                        $badgeClass = 'text-bg-secondary';
                                        $statusText = $cinema->status;

                                        if ($cinema->status === 'ACTIVE') {
                                            $badgeClass = 'text-bg-success';
                                            $statusText = 'Hoạt động';
                                        } elseif ($cinema->status === 'INACTIVE') {
                                            $badgeClass = 'text-bg-secondary';
                                            $statusText = 'Tạm ngưng';
                                        } elseif ($cinema->status === 'MAINTENANCE') {
                                            $badgeClass = 'text-bg-warning';
                                            $statusText = 'Bảo trì';
                                        }
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ $statusText }}</span>
                                </td>

                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a href="{{ route('admin.cinemas.edit', $cinema->id) }}"
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-pencil"></i> Sửa
                                        </a>

                                        <button type="button"
                                                class="btn btn-outline-danger btn-sm btn-delete-cinema"
                                                data-cinema-id="{{ $cinema->id }}"
                                                data-cinema-name="{{ $cinema->name }}"
                                                data-cinema-rooms="{{ $cinema->rooms_count }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteCinemaModal">
                                            <i class="bi bi-trash"></i> Xoá
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="bi bi-building fs-1 d-block mb-2"></i>
                                    Chưa có rạp chiếu nào.
                                    <a href="{{ route('admin.cinemas.create') }}">Thêm rạp mới</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex align-items-center justify-content-between mt-3">
                <div class="text-muted">
                    Tổng số rạp: {{ $cinemas->total() }}
                </div>
                {{ $cinemas->links() }}
            </div>
        </div>
    </div>
</div>

{{-- Modal xác nhận xoá --}}
<div class="modal fade" id="deleteCinemaModal" tabindex="-1" aria-labelledby="deleteCinemaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-danger">
                <h5 class="modal-title" id="deleteCinemaModalLabel">
                    <i class="bi bi-exclamation-triangle text-danger me-2"></i>Xác nhận xoá rạp
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xoá rạp <strong id="deleteCinemaName"></strong>?</p>
                <div id="deleteCinemaWarning" class="alert alert-warning d-none">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Rạp này đang có <strong id="deleteCinemaRooms"></strong> phòng chiếu. Không thể xoá!
                </div>
                <p class="text-muted mb-0">Thao tác này không thể hoàn tác.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ bỏ</button>
                <form id="deleteCinemaForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button type="submit" id="deleteCinemaBtn" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Xoá rạp
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Auto-hide alerts
    ['success-alert', 'error-alert'].forEach(function (id) {
        const alertEl = document.getElementById(id);
        if (alertEl) {
            setTimeout(() => {
                alertEl.classList.remove('show');
                setTimeout(() => alertEl.remove(), 150);
            }, 3000);
        }
    });

    // Delete modal logic
    document.querySelectorAll('.btn-delete-cinema').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const cinemaId = this.dataset.cinemaId;
            const cinemaName = this.dataset.cinemaName;
            const cinemaRooms = parseInt(this.dataset.cinemaRooms);

            document.getElementById('deleteCinemaName').textContent = cinemaName;
            document.getElementById('deleteCinemaForm').action = '/admin/cinemas/' + cinemaId;

            const warningEl = document.getElementById('deleteCinemaWarning');
            const deleteBtn = document.getElementById('deleteCinemaBtn');

            if (cinemaRooms > 0) {
                document.getElementById('deleteCinemaRooms').textContent = cinemaRooms;
                warningEl.classList.remove('d-none');
                deleteBtn.disabled = true;
                deleteBtn.classList.add('disabled');
            } else {
                warningEl.classList.add('d-none');
                deleteBtn.disabled = false;
                deleteBtn.classList.remove('disabled');
            }
        });
    });
});
</script>
@endpush
