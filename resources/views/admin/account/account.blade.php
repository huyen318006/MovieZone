@extends('layout.admin')

@section('title', 'Quản lý tài khoản')

@section('content')

<style>
    /* ===== Dark mode overrides ===== */
    .stat-card       { background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.08); }
    .stat-icon-admin { background: rgba(220,53,69,.15); }
    .stat-icon-staff { background: rgba(255,193,7,.12); }
    .stat-icon-cust  { background: rgba(13,110,253,.15); }

    .account-card    { background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.08); }
    .account-card .card-header { background: rgba(255,255,255,.03); border-bottom: 1px solid rgba(255,255,255,.08); }
    .account-card .card-footer { background: rgba(255,255,255,.03); border-top:    1px solid rgba(255,255,255,.08); }

    /* Tab nav */
    .dark-tabs .nav-link              { color: rgba(255,255,255,.5); border: none; background: transparent; }
    .dark-tabs .nav-link:hover        { color: rgba(255,255,255,.85); background: rgba(255,255,255,.06); border-radius: 8px 8px 0 0; }
    .dark-tabs .nav-link.active       { background: rgba(255,255,255,.08); border-bottom: 2px solid currentColor; border-radius: 8px 8px 0 0; }
    .dark-tabs .tab-admin.active      { color: #f87171; border-bottom-color: #f87171; }
    .dark-tabs .tab-staff.active      { color: #fbbf24; border-bottom-color: #fbbf24; }
    .dark-tabs .tab-customer.active   { color: #60a5fa; border-bottom-color: #60a5fa; }

    /* Filter area */
    .filter-area { background: rgba(255,255,255,.03); border-bottom: 1px solid rgba(255,255,255,.08); }
    .filter-area .input-group-text {
        background: rgba(255,255,255,.06);
        border-color: rgba(255,255,255,.15);
        color: rgba(255,255,255,.5);
    }
    .filter-area .form-control,
    .filter-area .form-select {
        background: rgba(255,255,255,.06);
        border-color: rgba(255,255,255,.15);
        color: rgba(255,255,255,.85);
    }
    .filter-area .form-control::placeholder { color: rgba(255,255,255,.3); }
    .filter-area .form-control:focus,
    .filter-area .form-select:focus {
        background: rgba(255,255,255,.09);
        border-color: rgba(255,255,255,.35);
        box-shadow: none;
        color: #fff;
    }
    /* Fix màu dropdown options trong dark mode */
    .filter-area .form-select option {
        background: #1e2433;
        color: #e2e8f0;
    }

    /* Table */
    .dark-table                  { background: transparent !important; }
    .dark-table thead th         { background: rgba(255,255,255,.06) !important; color: rgba(255,255,255,.5); border-bottom: 1px solid rgba(255,255,255,.08); }
    .dark-table tbody tr         { border-bottom: 1px solid rgba(255,255,255,.06); }
    .dark-table tbody tr:hover   { background: rgba(255,255,255,.04) !important; }
    .dark-table td               { background: transparent !important; color: inherit; }

    /* Badge soft */
    .badge-admin    { background: rgba(220,53,69,.18);  color: #f87171; }
    .badge-staff    { background: rgba(255,193,7,.15);  color: #fbbf24; }
    .badge-customer { background: rgba(13,110,253,.18); color: #60a5fa; }
    .badge-active   { background: rgba(25,135,84,.2);   color: #4ade80; }
    .badge-locked   { background: rgba(108,117,125,.2); color: #94a3b8; }

    .detail-btn { background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1); color: #93c5fd; }
    .detail-btn:hover { background: rgba(255,255,255,.12); color: #bfdbfe; }
</style>

<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1 fw-bold">Quản lý người dùng</h3>
            <p class="text-muted mb-0">Phân nhóm và quản lý tài khoản hệ thống</p>
        </div>
        <a href="{{ route('admin.create_account') }}" class="btn btn-success rounded-pill px-4 shadow-sm">
            <i class="fas fa-user-plus me-2"></i>Thêm tài khoản
        </a>
    </div>

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card rounded-4 p-3 d-flex align-items-center gap-3">
                <div class="stat-icon-admin rounded-3 d-flex align-items-center justify-content-center" style="width:52px;height:52px;flex-shrink:0;">
                    <i class="fas fa-shield-alt text-danger fs-5"></i>
                </div>
                <div>
                    <p class="text-muted small mb-0 text-uppercase fw-bold">Admin</p>
                    <h3 class="mb-0 fw-bold">{{ $countAdmin }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card rounded-4 p-3 d-flex align-items-center gap-3">
                <div class="stat-icon-staff rounded-3 d-flex align-items-center justify-content-center" style="width:52px;height:52px;flex-shrink:0;">
                    <i class="fas fa-user-tie text-warning fs-5"></i>
                </div>
                <div>
                    <p class="text-muted small mb-0 text-uppercase fw-bold">Staff</p>
                    <h3 class="mb-0 fw-bold">{{ $countStaff }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card rounded-4 p-3 d-flex align-items-center gap-3">
                <div class="stat-icon-cust rounded-3 d-flex align-items-center justify-content-center" style="width:52px;height:52px;flex-shrink:0;">
                    <i class="fas fa-users text-primary fs-5"></i>
                </div>
                <div>
                    <p class="text-muted small mb-0 text-uppercase fw-bold">Customer</p>
                    <h3 class="mb-0 fw-bold">{{ $countCustomer }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card rounded-4 p-3 d-flex align-items-center gap-3">
                <div class="stat-icon-locked rounded-3 d-flex align-items-center justify-content-center" style="width:52px;height:52px;flex-shrink:0;">
                    <i class="fas fa-lock text-secondary fs-5"></i>
                </div>
                <div>
                    <p class="text-muted small mb-0 text-uppercase fw-bold">Đã khóa</p>
                    <h3 class="mb-0 fw-bold">{{ $countLocked }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="account-card rounded-4 overflow-hidden">
        {{-- Tabs --}}
  {{-- Tabs --}}
<div class="card-header px-4 pt-3 pb-0">
    <ul class="nav dark-tabs gap-1">
        <li class="nav-item">
            <a href="{{ route('admin.list_account', array_merge(request()->except(['tab','page']), ['tab'=>'all'])) }}"
               class="nav-link px-4 py-2 fw-semibold {{ $tab=='all' ? 'active' : '' }}">
                <i class="fas fa-list me-2"></i>Tất cả
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.list_account', array_merge(request()->except(['tab','page']), ['tab'=>'admin'])) }}"
               class="nav-link px-4 py-2 fw-semibold tab-admin {{ $tab=='admin' ? 'active' : '' }}">
                <i class="fas fa-shield-alt me-2"></i>Admin
                <span class="badge rounded-pill ms-1 {{ $tab=='admin' ? 'badge-admin' : 'bg-secondary bg-opacity-50' }}">{{ $countAdmin }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.list_account', array_merge(request()->except(['tab','page']), ['tab'=>'staff'])) }}"
               class="nav-link px-4 py-2 fw-semibold tab-staff {{ $tab=='staff' ? 'active' : '' }}">
                <i class="fas fa-user-tie me-2"></i>Staff
                <span class="badge rounded-pill ms-1 {{ $tab=='staff' ? 'badge-staff' : 'bg-secondary bg-opacity-50' }}">{{ $countStaff }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.list_account', array_merge(request()->except(['tab','page']), ['tab'=>'customer'])) }}"
               class="nav-link px-4 py-2 fw-semibold tab-customer {{ $tab=='customer' ? 'active' : '' }}">
                <i class="fas fa-users me-2"></i>Customer
                <span class="badge rounded-pill ms-1 {{ $tab=='customer' ? 'badge-customer' : 'bg-secondary bg-opacity-50' }}">{{ $countCustomer }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.list_account', array_merge(request()->except(['tab','page']), ['tab'=>'locked'])) }}"
               class="nav-link px-4 py-2 fw-semibold tab-locked {{ $tab=='locked' ? 'active' : '' }}">
                <i class="fas fa-lock me-2"></i>Đã khóa
                <span class="badge rounded-pill ms-1 {{ $tab=='locked' ? 'badge-locked' : 'bg-secondary bg-opacity-50' }}">{{ $countLocked }}</span>
            </a>
        </li>
    </ul>
</div>

        {{-- Bộ lọc --}}
        <div class="filter-area px-4 py-3">
            <form action="{{ route('admin.list_account') }}" method="GET">
                <input type="hidden" name="tab" value="{{ $tab }}">

                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label text-muted small fw-bold text-uppercase">Tìm kiếm theo email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" name="email" class="form-control" placeholder="Nhập email cần tìm..." value="{{ request('email') }}">
                        </div>
                    </div>

                    @if($tab === 'locked')
                    <div class="col-md-4">
                        <label class="form-label text-muted small fw-bold text-uppercase">Lọc theo quyền</label>
                        <select name="locked_role" class="form-select">
                            <option value="">Tất cả quyền</option>
                            <option value="admin" {{ request('locked_role')=='admin'?'selected':'' }}>Admin</option>
                            <option value="staff" {{ request('locked_role')=='staff'?'selected':'' }}>Staff</option>
                            <option value="customer" {{ request('locked_role')=='customer'?'selected':'' }}>Customer</option>
                        </select>
                    </div>
                   
                    @endif

                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fas fa-filter me-2"></i>Lọc
                        </button>
                        <a href="{{ route('admin.list_account', ['tab'=>$tab]) }}" class="btn btn-secondary flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-undo me-1"></i>Làm mới
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- Bảng dữ liệu --}}
        <div class="table-responsive">
            <table class="table dark-table align-middle text-center mb-0">
                <thead>
                    <tr>
                        <th class="py-3 px-4 text-start fw-semibold small text-uppercase">Người dùng</th>
                        <th class="py-3 fw-semibold small text-uppercase">Trạng thái</th>
                        <th class="py-3 fw-semibold small text-uppercase">Tham gia</th>
                        <th class="py-3 fw-semibold small text-uppercase">Quyền hiện tại</th>
                        <th class="py-3 fw-semibold small text-uppercase">Hành động</th>
                        <th class="py-3 pe-4 fw-semibold small text-uppercase text-end">Chi tiết</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($account as $user)
                        <tr>
                            <td class="text-start px-4">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="{{ $user->avatar ? asset('storage/'.$user->avatar) : asset('public/images/avatar.png') }}"
                                         class="rounded-circle object-fit-cover" width="42" height="42" alt="Avatar"
                                         onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&size=42&background=random'">
                                    <div class="text-start">
                                        <div class="fw-semibold">{{ $user->name }}</div>
                                        <small class="text-muted">{{ $user->email }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($user->status == 'ACTIVE')
                                    <span class="badge badge-active px-3 py-2 rounded-pill fw-medium">
                                        <i class="fas fa-circle me-1" style="font-size:7px;"></i>Hoạt động
                                    </span>
                                @else
                                    <span class="badge badge-locked px-3 py-2 rounded-pill fw-medium">
                                        <i class="fas fa-lock me-1" style="font-size:9px;"></i>Đã khóa
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="text-muted small">{{ \Carbon\Carbon::parse($user->created_at)->format('d/m/Y') }}</span>
                            </td>
                            <td>
                                @if($user->role_name)
                                    <span class="badge {{ $user->role_name == 'ADMIN' ? 'badge-admin' : ($user->role_name == 'STAFF' ? 'badge-staff' : 'badge-customer') }} px-3 py-2 rounded-pill">
                                        {{ $user->role_name }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                        @if($user->status == 'ACTIVE')
                            @if($user->role_name == 'CUSTOMER')
                                <button class="btn btn-sm btn-warning rounded-pill px-3 mb-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#promoteModal"
                                        data-id="{{ $user->id }}"
                                        data-name="{{ $user->name }}">
                                    <i class="fas fa-arrow-up"></i> Nâng Staff
                                </button>
                            @elseif($user->role_name == 'STAFF')
                                <button class="btn btn-sm btn-danger rounded-pill px-3 mb-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#demoteModal"
                                        data-id="{{ $user->id }}"
                                        data-name="{{ $user->name }}">
                                    <i class="fas fa-arrow-down"></i> Hạ Customer
                                </button>
                            @elseif($user->role_name == 'ADMIN')
                                <button class="btn btn-sm btn-dark rounded-pill px-3 mb-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#demoteAdminModal"
                                        data-id="{{ $user->id }}"
                                        data-name="{{ $user->name }}">
                                    <i class="fas fa-user-shield"></i> Hạ Admin
                                </button>
                            @endif
                        @else
                            <span class="text-muted small">Không thể thay đổi</span>
                        @endif
                    </td>
                            <td class="pe-4 text-end">
                                <a href="{{ route('admin.detail.account', $user->id) }}" class="btn btn-sm rounded-pill px-3 fw-medium detail-btn">
                                    <i class="fas fa-eye me-1"></i>Chi tiết
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-5 text-center text-muted">
                                <i class="fas fa-inbox fs-2 d-block mb-2"></i>
                                Không tìm thấy tài khoản nào phù hợp.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($account->total() > 0)
        <div class="card-footer px-4 py-3">
            <div class="d-flex align-items-center justify-content-between">
                <span class="text-muted small">
                    Hiển thị <strong>{{ $account->count() }}</strong> / <strong>{{ $account->total() }}</strong> tài khoản
                </span>
                {{ $account->links() }}
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Modal Nâng Quyền -->
<div class="modal fade" id="promoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-arrow-up text-warning me-2"></i>Xác nhận nâng quyền</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-muted">
                Bạn có chắc muốn nâng quyền cho <strong id="modalUserName" class="text-white"></strong> lên <span class="badge badge-staff">Staff</span>?
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                <form method="POST" action="{{ route('admin.users.promote') }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="user_id" id="modalUserId">
                    <button type="submit" class="btn btn-warning rounded-pill px-4">Xác nhận</button>
                </form>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Modal Hạ Quyền -->
<div class="modal fade" id="demoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-arrow-down text-danger me-2"></i>Xác nhận hạ quyền</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-muted">
                Bạn có chắc muốn hạ quyền của <strong id="demoteUserName" class="text-white"></strong> về <span class="badge badge-customer">Customer</span>?
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                <form method="POST" action="{{ route('admin.users.demote') }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="user_id" id="demoteUserId">
                    <button type="submit" class="btn btn-danger rounded-pill px-4">Xác nhận</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Hạ Admin -->
<div class="modal fade" id="demoteAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-shield text-secondary me-2"></i>Xác nhận hạ Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-muted">
                Bạn có chắc muốn hạ quyền Admin của <strong id="demoteAdminName" class="text-white"></strong> xuống <span class="badge badge-staff">Staff</span>?
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                <form method="POST" action="{{ route('admin.users.demote.admin') }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="user_id" id="demoteAdminId">
                    <button type="submit" class="btn btn-dark rounded-pill px-4">Xác nhận</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('promoteModal').addEventListener('show.bs.modal', function(e) {
            document.getElementById('modalUserId').value    = e.relatedTarget.dataset.id;
            document.getElementById('modalUserName').textContent = e.relatedTarget.dataset.name;
        });
        document.getElementById('demoteModal').addEventListener('show.bs.modal', function(e) {
            document.getElementById('demoteUserId').value    = e.relatedTarget.dataset.id;
            document.getElementById('demoteUserName').textContent = e.relatedTarget.dataset.name;
        });
        document.getElementById('demoteAdminModal').addEventListener('show.bs.modal', function(e) {
            document.getElementById('demoteAdminId').value    = e.relatedTarget.dataset.id;
            document.getElementById('demoteAdminName').textContent = e.relatedTarget.dataset.name;
        });
    });
</script>
@endpush

@endsection
