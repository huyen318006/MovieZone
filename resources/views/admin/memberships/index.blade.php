@extends('layout.admin')

@section('title', 'Quản lý Membership Khách hàng')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    .admin-membership-box {
        background: #1e293b !important;
        border: 1px solid #334155 !important;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4) !important;
    }

    .table-admin {
        width: 100% !important;
        border-collapse: collapse !important;
        color: #f8fafc !important;
    }

    .table-admin th {
        background: #0f172a !important;
        color: #cbd5e1 !important;
        border-bottom: 2px solid #334155 !important;
        padding: 16px !important;
        font-weight: 700;
    }

    .table-admin td {
        background: #1e293b !important;
        color: #e2e8f0 !important;
        border-bottom: 1px solid #334155 !important;
        padding: 16px !important;
        vertical-align: middle;
    }

    .table-admin tr:hover td {
        background: #2d3748 !important;
    }

    .badge-bronze { background: #78350f; color: #fef3c7; border: 1px solid #92400e; }
    .badge-silver { background: #475569; color: #f8fafc; border: 1px solid #64748b; }
    .badge-gold { background: #b45309; color: #fffbeb; border: 1px solid #d97706; }
    .badge-platinum { background: #0284c7; color: #f0f9ff; border: 1px solid #38bdf8; }
    .badge-diamond { background: #7c3aed; color: #faf5ff; border: 1px solid #c084fc; }

    .search-filter-card {
        background: #0f172a;
        border: 1px solid #334155;
        border-radius: 16px;
        padding: 20px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1 text-white"><i class="bi bi-shield-check text-warning me-2"></i>Quản Lý Membership Khách Hàng</h2>
            <p class="mb-0 text-white-50">Danh sách tài khoản khách hàng, mốc hạng thành viên, số dư Coin và tổng chi tiêu</p>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="search-filter-card mb-4">
        <form action="{{ route('admin.memberships.index') }}" method="GET" class="row g-3 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-white-50"><i class="bi bi-search"></i></span>
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
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold w-100">
                    <i class="bi bi-filter me-1"></i> Lọc dữ liệu
                </button>
                @if(!empty($search) || !empty($levelId))
                    <a href="{{ route('admin.memberships.index') }}" class="btn btn-outline-light rounded-pill px-3" title="Xóa bộ lọc">
                        <i class="bi bi-x-circle"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Customer Table Card -->
    <div class="admin-membership-box">
        @if(isset($customers) && $customers->isNotEmpty())
            <div class="table-responsive">
                <table class="table-admin mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Khách Hàng</th>
                            <th>Hạng Thành Viên</th>
                            <th>Số Dư Coin</th>
                            <th>Tổng Chi Tiêu</th>
                            <th>Ngày Đăng Ký</th>
                            <th class="text-end">Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customers as $index => $cust)
                            @php
                                $lvlName = strtoupper($cust->membership?->level?->name ?? 'BRONZE');
                                $badgeClass = match($lvlName) {
                                    'SILVER' => 'badge-silver',
                                    'GOLD' => 'badge-gold',
                                    'PLATINUM' => 'badge-platinum',
                                    'DIAMOND' => 'badge-diamond',
                                    default => 'badge-bronze',
                                };
                            @endphp
                            <tr>
                                <td>{{ $customers->firstItem() + $index }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar-circle bg-secondary text-white rounded-circle d-grid place-items-center fw-bold" style="width: 40px; height: 40px; font-size: 16px; display: flex; align-items: center; justify-content: center;">
                                            {{ strtoupper(substr($cust->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="fw-bold text-white">{{ $cust->name }}</div>
                                            <div class="small text-white-50">{{ $cust->email }}</div>
                                            @if($cust->phone)
                                                <div class="small text-warning"><i class="bi bi-telephone me-1"></i>{{ $cust->phone }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge {{ $badgeClass }} px-3 py-2 rounded-pill fw-bold">
                                        <i class="bi bi-gem me-1"></i> {{ $lvlName }}
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold text-warning fs-6">🪙 {{ number_format($cust->coin?->balance ?? 0) }}</span> Coin
                                </td>
                                <td>
                                    <span class="fw-bold text-info">{{ number_format($cust->membership?->total_spent ?? 0) }}đ</span>
                                </td>
                                <td>
                                    <span class="small text-white-50">{{ $cust->created_at ? $cust->created_at->format('d/m/Y') : 'N/A' }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="#" class="btn btn-sm btn-outline-info rounded-pill px-3 fw-bold">
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
                <div class="mt-4 d-flex justify-content-center">
                    {{ $customers->links('pagination::bootstrap-5') }}
                </div>
            @endif
        @else
            <div class="text-center py-5">
                <i class="bi bi-people fs-1 text-white-50 d-block mb-3"></i>
                <h5 class="fw-bold text-white">Không tìm thấy khách hàng nào</h5>
                <p class="small text-white-50 mb-0">Thử thay đổi từ khóa tìm kiếm hoặc bộ lọc hạng thành viên</p>
            </div>
        @endif
    </div>
</div>
@endsection