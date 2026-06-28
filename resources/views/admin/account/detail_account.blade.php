@extends('layout.admin')

@section('title', 'Chi tiết tài khoản')

@section('content')
<!-- Thông báo Success -->
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Thành công!</strong> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Thông báo Error -->
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Lỗi!</strong> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-1">
                    Chi tiết tài khoản
                </h3>

                <p class="text-muted mb-0">
                    Thông tin người dùng
                </p>
            </div>

            <a href="{{ route('admin.list_account') }}" class="btn btn-secondary">
                Quay lại
            </a>
        </div>

        <div class="card">

            <div class="card-body">

                <div class="row">

                    <div class="col-md-4 col-xl-3 text-center mb-4">
                        <div class="position-relative overflow-hidden rounded-4 shadow-sm mx-auto" style="aspect-ratio: 1/1; max-width: 250px;">
                            <img src="{{ $user->avatar ? asset('storage/' . $user->avatar) : asset('public/images/avatar.png') }}" 
                                class="w-100 h-100 object-fit-cover" 
                                alt="Avatar"
                                onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&size=250&background=random'">
                        </div>
                        <h5 class="mt-4 fw-bold text-dark">
                            {{ $user->name }}
                        </h5>
                        <p class="text-muted mb-0">{{ $user->email }}</p>
                    </div>

                    <div class="col-md-8 col-xl-9">

                        <table class="table table-bordered">

                            <tr>
                                <th width="200">
                                    ID
                                </th>
                                <td>
                                    {{ $user->id }}
                                </td>
                            </tr>

                            <tr>
                                <th>
                                    Họ tên
                                </th>
                                <td>
                                    {{ $user->name }}
                                </td>
                            </tr>

                            <tr>
                                <th>
                                    Email
                                </th>
                                <td>
                                    {{ $user->email }}
                                </td>
                            </tr>

                            <tr>
                                <th>
                                    Vai trò
                                </th>
                                <td>

                                    @if ($user->role_name == 'admin')
                                        <span class="badge bg-danger">
                                            Admin
                                        </span>
                                    @elseif($user->role_name == 'staff')
                                        <span class="badge bg-warning text-dark">
                                            Staff
                                        </span>
                                    @else
                                        <span class="badge bg-primary">
                                            Customer
                                        </span>
                                    @endif

                                </td>
                            </tr>

                            <tr>
                                <th>
                                    Trạng thái
                                </th>

                                <td>

                                    @if ($user->status == 'ACTIVE')
                                        <span class="badge bg-success">
                                            Hoạt động
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            Đã khóa
                                        </span>
                                    @endif

                                </td>
                            </tr>

                            <tr>
                                <th>
                                    Ngày tạo
                                </th>

                                <td>
                                    {{ \Carbon\Carbon::parse($user->created_at)->format('d/m/Y H:i') }}
                                </td>
                            </tr>

                            <tr>
                                <th>
                                    Cập nhật lần cuối
                                </th>

                                <td>
                                    {{ \Carbon\Carbon::parse($user->updated_at)->format('d/m/Y H:i') }}
                                </td>
                            </tr>

                        </table>

                        <div class="mt-3">

                            @if(auth()->user()->id == $user->id)
                                <a href="{{ route('admin.profile.account.admins', $user->id) }}" class="btn btn-primary">
                                    Sửa thông tin tài khoản
                                </a>
                            @else
                                @if ($user->status == 'ACTIVE')
                                    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#lockModal">
                                       Khóa Tài Khoản
                                    </button>
                                @else
                                    <a href="{{ route('admin.users.open',$user->id  ) }}" class="btn btn-success" onclick="return confirm('Hãy xác nhận mở lại quyền cho tài khoản chứ?')">
                                        Mở khóa tài khoản
                                    </a>
                                @endif
                            @endif

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- // --}}
    <div class="modal fade" id="lockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <form method="POST" action="{{ route('admin.users.lock', $user->id) }}">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Khóa tài khoản</h5>
                </div>

                <div class="modal-body">

                    <p class="text-danger">
                        Bạn có chắc chắn muốn khóa tài khoản này?
                    </p>

                    <label>Lý do khóa</label>
                    <textarea name="reason" class="form-control" required></textarea>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Hủy
                    </button>

                    <button type="submit" class="btn btn-danger">
                        Xác nhận
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

@endsection
