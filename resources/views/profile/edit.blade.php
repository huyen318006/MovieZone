@extends('layout.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<div class="profile-container">

    <h2>Chỉnh sửa thông tin cá nhân</h2>
    <div class="profile-actions">

        <a href="{{ route('profile') }}" class="action-btn">
            <i class="bi bi-house-door-fill"></i>
            <span>Hồ sơ cá nhân</span>
        </a>

        <a href="{{ route('my-tickets.index') }}" class="action-btn action-btn-secondary">
            <i class="bi bi-receipt-cutoff"></i>
            <span>Lịch sử giao dịch</span>
        </a>

    </div>

    <div class="avatar-box">
        @if(auth()->user()->avatar)
            <img src="{{ asset('storage/' . auth()->user()->avatar) }}" alt="avatar">
        @else
            <img src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&h=150&q=80" alt="avatar">
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label>Họ tên</label>
            <input type="text" name="name" value="{{ auth()->user()->name }}">
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" value="{{ auth()->user()->email }}" readonly class="readonly-input">
        </div>

        <div class="form-group">
            <label>Số điện thoại</label>
            <input type="text" name="phone" value="{{ auth()->user()->phone }}">
        </div>

        <div class="form-group">
            <label>Ảnh đại diện</label>
            <div class="file-input-wrapper">
                <input type="file" name="avatar" accept="image/*">
            </div>
        </div>

        <button type="submit" class="btn-submit">
            Cập nhật thông tin
        </button>
    </form>

    <hr class="divider">

    <h3>Đổi mật khẩu</h3>


    <form action="{{ route('profile.password.change') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label>Mật khẩu hiện tại</label>
            <div class="input-password-wrapper">
                <input type="password" name="current_password" placeholder="••••••••" required>
                <span class="toggle-password"></span>
            </div>
        </div>

        <div class="form-group">
            <label>Mật khẩu mới</label>
            <div class="input-password-wrapper">
                <input type="password" name="new_password" placeholder="Tối thiểu 6 ký tự" required>
                <span class="toggle-password"></span>
            </div>
        </div>

        <div class="form-group">
            <label>Xác nhận mật khẩu</label>
            <div class="input-password-wrapper">
                <input type="password" name="new_password_confirmation" placeholder="Nhập lại mật khẩu mới" required>
                <span class="toggle-password"></span>
            </div>
        </div>

        <button type="submit" class="btn-submit btn-secondary">
            Đổi mật khẩu
        </button>
    </form>

</div>

<script>
    // Mã SVG vẽ mắt mở và mắt đóng gạch chéo chuẩn nét
    const eyeOpenIcon = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>`;
    const eyeCloseIcon = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 1-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>`;

    // Khởi tạo ban đầu hiển thị mắt đóng (mật khẩu đang ẩn)
    document.querySelectorAll('.toggle-password').forEach(span => {
        span.innerHTML = eyeCloseIcon;

        span.addEventListener('click', function() {
            const input = this.parentElement.querySelector('input');
            if (input.type === 'password') {
                input.type = 'text';
                this.innerHTML = eyeOpenIcon; // Hiện mật khẩu -> Đổi sang mắt mở
            } else {
                input.type = 'password';
                this.innerHTML = eyeCloseIcon; // Ẩn mật khẩu -> Đổi sang mắt gạch chéo
            }
        });
    });
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

.profile-container {
    margin: 150px auto 0px auto;
    font-family: 'Inter', sans-serif;
    max-width: 650px;
    background: #18181b;
    color: #f4f4f5;
    padding: 40px;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    border: 1px solid #27272a;
}

.profile-container h2,
.profile-container h3 {
    font-weight: 600;
    letter-spacing: -0.5px;
    margin-top: 0;
}

.profile-container h2 {
    color: #ffffff;
    font-size: 24px;
    margin-bottom: 25px;
    border-left: 4px solid #3b82f6;
    padding-left: 12px;
}

.profile-container h3 {
    color: #e4e4e7;
    font-size: 20px;
    margin-bottom: 20px;
}

.avatar-box {
    text-align: center;
    margin-bottom: 30px;
    position: relative;
}

.avatar-box img {
    width: 130px;
    height: 130px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #2563eb;
    box-shadow: 0 0 15px rgba(37, 99, 235, 0.3);
    transition: transform 0.3s ease;
}

.avatar-box img:hover {
    transform: scale(1.05);
}

.form-group {
    margin-bottom: 20px;
}

.profile-container label {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 500;
    color: #94a3b8;
}

.profile-container input[type="text"],
.profile-container input[type="email"],
.profile-container input[type="password"] {
    width: 100%;
    padding: 12px 16px;
    background: #1e293b;
    border: 1px solid #3f3f46;
    border-radius: 8px;
    color: #ffffff;
    font-size: 15px;
    transition: all 0.2s ease;
    box-sizing: border-box;
}

.profile-container input:focus {
    outline: none;
    border-color: #3b82f6;
    background: #202023;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

.profile-container input.readonly-input {
    background: #1f1f22;
    border-color: #27272a;
    color: #71717a;
    cursor: not-allowed;
}

.profile-container input[type="file"] {
    color: #a1a1aa;
    font-size: 14px;
}

.divider {
    border: 0;
    height: 1px;
    background: #27272a;
    margin: 35px 0;
}

.btn-submit {
    width: 100%;
    background: #2563eb;
    color: #ffffff;
    border: none;
    padding: 14px 20px;
    font-size: 16px;
    font-weight: 600;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    margin-top: 10px;
}

.btn-submit:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4);
}

.btn-submit:active {
    transform: translateY(1px);
}

.btn-secondary {
    background: #1e293b;
    border: 1px solid #334155;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.btn-secondary:hover {
    background: #334155;
    color: #ffffff;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3);
}

/* CSS ĐỂ ĐỊNH VỊ CON MẮT NẰM TRONG Ô INPUT */
.input-password-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.input-password-wrapper input {
    padding-right: 45px !important; /* Khoảng cách tránh chữ bị đè lên con mắt */
}

.toggle-password {
    position: absolute;
    right: 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    color: #64748b;
    transition: color 0.2s;
    user-select: none;
}

.toggle-password:hover {
    color: #f4f4f5;
}

.toggle-password svg {
    width: 20px;
    height: 20px;
}

/* Khung hiển thị thông báo alert */
.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
}
.alert-success {
    background: rgba(34, 197, 94, 0.1);
    color: #4ade80;
    border: 1px solid rgba(34, 197, 94, 0.2);
}
.alert-danger {
    background: rgba(239, 68, 68, 0.1);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.2);
}
.action-btn {
    text-decoration: none !important;
    color: #ffffff !important;
}
.action-btn i .action-btn span{
    color: inherit;
    text-decoration: none;
}
.action-btn i{
    font-size:18px;
}
.profile-actions{
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}
</style>
@endsection
