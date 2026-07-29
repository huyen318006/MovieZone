@extends('layout.admin')

@section('title', 'Quản lý Membership Khách hàng')

@section('content')
<div class="container-fluid py-4">
    <!-- Title -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold text-white mb-1"><i class="bi bi-shield-check text-warning me-2"></i>Quản Lý Membership Khách Hàng</h3>
            <p class="text-muted small mb-0">Danh sách tài khoản khách hàng, mốc hạng thành viên, số dư Coin và tổng chi tiêu</p>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card bg-dark text-white border-secondary mb-4 shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.memberships.index') }}" method="GET" class="row g-3 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-secondary text-white border-secondary"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control bg-dark text-white border-secondary" placeholder="Tìm theo tên, email, số điện thoại..." value="{{ $search }}">
                    </div>
                </div>

                <div class="col-md-4">
                    <select name="level_id" class="form-select bg-dark text-white border-secondary">
                        <option value="">-- Tất cả Hạng Thành Viên --</option>
                        @foreach($levels as $lvl)
                            <option value="{{ $lvl->id }}" {{ $levelId == $lvl->id ? 'selected' : '' }}>
                                Hạng {{ $lvl->name }} (Mốc {{ number_format($lvl->min_points) }} Coin)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">
                        <i class="bi bi-funnel-fill me-1"></i> Lọc dữ liệu
                    </button>
                    @if(!empty($search) || !empty($levelId))
                        <a href="{{ route('admin.memberships.index') }}" class="btn btn-outline-light" title="Đặt lại bộ lọc">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card bg-dark text-white border-secondary shadow-sm">
        <div class="card-body p-0">
            @if(isset($customers) && $customers->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle mb-0">
                        <thead class="table-secondary text-uppercase small">
                            <tr>
                                <th class="ps-4">STT</th>
                                <th>Khách Hàng</th>
                                <th>Hạng Thành Viên</th>
                                <th>Số Dư Coin</th>
                                <th>Tổng Chi Tiêu</th>
                                <th>Ngày Đăng Ký</th>
                                <th class="text-end pe-4">Hành Động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($customers as $index => $cust)
                                @php
                                    $lvlName = strtoupper($cust->membership?->level?->name ?? 'BRONZE');
                                    $badgeClass = match($lvlName) {
                                        'SILVER' => 'bg-secondary text-white',
                                        'GOLD' => 'bg-warning text-dark',
                                        'PLATINUM' => 'bg-info text-dark',
                                        'DIAMOND' => 'bg-primary text-white',
                                        default => 'bg-dark text-warning border border-warning',
                                    };
                                @endphp
                                <tr>
                                    <td class="ps-4 text-muted fs-6">{{ $customers->firstItem() + $index }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px; font-size: 16px;">
                                                {{ strtoupper(substr($cust->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="fw-bold text-white">{{ $cust->name }}</div>
                                                <div class="small text-muted">{{ $cust->email }}</div>
                                                @if($cust->phone)
                                                    <div class="small text-warning"><i class="bi bi-telephone me-1"></i>{{ $cust->phone }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $badgeClass }} px-3 py-2 fw-bold fs-6">
                                            <i class="bi bi-gem me-1"></i> {{ $lvlName }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-warning fs-6">🪙 {{ number_format($cust->coin?->balance ?? 0) }}</span> <span class="small text-muted">Coin</span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-info fs-6">{{ number_format($cust->membership?->total_spent ?? 0) }}đ</span>
                                    </td>
                                    <td>
                                        <span class="small text-muted">{{ $cust->created_at ? $cust->created_at->format('d/m/Y') : 'N/A' }}</span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="{{ route('admin.memberships.show', $cust->id) }}" class="btn btn-sm btn-outline-info rounded-pill px-3">
                                            <i class="bi bi-eye me-1"></i> Chi tiết
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($customers->hasPages())
                    <div class="p-3 border-top border-secondary d-flex justify-content-center">
                        {{ $customers->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="bi bi-people fs-1 text-muted d-block mb-3"></i>
                    <h5 class="fw-bold text-white">Không tìm thấy khách hàng nào</h5>
                    <p class="small text-muted mb-0">Thử thay đổi từ khóa tìm kiếm hoặc bộ lọc hạng thành viên</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection