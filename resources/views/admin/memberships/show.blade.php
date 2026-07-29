@extends('layout.admin')

@section('title', 'Chi tiết Membership - Admin')

@section('content')
<div class="container-fluid py-4">
    <!-- Header & Back Button -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-white mb-1">
                <i class="bi bi-person-badge text-warning me-2"></i>Chi Tiết Membership Khách Hàng
            </h3>
            <p class="text-muted small mb-0">Xem thông tin hạng, số dư coin và nhật ký giao dịch mua vé của khách hàng</p>
        </div>
        <a href="{{ route('admin.memberships.index') }}" class="btn btn-outline-light rounded-pill px-4">
            <i class="bi bi-arrow-left me-1"></i> Quay lại danh sách
        </a>
    </div>

    @php
        $lvlName = strtoupper($customer->membership?->level?->name ?? 'BRONZE');
        $badgeClass = match($lvlName) {
            'SILVER' => 'bg-secondary text-white',
            'GOLD' => 'bg-warning text-dark',
            'PLATINUM' => 'bg-info text-dark',
            'DIAMOND' => 'bg-primary text-white',
            default => 'bg-dark text-warning border border-warning',
        };
    @endphp

    <!-- Customer Overview Cards Row -->
    <div class="row g-4 mb-4">
        <!-- Customer Profile Card -->
        <div class="col-lg-6">
            <div class="card bg-dark text-white border-secondary h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-4" style="width: 54px; height: 54px;">
                            {{ strtoupper(substr($customer->name, 0, 1)) }}
                        </div>
                        <div>
                            <h4 class="fw-bold text-white mb-1">{{ $customer->name }}</h4>
                            <div class="text-muted small mb-1"><i class="bi bi-envelope me-1"></i>{{ $customer->email }}</div>
                            @if($customer->phone)
                                <div class="text-warning small"><i class="bi bi-telephone me-1"></i>{{ $customer->phone }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="row g-3 pt-3 border-top border-secondary small">
                        <div class="col-6">
                            <span class="text-muted d-block mb-1">Ngày đăng ký</span>
                            <strong class="text-white">{{ $customer->created_at ? $customer->created_at->format('d/m/Y') : 'N/A' }}</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block mb-1">Hạn duy trì hạng</span>
                            @if(!empty($customer->membership?->level_expired_at))
                                <strong class="text-info">{{ $customer->membership->level_expired_at->format('d/m/Y') }}</strong>
                            @else
                                <strong class="text-white">Thành viên chính thức</strong>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rank & Coin Balance Card -->
        <div class="col-lg-6">
            <div class="card bg-dark text-white border-secondary h-100 shadow-sm">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-muted text-uppercase small fw-bold">Trạng thái Hạng</span>
                        <span class="badge {{ $badgeClass }} px-3 py-2 fw-bold fs-6">
                            <i class="bi bi-gem me-1"></i> HẠNG {{ $lvlName }}
                        </span>
                    </div>

                    <div class="row g-3 my-auto">
                        <div class="col-6">
                            <span class="text-muted small d-block mb-1">Số dư Coin tích lũy</span>
                            <div class="fs-3 fw-bold text-warning">
                                🪙 {{ number_format($customer->coin?->balance ?? 0) }}
                            </div>
                        </div>
                        <div class="col-6">
                            <span class="text-muted small d-block mb-1">Tổng chi tiêu mua vé</span>
                            <div class="fs-3 fw-bold text-info">
                                {{ number_format($customer->membership?->total_spent ?? 0) }}đ
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-top border-secondary d-flex justify-content-end">
                        <button type="button" class="btn btn-warning rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#adjustCoinModal">
                            <i class="bi bi-sliders me-1"></i> Điều chỉnh Coin thủ công
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Spending History Table Card (Paid Bookings) -->
    <div class="card bg-dark text-white border-secondary mb-4 shadow-sm">
        <div class="card-header bg-secondary text-white fw-bold d-flex align-items-center justify-content-between py-3">
            <span><i class="bi bi-ticket-detailed me-2"></i>Lịch Sử Chi Tiêu Mua Vé (Đã Thanh Toán)</span>
            <span class="badge bg-info text-dark fw-bold">{{ $bookings->total() }} Đơn hàng</span>
        </div>
        <div class="card-body p-0">
            @if(isset($bookings) && $bookings->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle mb-0">
                        <thead class="table-secondary text-uppercase small">
                            <tr>
                                <th class="ps-4">Mã Đơn</th>
                                <th>Tổng Tiền</th>
                                <th>Phương Thức</th>
                                <th>Trạng Thái</th>
                                <th>Thời Gian Thanh Toán</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bookings as $b)
                                <tr>
                                    <td class="ps-4 font-monospace fw-bold text-warning">#{{ $b->booking_code }}</td>
                                    <td class="fw-bold text-info">{{ number_format($b->final_amount ?? $b->total_price) }}đ</td>
                                    <td>
                                        <span class="badge bg-outline-light border px-2 py-1">{{ $b->payment_method ?? 'ONLINE' }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Đã thanh toán</span>
                                    </td>
                                    <td class="small text-muted">
                                        {{ $b->paid_at ? $b->paid_at->format('H:i - d/m/Y') : ($b->created_at ? $b->created_at->format('H:i - d/m/Y') : 'N/A') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($bookings->hasPages())
                    <div class="p-3 border-top border-secondary d-flex justify-content-center">
                        {{ $bookings->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            @else
                <div class="text-center py-4">
                    <i class="bi bi-inbox fs-2 text-muted d-block mb-2"></i>
                    <span class="text-muted small">Khách hàng chưa có lịch sử mua vé thành công nào</span>
                </div>
            @endif
        </div>
    </div>

    <!-- Point Transactions Table Card -->
    <div class="card bg-dark text-white border-secondary shadow-sm">
        <div class="card-header bg-secondary text-white fw-bold py-3">
            <i class="bi bi-clock-history me-2"></i>Lịch Sử Biến Động Coin (Nhật Ký Tích / Trừ Point)
        </div>
        <div class="card-body p-0">
            @if(isset($pointTransactions) && $pointTransactions->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle mb-0">
                        <thead class="table-secondary text-uppercase small">
                            <tr>
                                <th class="ps-4">Thời Gian</th>
                                <th>Loại</th>
                                <th>Mã Booking Liên Quan</th>
                                <th class="text-end pe-4">Số Coin Thay Đổi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pointTransactions as $pt)
                                <tr>
                                    <td class="ps-4 small text-muted">{{ $pt->created_at ? $pt->created_at->format('H:i - d/m/Y') : 'N/A' }}</td>
                                    <td>
                                        @if($pt->type === 'EARN')
                                            <span class="badge bg-success">+ Cộng Coin</span>
                                        @elseif($pt->type === 'REDEEM')
                                            <span class="badge bg-warning text-dark">- Sử dụng</span>
                                        @else
                                            <span class="badge bg-info text-dark">⚙️ Điều chỉnh</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($pt->booking_id)
                                            <span class="font-monospace text-warning">#{{ $pt->booking?->booking_code ?? $pt->booking_id }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4 fw-bold">
                                        @if($pt->points > 0)
                                            <span class="text-success">+{{ number_format($pt->points) }} Coin</span>
                                        @else
                                            <span class="text-danger">{{ number_format($pt->points) }} Coin</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="bi bi-journal-x fs-2 text-muted d-block mb-2"></i>
                    <span class="text-muted small">Khách hàng chưa có lịch sử biến động Coin nào</span>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection