@extends('layout.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <div class="profile-container">
    <h2>Hồ sơ cá nhân</h2>
    
    <div class="profile-actions">
        <a href="{{ route('home') }}" class="action-btn">
            <i class="bi bi-house-door-fill"></i>
            <span>Trang chủ</span>
        </a>
        <a href="{{ route('membership.index') }}" class="action-btn action-btn-secondary" style="background: rgba(245, 158, 11, 0.15); border-color: rgba(245, 158, 11, 0.3); color: #fbbf24;">
            <i class="bi bi-shield-check"></i>
            <span>Membership</span>
        </a>
        <a href="{{ route('my-tickets.index') }}" class="action-btn action-btn-secondary">
            <i class="bi bi-receipt-cutoff"></i>
            <span>Lịch sử giao dịch</span>
        </a>
    </div>

    <div class="avatar-wrapper">
        <div class="avatar-box">
            @if(auth()->user()->avatar)
                <img src="{{ asset('storage/' . auth()->user()->avatar) }}" alt="avatar">
            @else
                <img src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&h=150&q=80" alt="avatar">
            @endif
        </div>
        <h3 class="user-display-name">{{ auth()->user()->name }}</h3>
        @php
            $lvlName = strtoupper(auth()->user()->membership?->level?->name ?? 'BRONZE');
        @endphp
        <a href="{{ route('membership.index') }}" class="user-badge text-decoration-none fw-bold" style="background: linear-gradient(135deg, #b45309, #d97706); color: #ffffff;">
            <i class="bi bi-gem me-1"></i> HẠNG {{ $lvlName }}
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="info-card-list">
        <div class="info-item">
            <div class="info-label"><i class="bi bi-person-fill"></i> Họ tên</div>
            <div class="info-value">{{ auth()->user()->name }}</div>
        </div>

        <div class="info-item">
            <div class="info-label"><i class="bi bi-envelope-fill"></i> Email</div>
            <div class="info-value value-email">{{ auth()->user()->email }}</div>
        </div>

        <div class="info-item">
            <div class="info-label"><i class="bi bi-telephone-fill"></i> Số điện thoại</div>
            <div class="info-value">{{ auth()->user()->phone ?? 'Chưa cập nhật' }}</div>
        </div>
    </div>

    <a href="{{ route('profile.edit') }}" class="btn-submit text-center-btn">
        <i class="bi bi-pencil-square"></i> Chỉnh sửa hồ sơ cá nhân
    </a>
</div>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

/* Toàn bộ khung nền Đen Carbon sâu */
.profile-container {
    font-family: 'Inter', sans-serif;
    max-width: 600px;
    background: #09090b; /* Đen kịt nền sâu cực chất */
    color: #f4f4f5; 
    padding: 35px;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.7);
    border: 1px solid #1e293b; /* Border xanh đen nhẹ */
    margin: 50px auto 50px auto;
}

/* Header & Tiêu đề */
.profile-container h2 {
    color: #ffffff;
    font-size: 24px;
    font-weight: 700;
    margin: 0 0 25px 0;
    border-left: 4px solid #2563eb; /* Thanh dọc xanh Neon nổi bật */
    padding-left: 12px;
}
.profile-container h3 {
    color: #e4e4e7;
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 20px;
}
.edit-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}
.back-profile-btn {
    text-decoration: none;
    color: #3b82f6;
    font-size: 14px;
    font-weight: 500;
    transition: color 0.2s;
}
.back-profile-btn:hover {
    color: #60a5fa;
}

/* Top Actions Navigation */
.profile-actions {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 30px;
    width: 100%;
}
.action-btn {
    text-decoration: none !important; 
    color: #ffffff !important;
    background: #1e293b;
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: 1px solid #334155;
    transition: all 0.2s;
}
.action-btn:hover {
    background: #2563eb;
    border-color: #2563eb;
    box-shadow: 0 0 12px rgba(37, 99, 235, 0.4);
}
.action-btn i {
    font-size: 16px;
}

/* Avatar Box */
.avatar-wrapper {
    text-align: center;
    margin-bottom: 30px;
    background: linear-gradient(180deg, rgba(37, 99, 235, 0.1) 0%, rgba(0,0,0,0) 100%);
    padding: 20px;
    border-radius: 12px;
}
.avatar-box {
    text-align: center;
    margin-bottom: 12px;
}
.avatar-box img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #2563eb; /* Viền xanh biển phát sáng */
    box-shadow: 0 0 20px rgba(37, 99, 235, 0.5);
    transition: transform 0.3s ease;
}
.avatar-box img:hover {
    transform: scale(1.05);
}
.user-display-name {
    font-size: 20px;
    font-weight: 600;
    color: #fff;
    margin: 8px 0 4px 0 !important;
}
.user-badge {
    font-size: 12px;
    background: rgba(37, 99, 235, 0.2);
    color: #60a5fa;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 500;
    border: 1px solid rgba(37, 99, 235, 0.3);
}

/* Thẻ hiển thị thông tin ở trang Profile chính */
.info-card-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 25px;
}
.info-item {
    background: #111827; /* Màu ô thông tin xanh đen tối */
    border: 1px solid #1f2937;
    padding: 14px 20px;
    border-radius: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.info-label {
    font-size: 14px;
    color: #9ca3af;
    display: flex;
    align-items: center;
    gap: 8px;
}
.info-label i {
    color: #3b82f6; /* Icon màu xanh biển */
}
.info-value {
    font-size: 15px;
    font-weight: 500;
    color: #ffffff;
}
.info-value.value-email {
    color: #93c5fd; /* Highlight email màu xanh nhạt */
}

/* Các trường Input mẫu form Edit */
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
    background: #111827; 
    border: 1px solid #374151;
    border-radius: 8px;
    color: #ffffff;
    font-size: 15px;
    transition: all 0.2s ease;
    box-sizing: border-box;
}
.profile-container input:focus {
    outline: none;
    border-color: #2563eb; 
    background: #1f2937;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
}
.profile-container input.readonly-input {
    background: #0f172a;
    border-color: #1e293b;
    color: #64748b;
    cursor: not-allowed;
}
.file-input-wrapper {
    background: #111827;
    border: 1px solid #374151;
    padding: 10px;
    border-radius: 8px;
}

/* Phân cách */
.divider {
    border: 0;
    height: 1px;
    background: #1e293b;
    margin: 35px 0;
}

/* Nút bấm (Buttons) */
.btn-submit {
    width: 100%;
    background: #2563eb; /* Màu xanh chủ đạo chính */
    color: #ffffff;
    border: none;
    padding: 14px 20px;
    font-size: 15px;
    font-weight: 600;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    margin-top: 10px;
    display: inline-block;
    box-sizing: border-box;
}
.text-center-btn {
    text-align: center;
    text-decoration: none;
}
.btn-submit:hover {
    background: #1d4ed8;
    box-shadow: 0 6px 16px rgba(37, 99, 235, 0.5);
    transform: translateY(-1px);
}
.btn-secondary {
    background: #1f2937;
    border: 1px solid #374151;
    color: #e5e7eb;
    box-shadow: none;
}
.btn-secondary:hover {
    background: #374151;
    color: #ffffff;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}

/* Mắt ẩn hiện password */
.input-password-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}
.input-password-wrapper input {
    padding-right: 45px !important;
}
.toggle-password {
    position: absolute;
    right: 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    color: #4b5563; 
    transition: color 0.2s;
    user-select: none;
}
.toggle-password:hover {
    color: #3b82f6; 
}
.toggle-password svg {
    width: 20px;
    height: 20px;
}

/* Alert báo lỗi / thành công */
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
</style>
@endsection