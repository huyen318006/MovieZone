@extends('layout.admin')

@section('title', 'Quản lý tài khoản')

@section('content')

    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-1">Quản lý người dùng</h3>
                <p class="text-muted mb-0">
                    Danh sách tài khoản hệ thống
                </p>
            </div>
        </div>

        {{-- Bộ lọc --}}
        <form action="{{ route('admin.list_account') }}">
            <div class="card mb-4">
                <div class="card-body">

                    <form method="GET">

                        <div class="row g-3">

                            <div class="col-md-4">
                                <label class="form-label">
                                    Trạng thái
                                </label>

                                <select name="status" class="form-select">
                                    <option value="">
                                        Tất cả trạng thái
                                    </option>

                                    <option value="ACTIVE" {{ request('status') == 'ACTIVE' ? 'selected' : '' }}>
                                        Hoạt động
                                    </option>

                                    <option value="LOCK" {{ request('status') == 'LOCK' ? 'selected' : '' }}>
                                        Đã khóa
                                    </option>

                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">
                                    Vai trò
                                </label>

                                <select name="role" class="form-select">
                                    <option value="">
                                        Tất cả vai trò
                                    </option>

                                    <option value="admin" {{ request('role') == 'ADMIN' ? 'selected' : '' }}>
                                        Admin
                                    </option>

                                    <option value="staff" {{ request('role') == 'STAFF' ? 'selected' : '' }}>
                                        Staff
                                    </option>

                                    <option value="customer" {{ request('role') == 'CUSTOMER' ? 'selected' : '' }}>
                                        Customer
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-4 d-flex align-items-end gap-2">
                                <button class="btn btn-primary">
                                    Lọc
                                </button>

                                <a href="{{ route('admin.list_account') }}" class="btn btn-secondary">
                                    Làm mới
                                </a>
                            </div>

                        </div>

                    </form>

                </div>
            </div>
        </form>

        {{-- Danh sách --}}
        <div class="card">
            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-hover align-middle">

                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Tên người dùng</th>
                                <th>Email</th>
                                <th>Vai trò</th>
                                <th>Nâng Quyền</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th width="150">
                                    Thao tác
                                </th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse($account as $user)
                                <tr>

                                    <td>
                                        {{ $user->id }}
                                    </td>

                                    <td>
                                        {{ $user->name }}
                                    </td>

                                    <td>
                                        {{ $user->email }}
                                    </td>

                                    <td>

                                        @if ($user->role_name == 'ADMIN')
                                            <span class="badge bg-danger">
                                                Admin
                                            </span>
                                        @elseif($user->role_name == 'STAFF')
                                            <span class="badge bg-warning text-dark">
                                                Staff
                                            </span>
                                        @else
                                            <span class="badge bg-primary">
                                                Customer
                                            </span>
                                        @endif

                                    </td>
                                    <td>
                                        @if ($user->role_name == 'CUSTOMER')
                                            <button type="button" class="btn btn-warning btn-sm promote-btn"
                                                data-id="{{ $user->id }}" data-name="{{ $user->name }}"
                                                data-bs-toggle="modal" data-bs-target="#promoteModal">

                                                <i class="fas fa-arrow-up"></i>
                                                Nâng lên Staff
                                            </button>
                                        @elseif($user->role_name == 'STAFF')
                                            <span class="badge bg-warning text-dark px-3 py-2">
                                                <i class="fas fa-check-circle"></i> Staff
                                            </span>
                                        @elseif($user->role_name == 'ADMIN')
                                            <span class="badge bg-danger px-3 py-2">
                                                <i class="fas fa-crown"></i> Admin
                                            </span>
                                        @else
                                            <span class="badge bg-secondary px-3 py-2">Khách hàng</span>
                                        @endif
                                    </td>

                                    <td>

                                        @if ($user->status == 'ACTIVE')
                                            <span class="badge bg-success">
                                                Hoạt động
                                            </span>
                                        @else
                                            <span class="badge bg-danger">
                                                khóa
                                            </span>
                                        @endif

                                    </td>

                                    <td>
                                        {{ \Carbon\Carbon::parse($user->created_at)->format('d/m/Y') }}
                                    </td>

                                    <td>

                                        <a href="{{ route('admin.detail.account', $user->id) }}"
                                            class="btn btn-info btn-sm">
                                            Chi tiết
                                        </a>

                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="7" class="text-center">
                                        Không có dữ liệu
                                    </td>
                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>
                <div class="d-flex align-items-center justify-content-between mt-3">
                    <div class="text-muted">
                        Tổng số tài khoản: {{ $account->total() ?? 'N/A' }}
                    </div>

                    {{ $account->links() }}
                </div>

            </div>
        </div>

    </div>

    <!-- ==================== MODAL NÂNG QUYỀN ==================== -->
    <div class="modal fade" id="promoteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">
                        Xác nhận nâng quyền
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal">
                    </button>
                </div>

                <div class="modal-body">
                    Bạn có chắc muốn nâng quyền cho

                    <strong id="modalUserName"></strong>

                    lên Staff?
                </div>

                <div class="modal-footer">

                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Hủy
                    </button>

                    <form method="POST" action="{{ route('admin.users.promote') }}">

                        @csrf
                        @method('PUT')

                        <input type="hidden" name="user_id" id="modalUserId">

                        <button type="submit" class="btn btn-warning">

                            Xác nhận nâng quyền
                        </button>

                    </form>

                </div>

            </div>
        </div>
    </div>
    <!-- ====================================================== -->
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {

                const promoteModal =
                    document.getElementById('promoteModal');

                promoteModal.addEventListener(
                    'show.bs.modal',
                    function(event) {

                        const button = event.relatedTarget;

                        const userId =
                            button.getAttribute('data-id');

                        const userName =
                            button.getAttribute('data-name');

                        document.getElementById(
                            'modalUserId'
                        ).value = userId;

                        document.getElementById(
                            'modalUserName'
                        ).textContent = userName;
                    }
                );
            });
        </script>


    @endpush

@endsection
