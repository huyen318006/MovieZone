@extends('layout.admin')

@section('title', 'Thêm tài khoản mới')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1 fw-bold text-primary">
                <i class="fas fa-user-plus me-2"></i>Thêm tài khoản mới
            </h3>
            <p class="text-muted mb-0">
                Tạo một tài khoản người dùng hoặc quản trị viên mới cho hệ thống
            </p>
        </div>
        <a href="{{ route('admin.list_account') }}" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="fas fa-arrow-left me-2"></i>Quay lại
        </a>
    </div>

    <!-- Session error (trả về từ controller) -->
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-4" role="alert">
            <div class="d-flex align-items-center gap-3">
                <i class="fas fa-times-circle fs-4"></i>
                <div><strong>{{ session('error') }}</strong></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Validation errors -->
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-circle fs-4 me-3"></i>
                <div>
                    <strong class="d-block mb-1">Vui lòng kiểm tra lại thông tin!</strong>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
            <h5 class="card-title fw-bold text-dark mb-0">Thông tin tài khoản</h5>
        </div>
        
        <div class="card-body p-4">
            <!-- Thay đổi action trỏ tới route xử lý submit form thực tế -->
            <form action="{{ route('admin.account.store_account') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="row g-4">
                    <!-- Cột trái: Upload Avatar -->
                    <div class="col-md-4 col-xl-3 text-center">
                        <div class="avatar-upload-box p-4 border border-2 border-dashed rounded-4 bg-light transition-all position-relative" style="cursor: pointer;" onclick="document.getElementById('avatar').click()">
                            <div class="avatar-preview-container mb-3 d-flex justify-content-center">
                                <img id="avatar-preview" src="{{ asset('public/images/avatar.png') }}" class="rounded-circle shadow-sm object-fit-cover" width="160" height="160" alt="Avatar Preview" onerror="this.src='https://ui-avatars.com/api/?name=New+User&size=160&background=random'">
                            </div>
                            <h6 class="mb-1 mt-3 fw-semibold">Tải ảnh lên</h6>
                            <p class="text-muted small mb-0">JPG, JPEG, PNG<br>(Tối đa 2MB)</p>
                            <input type="file" class="d-none" id="avatar" name="avatar" accept="image/*" onchange="previewImage(this)">
                        </div>
                    </div>

                    <!-- Cột phải: Form nhập liệu -->
                    <div class="col-md-8 col-xl-9">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control rounded-3 @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="Họ và tên" required>
                                    <label for="name"><i class="fas fa-user text-muted me-2"></i>Họ và tên <span class="text-danger">*</span></label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="email" class="form-control rounded-3 @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="name@example.com" required>
                                    <label for="email"><i class="fas fa-envelope text-muted me-2"></i>Email <span class="text-danger">*</span></label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control rounded-3 @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}" placeholder="Số điện thoại" required>
                                    <label for="phone"><i class="fas fa-phone text-muted me-2"></i>Số điện thoại <span class="text-danger">*</span></label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <select class="form-select rounded-3 @error('role_id') is-invalid @enderror" id="role_id" name="role_id" required>
                                        <option value="" selected disabled>Chọn vai trò...</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                                {{ ucfirst(strtolower($role->name)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <label for="role_id"><i class="fas fa-user-shield text-muted me-2"></i>Vai trò <span class="text-danger">*</span></label>
                                </div>
                            </div>

                            <div class="col-12 mt-4 mb-2">
                                <h6 class="fw-semibold text-muted text-uppercase mb-0 border-bottom pb-2">Thiết lập bảo mật</h6>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating mb-3 position-relative">
                                    <input type="password" class="form-control rounded-3 @error('password') is-invalid @enderror" id="password" name="password" placeholder="Mật khẩu" required>
                                    <label for="password"><i class="fas fa-lock text-muted me-2"></i>Mật khẩu <span class="text-danger">*</span></label>
                                    <span class="position-absolute top-50 end-0 translate-middle-y pe-3 cursor-pointer text-muted" style="z-index: 10;" onclick="togglePassword('password', this)">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating mb-3 position-relative">
                                    <input type="password" class="form-control rounded-3" id="password_confirmation" name="password_confirmation" placeholder="Xác nhận mật khẩu" required>
                                    <label for="password_confirmation"><i class="fas fa-check-circle text-muted me-2"></i>Xác nhận mật khẩu <span class="text-danger">*</span></label>
                                    <span class="position-absolute top-50 end-0 translate-middle-y pe-3 cursor-pointer text-muted" style="z-index: 10;" onclick="togglePassword('password_confirmation', this)">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 mt-5 pt-4 border-top">
                    <button type="reset" class="btn btn-light rounded-pill px-4 shadow-sm border fw-medium" onclick="resetAvatar()">
                        <i class="fas fa-undo me-2"></i>Nhập lại
                    </button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 shadow-sm fw-semibold">
                        <i class="fas fa-save me-2"></i>Tạo tài khoản
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Styling cho giao diện hiện đại */
    .form-floating > .form-control:focus, 
    .form-floating > .form-select:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
    }
    .form-floating > label {
        padding-left: 1rem;
    }
    .avatar-upload-box:hover {
        background-color: #f8f9fa !important;
        border-color: #0d6efd !important;
        transform: translateY(-2px);
    }
    .border-dashed {
        border-style: dashed !important;
    }
    .transition-all {
        transition: all 0.3s ease;
    }
    .cursor-pointer {
        cursor: pointer;
    }
</style>

<script>
    // Preview ảnh khi chọn file
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            
            reader.onload = function(e) {
                document.getElementById('avatar-preview').src = e.target.result;
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Reset lại ảnh khi bấm nút "Nhập lại"
    function resetAvatar() {
        document.getElementById('avatar-preview').src = '{{ asset('public/images/avatar.png') }}';
    }

    // Ẩn / Hiện mật khẩu
    function togglePassword(inputId, iconElement) {
        const input = document.getElementById(inputId);
        const icon = iconElement.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
</script>
@endsection
