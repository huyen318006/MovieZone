@extends('layout.admin')

@section('title', 'Cập nhật tài khoản')

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

<!-- Error validation -->
@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">
                Cập nhật tài khoản
            </h3>
            <p class="text-muted mb-0">
                Chỉnh sửa thông tin cá nhân của bạn
            </p>
        </div>
        <a href="{{ route('admin.detail.account', $user->id ?? auth()->id()) }}" class="btn btn-secondary">
            Quay lại
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 col-xl-3 text-center mb-4">
                    <div class="position-relative overflow-hidden rounded-4 shadow-sm mx-auto group-hover-overlay" style="aspect-ratio: 1/1; max-width: 250px; cursor: pointer;" onclick="document.getElementById('avatar').click()">
                        @if(auth()->user()->avatar)
                            <img id="avatar-preview" src="{{ Storage::url(auth()->user()->avatar) }}" class="w-100 h-100 object-fit-cover" alt="Avatar">
                        @else
                            <img id="avatar-preview" src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&size=250&background=random" class="w-100 h-100 object-fit-cover" alt="Avatar">
                        @endif
                        
                        <!-- Lớp phủ khi hover -->
                        <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-flex flex-column align-items-center justify-content-center hover-overlay transition-all text-white">
                            <i class="fas fa-camera fs-3 mb-2"></i>
                            <span class="fw-medium">Thay đổi ảnh</span>
                        </div>
                    </div>
                    <h5 class="mt-4 fw-bold text-dark">{{ auth()->user()->name }}</h5>
                    <p class="text-muted">{{ auth()->user()->email }}</p>
                </div>

                <div class="col-md-8 col-xl-9">
                    <form action="{{ route('admin.profile.account.admins.update', $user->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Họ tên <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', auth()->user()->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', auth()->user()->phone) }}" required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3 d-none">
                            <input type="file" class="form-control @error('avatar') is-invalid @enderror" id="avatar" name="avatar" accept="image/*" onchange="previewImage(this)">
                            @error('avatar')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                        </div>
                    </form>

                    <hr class="my-5">

                    <h5 class="mb-3 text-danger">Đổi mật khẩu</h5>
                    <form action="{{ route('admin.profile.account.admins.updatepassword', $user->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label for="old_password" class="form-label">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                            <input type="password" class="form-control @error('old_password') is-invalid @enderror" id="old_password" name="old_password" required>
                            @error('old_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Mật khẩu mới <span class="text-danger">*</span></label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                        </div>

                        <div class="mt-4 d-flex justify-content-between align-items-center">
                            <button type="submit" class="btn btn-danger">Đổi mật khẩu</button>
                            <a href="{{ route('password.request') }}" class="text-decoration-none">Quên mật khẩu?</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .group-hover-overlay:hover .hover-overlay {
        opacity: 1;
    }
    .hover-overlay {
        opacity: 0;
    }
    .transition-all {
        transition: all 0.3s ease;
    }
</style>

<script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            
            reader.onload = function(e) {
                document.getElementById('avatar-preview').src = e.target.result;
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endsection
