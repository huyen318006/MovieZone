@extends('layout.admin')

@section('title', 'Quản lý Membership Khách hàng')

@section('content')
<div class="container-fluid py-4 position-relative">
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

    <!-- Title & Action Buttons -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-white mb-1"><i class="bi bi-shield-check text-warning me-2"></i>Quản Lý Membership Khách Hàng</h3>
            <p class="text-muted small mb-0">Danh sách tài khoản khách hàng, mốc hạng thành viên, số dư Coin và tổng chi tiêu</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ \App\Helpers\TabAuthHelper::route('admin.membership_levels.index') }}" class="btn btn-outline-info fw-bold">
                <i class="bi bi-sliders me-1"></i> Cấu Hình Mốc Hạng
            </a>
            <form action="{{ \App\Helpers\TabAuthHelper::route('admin.memberships.scan_expired') }}" method="POST">
                @csrf
                <input type="hidden" name="tab_token" value="{{ request('tab_token') }}">
                <button type="submit" class="btn btn-outline-warning fw-bold">
                    <i class="bi bi-arrow-repeat me-1"></i> Quét Quá Hạn 6 Tháng
                </button>
            </form>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card bg-dark text-white border-secondary mb-4 shadow-sm">
        <div class="card-body">
            <form action="{{ \App\Helpers\TabAuthHelper::route('admin.memberships.index') }}" method="GET" class="row g-3 align-items-center">
                <input type="hidden" name="tab_token" value="{{ request('tab_token') }}">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-secondary text-white border-secondary"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control bg-dark text-white border-secondary" placeholder="Tìm theo tên, email, số điện thoại..." value="{{ $search }}">
                    </div>
                </div>

                <div class="col-md-4">
                    <select name="level_id" class="form-select bg-dark text-white border-secondary" onchange="this.form.submit()">
                        <option value="">-- Tất cả Hạng Thành Viên --</option>
                        @foreach($levels as $lvl)
                            <option value="{{ $lvl->id }}" {{ $levelId == $lvl->id ? 'selected' : '' }}>
                                Hạng {{ $lvl->name }} (Mốc {{ number_format($lvl->min_points) }}đ)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">
                        <i class="bi bi-funnel-fill me-1"></i> Lọc dữ liệu
                    </button>
                    @if(!empty($search) || !empty($levelId))
                        <a href="{{ \App\Helpers\TabAuthHelper::route('admin.memberships.index') }}" class="btn btn-outline-light" title="Đặt lại bộ lọc">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Floating Bulk Action Bar -->
    <div id="bulkActionBar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 p-3 bg-dark border border-warning rounded-4 shadow-lg d-none align-items-center gap-3" style="z-index: 1050; min-width: 600px;">
        <div class="text-white fw-bold">
            <i class="bi bi-check-square-fill text-warning me-2"></i>Đã chọn <span id="selectedCount" class="text-warning fs-5">0</span> khách hàng
        </div>
        <div class="d-flex gap-2 ms-auto">
            <button type="button" class="btn btn-sm btn-info text-dark fw-bold px-3" data-bs-toggle="modal" data-bs-target="#bulkChangeLevelModal">
                <i class="bi bi-shield-shaded me-1"></i> Đổi Hạng Hàng Loạt
            </button>
            <button type="button" class="btn btn-sm btn-warning fw-bold px-3" data-bs-toggle="modal" data-bs-target="#bulkAdjustCoinModal">
                <i class="bi bi-plus-slash-minus me-1"></i> Chỉnh Coin Hàng Loạt
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger fw-bold px-3" data-bs-toggle="modal" data-bs-target="#bulkResetLevelModal">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Hạng Hàng Loạt
            </button>
            <button type="button" id="clearSelection" class="btn btn-sm btn-outline-light" title="Bỏ chọn">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card bg-dark text-white border-secondary shadow-sm">
        <div class="card-body p-0">
            @if(isset($customers) && $customers->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle mb-0">
                        <thead class="bg-secondary text-uppercase small">
                            <tr>
                                <th class="ps-4" style="width: 40px;">
                                    <input type="checkbox" id="selectAllCust" class="form-check-input border-secondary">
                                </th>
                                <th style="width: 50px;">STT</th>
                                <th>Khách Hàng</th>
                                <th>Hạng Thành Viên</th>
                                <th>Số Dư Coin</th>
                                <th>Tổng Chi Tiêu</th>
                                <th>Ngày Đăng Ký</th>
                                <th class="text-end pe-4">Thao Tác Nhanh</th>
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
                                    <td class="ps-4">
                                        <input type="checkbox" class="form-check-input cust-checkbox border-secondary" value="{{ $cust->id }}" data-name="{{ $cust->name }}">
                                    </td>
                                    <td class="text-muted fs-6">{{ $customers->firstItem() + $index }}</td>
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
                                        <div class="d-flex align-items-center justify-content-end gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-info rounded-circle p-2" style="width: 34px; height: 34px; line-height: 1;" title="Đổi Hạng trực tiếp" data-bs-toggle="modal" data-bs-target="#quickChangeLevelModal{{ $cust->id }}">
                                                <i class="bi bi-shield-shaded"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-warning rounded-circle p-2" style="width: 34px; height: 34px; line-height: 1;" title="Chỉnh Coin nhanh" data-bs-toggle="modal" data-bs-target="#quickCoinModal{{ $cust->id }}">
                                                <i class="bi bi-coin"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger rounded-circle p-2" style="width: 34px; height: 34px; line-height: 1;" title="Reset Hạng nhanh" data-bs-toggle="modal" data-bs-target="#quickResetModal{{ $cust->id }}">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                            <a href="{{ \App\Helpers\TabAuthHelper::route('admin.memberships.show', $cust->id) }}" class="btn btn-sm btn-outline-info rounded-pill px-3 ms-1">
                                                <i class="bi bi-eye me-1"></i> Chi tiết
                                            </a>
                                        </div>

                                        <!-- Modal Quick Change Level -->
                                        <div class="modal fade text-start" id="quickChangeLevelModal{{ $cust->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content bg-dark text-white border-secondary">
                                                    <div class="modal-header border-secondary">
                                                        <h5 class="modal-title fw-bold text-info"><i class="bi bi-shield-shaded me-2"></i>Đổi Hạng Thẻ cho {{ $cust->name }}</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="{{ \App\Helpers\TabAuthHelper::route('admin.memberships.change_level', $cust->id) }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="tab_token" value="{{ request('tab_token') }}">
                                                        <div class="modal-body">
                                                            <p class="small text-muted mb-3">Hạng hiện tại: <span class="badge {{ $badgeClass }} px-2 py-1">{{ $lvlName }}</span></p>
                                                            <div class="mb-3">
                                                                <label class="form-label text-white-50">Chọn Hạng Thành Viên Mới</label>
                                                                <select name="level_id" class="form-select bg-dark text-white border-secondary" required>
                                                                    @foreach($levels as $lvlOption)
                                                                        <option value="{{ $lvlOption->id }}" {{ $cust->membership?->level_id == $lvlOption->id ? 'selected' : '' }}>
                                                                            Hạng {{ $lvlOption->name }} (Ưu đãi {{ $lvlOption->discount_percent }}%)
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label text-white-50">Lý do điều chỉnh Hạng (Bắt buộc ghi Audit Log)</label>
                                                                <input type="text" name="reason" class="form-control bg-dark text-white border-secondary" required placeholder="Nhập lý do nâng/hạ hạng trực tiếp...">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-secondary">
                                                            <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Hủy</button>
                                                            <button type="submit" class="btn btn-info text-dark fw-bold">Xác nhận Đổi Hạng</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Modal Quick Adjust Coin -->
                                        <div class="modal fade text-start" id="quickCoinModal{{ $cust->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content bg-dark text-white border-secondary">
                                                    <div class="modal-header border-secondary">
                                                        <h5 class="modal-title fw-bold text-warning"><i class="bi bi-coin me-2"></i>Chỉnh Coin cho {{ $cust->name }}</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="{{ \App\Helpers\TabAuthHelper::route('admin.memberships.adjust_coin', $cust->id) }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="tab_token" value="{{ request('tab_token') }}">
                                                        <div class="modal-body">
                                                            <p class="small text-muted mb-3">Số dư hiện tại: <strong class="text-warning">{{ number_format($cust->coin?->balance ?? 0) }} Coin</strong></p>
                                                            <div class="mb-3">
                                                                <label class="form-label text-white-50">Loại thao tác</label>
                                                                <select name="action_type" class="form-select bg-dark text-white border-secondary" required>
                                                                    <option value="ADD">➕ Cộng Coin vào ví</option>
                                                                    <option value="DEDUCT">➖ Trừ Coin từ ví</option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label text-white-50">Số Coin</label>
                                                                <input type="number" name="amount" class="form-control bg-dark text-white border-secondary" min="1" required placeholder="Nhập số Coin (vd: 100)">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label text-white-50">Lý do điều chỉnh (Bắt buộc)</label>
                                                                <input type="text" name="reason" class="form-control bg-dark text-white border-secondary" required placeholder="Nêu lý do ghi nhận Audit Log...">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-secondary">
                                                            <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Hủy</button>
                                                            <button type="submit" class="btn btn-warning fw-bold">Xác nhận</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Modal Quick Reset Level -->
                                        <div class="modal fade text-start" id="quickResetModal{{ $cust->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content bg-dark text-white border-secondary">
                                                    <div class="modal-header border-secondary">
                                                        <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Reset Hạng cho {{ $cust->name }}</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="{{ \App\Helpers\TabAuthHelper::route('admin.memberships.reset_level', $cust->id) }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="tab_token" value="{{ request('tab_token') }}">
                                                        <div class="modal-body">
                                                            <p class="text-white mb-2">Bạn có chắc chắn muốn đưa Hạng thành viên của <strong>{{ $cust->name }}</strong> về hạng <strong class="text-warning">BRONZE</strong>?</p>
                                                            <div class="form-check p-3 bg-secondary bg-opacity-25 rounded-3 border border-secondary">
                                                                <input class="form-check-input" type="checkbox" name="reset_spent" value="1" id="resetSpentQuick{{ $cust->id }}">
                                                                <label class="form-check-label text-white small" for="resetSpentQuick{{ $cust->id }}">
                                                                    <strong>Tùy chọn Demo:</strong> Đặt cả Tổng chi tiêu tích lũy (`total_spent`) về 0đ.
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-secondary">
                                                            <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Hủy</button>
                                                            <button type="submit" class="btn btn-danger fw-bold">Xác nhận Reset</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
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

<!-- Modal Bulk Change Level -->
<div class="modal fade" id="bulkChangeLevelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold text-info"><i class="bi bi-shield-shaded me-2"></i>Đổi Hạng Thẻ Hàng Loạt</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="bulkChangeLevelForm" action="{{ \App\Helpers\TabAuthHelper::route('admin.memberships.bulk_change_level') }}" method="POST">
                @csrf
                <input type="hidden" name="tab_token" value="{{ request('tab_token') }}">
                <div id="bulkChangeUserInputs"></div>
                <div class="modal-body">
                    <div class="alert alert-info border-0 small mb-3 text-dark">
                        <i class="bi bi-info-circle me-1"></i> Đang áp dụng cho <strong id="bulkChangeCustCount">0</strong> khách hàng đã chọn.
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white-50">Chọn Hạng Thành Viên Mới</label>
                        <select name="level_id" class="form-select bg-dark text-white border-secondary" required>
                            @foreach($levels as $lvlOption)
                                <option value="{{ $lvlOption->id }}">
                                    Hạng {{ $lvlOption->name }} (Ưu đãi {{ $lvlOption->discount_percent }}%)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white-50">Lý do điều chỉnh Hạng (Bắt buộc ghi Audit Log)</label>
                        <input type="text" name="reason" class="form-control bg-dark text-white border-secondary" required placeholder="Nhập lý do đổi hạng hàng loạt...">
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-info text-dark fw-bold">Xác nhận Đổi Hạng Hàng Loạt</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Bulk Adjust Coin -->
<div class="modal fade" id="bulkAdjustCoinModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold text-warning"><i class="bi bi-coin me-2"></i>Điều Chỉnh Coin Hàng Loạt</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="bulkCoinForm" action="{{ \App\Helpers\TabAuthHelper::route('admin.memberships.bulk_adjust_coin') }}" method="POST">
                @csrf
                <input type="hidden" name="tab_token" value="{{ request('tab_token') }}">
                <div id="bulkCoinUserInputs"></div>
                <div class="modal-body">
                    <div class="alert alert-warning border-0 small mb-3">
                        <i class="bi bi-info-circle me-1"></i> Đang áp dụng cho <strong id="bulkCoinCustCount">0</strong> khách hàng đã chọn.
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white-50">Loại thao tác</label>
                        <select name="action_type" class="form-select bg-dark text-white border-secondary" required>
                            <option value="ADD">➕ Cộng Coin vào ví</option>
                            <option value="DEDUCT">➖ Trừ Coin từ ví</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white-50">Số Coin điều chỉnh</label>
                        <input type="number" name="amount" class="form-control bg-dark text-white border-secondary" min="1" required placeholder="Nhập số Coin (vd: 50)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white-50">Lý do điều chỉnh (Bắt buộc ghi Audit Log)</label>
                        <input type="text" name="reason" class="form-control bg-dark text-white border-secondary" required placeholder="Nêu lý do cộng/trừ hàng loạt...">
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-warning fw-bold">Xác nhận Thao tác Hàng loạt</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Bulk Reset Level -->
<div class="modal fade" id="bulkResetLevelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Reset Hạng Hàng Loạt về BRONZE</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="bulkResetForm" action="{{ \App\Helpers\TabAuthHelper::route('admin.memberships.bulk_reset_level') }}" method="POST">
                @csrf
                <input type="hidden" name="tab_token" value="{{ request('tab_token') }}">
                <div id="bulkResetUserInputs"></div>
                <div class="modal-body">
                    <p class="text-white mb-2">Bạn có chắc chắn muốn đưa Hạng thành viên của <strong id="bulkResetCustCount" class="text-warning">0</strong> khách hàng đã chọn về <strong class="text-danger">BRONZE</strong>?</p>
                    <div class="form-check p-3 bg-secondary bg-opacity-25 rounded-3 border border-secondary mt-3">
                        <input class="form-check-input" type="checkbox" name="reset_spent" value="1" id="bulkResetSpent">
                        <label class="form-check-label text-white small" for="bulkResetSpent">
                            <strong>Tùy chọn Demo:</strong> Đặt cả Tổng chi tiêu tích lũy (`total_spent`) về 0đ.
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger fw-bold">Xác nhận Reset Hàng Loạt</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const selectAll = document.getElementById('selectAllCust');
    const custCheckboxes = document.querySelectorAll('.cust-checkbox');
    const bulkActionBar = document.getElementById('bulkActionBar');
    const selectedCountSpan = document.getElementById('selectedCount');
    const clearSelectionBtn = document.getElementById('clearSelection');

    const bulkCoinUserInputs = document.getElementById('bulkCoinUserInputs');
    const bulkCoinCustCount = document.getElementById('bulkCoinCustCount');
    const bulkResetUserInputs = document.getElementById('bulkResetUserInputs');
    const bulkResetCustCount = document.getElementById('bulkResetCustCount');
    const bulkChangeUserInputs = document.getElementById('bulkChangeUserInputs');
    const bulkChangeCustCount = document.getElementById('bulkChangeCustCount');

    function updateBulkBar() {
        const checked = document.querySelectorAll('.cust-checkbox:checked');
        const count = checked.length;

        if (count > 0) {
            selectedCountSpan.textContent = count;
            bulkActionBar.classList.remove('d-none');
            bulkActionBar.classList.add('d-flex');
        } else {
            bulkActionBar.classList.remove('d-flex');
            bulkActionBar.classList.add('d-none');
        }

        // Sync hidden inputs for forms
        if (bulkCoinUserInputs) {
            bulkCoinUserInputs.innerHTML = '';
            checked.forEach(cb => {
                bulkCoinUserInputs.innerHTML += `<input type="hidden" name="user_ids[]" value="${cb.value}">`;
            });
            if (bulkCoinCustCount) bulkCoinCustCount.textContent = count;
        }

        if (bulkResetUserInputs) {
            bulkResetUserInputs.innerHTML = '';
            checked.forEach(cb => {
                bulkResetUserInputs.innerHTML += `<input type="hidden" name="user_ids[]" value="${cb.value}">`;
            });
            if (bulkResetCustCount) bulkResetCustCount.textContent = count;
        }

        if (bulkChangeUserInputs) {
            bulkChangeUserInputs.innerHTML = '';
            checked.forEach(cb => {
                bulkChangeUserInputs.innerHTML += `<input type="hidden" name="user_ids[]" value="${cb.value}">`;
            });
            if (bulkChangeCustCount) bulkChangeCustCount.textContent = count;
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', () => {
            custCheckboxes.forEach(cb => cb.checked = selectAll.checked);
            updateBulkBar();
        });
    }

    custCheckboxes.forEach(cb => {
        cb.addEventListener('change', () => {
            if (!cb.checked && selectAll) selectAll.checked = false;
            updateBulkBar();
        });
    });

    if (clearSelectionBtn) {
        clearSelectionBtn.addEventListener('click', () => {
            if (selectAll) selectAll.checked = false;
            custCheckboxes.forEach(cb => cb.checked = false);
            updateBulkBar();
        });
    }
});
</script>
@endpush
@endsection
