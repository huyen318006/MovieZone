@extends('layout.admin')

@section('title', 'Quản lý Banner')

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle-fill me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="col-12">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h3 class="mb-1">Quản lý Banner</h3>
            <p class="text-muted mb-0">Quản lý các hình ảnh banner trình chiếu quảng cáo ở trang chủ và chi tiết.</p>
        </div>
        <div>
            <a href="{{ route('admin.banners.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Thêm Banner
            </a>
        </div>
    </div>
</div>

<div class="col-12 mt-3">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="fw-semibold">Danh sách Banner</div>
            <form method="GET" action="{{ route('admin.banners.index') }}" class="d-flex gap-2 align-items-center">
                <select name="position" class="form-select form-select-sm" style="width: 150px;" onchange="this.form.submit()">
                    <option value="">Tất cả vị trí</option>
                    <option value="HOME_TOP" {{ request('position') == 'HOME_TOP' ? 'selected' : '' }}>HOME_TOP (Trang chủ đầu)</option>
                    <option value="HOME_MIDDLE" {{ request('position') == 'HOME_MIDDLE' ? 'selected' : '' }}>HOME_MIDDLE (Trang chủ giữa)</option>
                </select>
                <select name="status" class="form-select form-select-sm" style="width: 140px;" onchange="this.form.submit()">
                    <option value="">Tất cả trạng thái</option>
                    <option value="ACTIVE" {{ request('status') == 'ACTIVE' ? 'selected' : '' }}>Hoạt động</option>
                    <option value="INACTIVE" {{ request('status') == 'INACTIVE' ? 'selected' : '' }}>Ngừng hoạt động</option>
                </select>
                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-search"></i></button>
                <a href="{{ route('admin.banners.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-clockwise"></i></a>
            </form>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th style="width: 150px;">Hình ảnh</th>
                            <th>Vị trí</th>
                            <th>Hạn hiển thị (Bắt đầu - Kết thúc)</th>
                            <th>Trạng thái hiện tại</th>
                            <th>Trạng thái cấu hình</th>
                            <th style="width: 180px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($banners as $banner)
                            @php
                                $now = now();
                                $isUpcoming = $banner->start_date && \Carbon\Carbon::parse($banner->start_date)->gt($now);
                                $isExpired = $banner->end_date && \Carbon\Carbon::parse($banner->end_date)->lt($now);
                                $isShowing = $banner->status === 'ACTIVE' && !$isUpcoming && !$isExpired;
                            @endphp
                            <tr>
                                <td>{{ $banner->id }}</td>
                                <td>
                                    @if($banner->image_url)
                                        <img src="{{ asset('storage/' . $banner->image_url) }}" alt="{{ $banner->title }}" class="img-thumbnail" style="width: 120px; height: 60px; object-fit: cover;">
                                    @else
                                        <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center" style="width: 120px; height: 60px;">
                                            <i class="bi bi-image fs-4"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @switch($banner->position)
                                        @case('HOME_TOP')
                                            <span class="badge bg-primary">Đầu trang chủ </span>
                                            @break
                                        @case('HOME_MIDDLE')
                                            <span class="badge bg-info text-dark">Giữa trang chủ</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">{{ $banner->position }}</span>
                                    @endswitch
                                </td>
                                <td>
                                    @if($banner->start_date || $banner->end_date)
                                        <div style="font-size: 0.85rem;">
                                            <div><strong>Bắt đầu:</strong> {{ $banner->start_date ? \Carbon\Carbon::parse($banner->start_date)->format('H:i d/m/Y') : 'Không hạn chế' }}</div>
                                            <div><strong>Kết thúc:</strong> {{ $banner->end_date ? \Carbon\Carbon::parse($banner->end_date)->format('H:i d/m/Y') : 'Không hạn chế' }}</div>
                                        </div>
                                    @else
                                        <span class="text-muted" style="font-size: 0.85rem;">Hiển thị vô thời hạn</span>
                                    @endif
                                </td>
                                <td>
                                    @if($isShowing)
                                        <span class="badge bg-success"><i class="bi bi-eye-fill"></i> Đang hiển thị</span>
                                    @elseif($banner->status === 'INACTIVE')
                                        <span class="badge bg-secondary"><i class="bi bi-eye-slash-fill"></i> Đã tắt</span>
                                    @elseif($isUpcoming)
                                        <span class="badge bg-warning text-dark"><i class="bi bi-alarm-fill"></i> Chờ đến ngày</span>
                                    @elseif($isExpired)
                                        <span class="badge bg-danger"><i class="bi bi-calendar-x-fill"></i> Đã hết hạn</span>
                                    @endif
                                </td>
                                <td>
                                    @if($banner->status === 'ACTIVE')
                                        <span class="badge text-bg-success">Hoạt động</span>
                                    @else
                                        <span class="badge text-bg-secondary">Ngừng hoạt động</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.banners.edit', $banner->id) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-pencil"></i> Sửa
                                        </a>
                                        <form method="POST" action="{{ route('admin.banners.destroy', $banner->id) }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa banner này không?');" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                <i class="bi bi-trash"></i> Xóa
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="bi bi-image fs-1 d-block mb-2"></i>
                                    Không tìm thấy banner nào phù hợp.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-end mt-3">
                {{ $banners->links() }}
            </div>
        </div>
    </div>
</div>

@endsection
