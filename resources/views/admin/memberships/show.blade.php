@extends('layout.admin')

@section('title', 'Chi tiết Membership - Admin')

@section('content')
<div class="container-fluid py-4">
    <!-- Flash Messages -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Header & Back Button -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-white mb-1">
                <i class="bi bi-person-badge text-warning me-2"></i>Chi Tiết Membership Khách Hàng
            </h3>
            <p class="text-muted small mb-0">Xem thông tin hạng, số dư coin và nhật ký giao dịch mua vé của khách hàng</p>
        </div>
        <a href="{{ \App\Helpers\TabAuthHelper::route('admin.memberships.index') }}" class="btn btn-outline-light rounded-pill px-4">
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
                            <strong class="text-white">{{ $customer->created_at ? \Illuminate\Support\Carbon::parse($customer->created_at)->format('d/m/Y') : 'N/A' }}</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block mb-1">Hạn duy trì hạng</span>
                            @if(!empty($customer->membership?->level_expired_at))
                                <strong class="text-info">{{ \Illuminate\Support\Carbon::parse($customer->membership->level_expired_at)->format('d/m/Y') }}</strong>
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

                    <div class="pt-3 border-top border-secondary d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-warning rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#adjustCoinModal">
                            <i class="bi bi-sliders me-1"></i> Điều chỉnh Coin thủ công
                        </button>
                        <button type="button" class="btn btn-outline-danger rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#resetLevelModal">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Hạng về BRONZE
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
                                        @if(!empty($b->paid_at))
                                            {{ \Illuminate\Support\Carbon::parse($b->paid_at)->format('H:i - d/m/Y') }}
                                        @elseif(!empty($b->created_at))
                                            {{ \Illuminate\Support\Carbon::parse($b->created_at)->format('H:i - d/m/Y') }}
                                        @else
                                            N/A
                                        @endif
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
    <div class="card bg-dark text-white border-secondary mb-4 shadow-sm">
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
                                        @elseif($pt->type === 'ADJUST' && $pt->points > 0)
                                            <span class="badge bg-primary">↩️ Hoàn Xu</span>
                                        @elseif($pt->type === 'ADJUST' && $pt->points < 0)
                                            <span class="badge bg-danger">🚫 Thu Hồi</span>
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

    <!-- Rank Transitions History Table Card (Commit 5.2) -->
    <div class="card bg-dark text-white border-secondary shadow-sm">
        <div class="card-header bg-secondary text-white fw-bold py-3">
            <i class="bi bi-graph-up-arrow me-2"></i>Lịch Sử Chuyển Đổi Hạng Thành Viên (Lưu Vết Thăng / Hạ Hạng)
        </div>
        <div class="card-body p-0">
            @if(isset($levelHistories) && $levelHistories->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle mb-0">
                        <thead class="table-secondary text-uppercase small">
                            <tr>
                                <th class="ps-4">Thời Gian</th>
                                <th>Hạng Cũ</th>
                                <th>Hạng Mới</th>
                                <th>Lý Do Chuyển Hạng</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($levelHistories as $lh)
                                <tr>
                                    <td class="ps-4 small text-muted">{{ $lh->created_at ? $lh->created_at->format('H:i - d/m/Y') : 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-secondary px-3 py-1 fw-bold">
                                            {{ strtoupper($lh->oldLevel?->name ?? 'BRONZE') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary px-3 py-1 fw-bold">
                                            <i class="bi bi-arrow-right me-1"></i> {{ strtoupper($lh->newLevel?->name ?? 'BRONZE') }}
                                        </span>
                                    </td>
                                    <td class="small text-white-50">
                                        {{ $lh->reason }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="bi bi-award fs-2 text-muted d-block mb-2"></i>
                    <span class="text-muted small">Khách hàng chưa có biến động thay đổi Hạng nào</span>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Điều Chỉnh Coin Thủ Công -->
<div class="modal fade" id="adjustCoinModal" tabindex="-1" aria-labelledby="adjustCoinModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold text-white" id="adjustCoinModalLabel">
                    <i class="bi bi-sliders text-warning me-2"></i>Điều Chỉnh Coin Khách Hàng
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ \App\Helpers\TabAuthHelper::route('admin.memberships.adjust_coin', $customer->id) }}" method="POST">
                @csrf
                <input type="hidden" name="tab_token" value="{{ request('tab_token') }}">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Loại hành động</label>
                        <select name="action_type" class="form-select bg-dark text-white border-secondary" required>
                            <option value="ADD">➕ Cộng Coin thưởng</option>
                            <option value="DEDUCT">➖ Trừ Coin</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Số Coin điều chỉnh</label>
                        <div class="input-group">
                            <span class="input-group-text bg-secondary border-secondary text-white">🪙</span>
                            <input type="number" name="amount" class="form-control bg-dark text-white border-secondary" min="1" placeholder="Nhập số Coin (vd: 500)..." required>
                        </div>
                        <div class="form-text text-muted">Số dư hiện tại của khách: <strong class="text-warning">{{ number_format($customer->coin?->balance ?? 0) }} Coin</strong></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Lý do điều chỉnh (Bắt buộc - Audit Log)</label>
                        <textarea name="reason" class="form-control bg-dark text-white border-secondary" rows="3" placeholder="Nhập lý do chi tiết (Ví dụ: Thưởng sự kiện, Bồi hoàn sự cố...)" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-warning fw-bold">
                        <i class="bi bi-check-circle me-1"></i> Xác Nhận Điều Chỉnh
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Reset Hạng Về BRONZE -->
<div class="modal fade" id="resetLevelModal" tabindex="-1" aria-labelledby="resetLevelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold text-white" id="resetLevelModalLabel">
                    <i class="bi bi-arrow-counterclockwise text-danger me-2"></i>Xác Nhận Reset Hạng Thành Viên
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ \App\Helpers\TabAuthHelper::route('admin.memberships.reset_level', $customer->id) }}" method="POST">
                @csrf
                <input type="hidden" name="tab_token" value="{{ request('tab_token') }}">
                <div class="modal-body">
                    <div class="alert alert-info border-0 small mb-3">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        Hành động này sẽ <strong>chuyển Hạng thành viên về BRONZE</strong>. Mặc định <strong>lịch sử mua vé & doanh thu thực tế vẫn được bảo toàn nguyên vẹn</strong>.
                    </div>
                    <p class="small text-muted mb-3">
                        Khách hàng: <strong class="text-white">{{ $customer->name }}</strong> ({{ $customer->email }})<br>
                        Hạng hiện tại: <strong class="text-warning">{{ strtoupper($customer->membership?->level?->name ?? 'BRONZE') }}</strong><br>
                        Tổng chi tiêu tích lũy: <strong class="text-info">{{ number_format($customer->membership?->total_spent ?? 0) }}đ</strong>
                    </p>
                    <div class="form-check bg-secondary bg-opacity-25 p-2 ps-4 rounded border border-secondary border-opacity-25">
                        <input class="form-check-input" type="checkbox" name="reset_spent" value="1" id="resetSpentCheck">
                        <label class="form-check-label small text-warning fw-bold" for="resetSpentCheck">
                            Tùy chọn Demo: Đặt cả Tổng chi tiêu tích lũy về 0đ
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-danger fw-bold">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Xác Nhận Reset Về BRONZE
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection