@extends('layout.app')

@section('content')
<div class="ticket-history-container">

    <div class="profile-actions">
        <a href="{{ route('home') }}" class="action-btn">
            <i class="bi bi-house-door-fill"></i>
            <span>Trang chủ</span>
        </a>
        <a href="{{ route('profile') }}" class="action-btn">
            <i class="bi bi-house-door-fill"></i>
            <span>Hồ sơ cá nhân</span>
        </a>
    </div>
    <div class="history-header">
        <h2>Lịch sử giao dịch</h2>
        <p>Danh sách các vé và giao dịch đã thực hiện</p>
    </div>

    <div class="history-card">

        <table class="history-table">
            <thead>
                <tr>
                    <th>Mã booking</th>
                    <th>Ngày đặt</th>
                    <th>Tổng tiền</th>
                    <th>Booking</th>
                    <th>Thanh toán</th>
                    <th>Số vé</th>
                </tr>
            </thead>

            <tbody>

            @forelse($bookings as $booking)

                <tr>

                    <td>
                        <strong>{{ $booking->booking_code }}</strong>
                    </td>

                    <td>
                        {{ $booking->created_at->format('d/m/Y H:i') }}
                    </td>

                    <td class="price">
                        {{ number_format($booking->final_amount) }} VNĐ
                    </td>

                    <td>
                        <span class="badge-status badge-booking">
                            {{ $booking->status }}
                        </span>
                    </td>

                    <td>
                        <span class="badge-status badge-payment">
                            {{ $booking->payment_status }}
                        </span>
                    </td>

                    <td>
                        <span class="badge-status badge-count">
                            {{ $booking->tickets->count() }} vé
                        </span>
                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="6" class="empty-row">
                        Chưa có giao dịch nào
                    </td>
                </tr>

            @endforelse

            </tbody>
        </table>

    </div>

</div>
<style>

Dưới đây là toàn bộ mã nguồn view Blade và CSS đã được tinh chỉnh lại toàn diện theo đúng thiết kế, layout và màu sắc như hình bạn đã cung cấp:

1. File Blade Template (ticket-history.blade.php)
HTML
@extends('layout.app')

@section('content')
<div class="ticket-history-container">

    {{-- Nhóm nút hành động phía trên góc phải giống nút "Thêm phim" --}}
    <div class="profile-actions-wrapper">
        <div class="profile-actions">
            <a href="{{ route('home') }}" class="action-btn">
                <i class="bi bi-house-door-fill"></i>
                <span>Trang chủ</span>
            </a>
            <a href="{{ route('profile') }}" class="action-btn">
                <i class="bi bi-person-badge-fill"></i>
                <span>Hồ sơ cá nhân</span>
            </a>
        </div>
    </div>

    {{-- Tiêu đề trang đồng bộ với "Quản lý phim" --}}
    <div class="history-header">
        <h2>Lịch sử giao dịch</h2>
        <p>Danh sách các vé và giao dịch đã thực hiện trên hệ thống</p>
    </div>

    {{-- Khối danh sách dạng bảng tối màu giống trong ảnh --}}
    <div class="history-card">
        <div class="card-header-title">
            <span>Danh sách giao dịch</span>
            <span class="total-count">Tổng số: {{ $bookings->count() }}</span>
        </div>

        <table class="history-table">
            <thead>
                <tr>
                    <th>Mã booking</th>
                    <th>Ngày đặt</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái đặt</th>
                    <th>Thanh toán</th>
                    <th>Số vé</th>
                </tr>
            </thead>

            <tbody>
            @forelse($bookings as $booking)
                <tr>
                    <td>
                        <strong class="booking-code">{{ $booking->booking_code }}</strong>
                    </td>

                    <td class="text-soft">
                        {{ $booking->created_at->format('Y-m-d H:i') }}
                    </td>

                    <td class="price">
                        {{ number_format($booking->final_amount, 0, ',', '.') }} VNĐ
                    </td>

                    <td>
                        {{-- Trạng thái linh hoạt dựa trên dữ liệu (Ví dụ mẫu: Đã đặt / Thành công) --}}
                        <span class="status booking-status status-active">
                            {{ $booking->status }}
                        </span>
                    </td>

                    <td>
                        <span class="status payment-status status-paid">
                            {{ $booking->payment_status }}
                        </span>
                    </td>

                    <td class="text-soft font-bold">
                        {{ $booking->tickets->count() }} phút
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="empty-row">
                        <i class="bi bi-inbox" style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
                        Chưa có giao dịch nào được thực hiện
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<style>
/* --- ĐỊNH DẠNG LAYOUT TỔNG THỂ (DARK MODE) --- */
body {
    background-color: #0b0f19 !important; /* Màu nền đen tối của hệ thống */
}

.ticket-history-container {
    max-width: 1200px;
    margin: 100px auto 50px auto;
    padding: 0 20px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}

/* --- KHỐI ĐIỀU HƯỚNG/NÚT HÀNH ĐỘNG --- */
.profile-actions-wrapper {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 15px;
}

.profile-actions {
    display: flex;
    gap: 10px;
}

.action-btn {
    text-decoration: none !important; 
    color: #ffffff !important;
    background: #3b82f6; /* Xanh biển tươi như nút "Thêm phim" */
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: none;
    transition: background 0.2s ease;
}

.action-btn:hover {
    background: #2563eb;
}

/* --- TIÊU ĐỀ TRANG --- */
.history-header {
    margin-bottom: 25px;
}

.history-header h2 {
    color: #ffffff;
    font-size: 26px;
    font-weight: 700;
    margin: 0 0 6px 0;
}

.history-header p {
    color: #94a3b8;
    font-size: 14px;
    margin: 0;
}

/* --- THIẾT KẾ KHỐI CARD & BẢNG (ĐỒNG BỘ 100% ẢNH MẪU) --- */
.history-card {
    background: #111827; /* Màu nền xám đen của khung danh sách */
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.05);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.card-header-title {
    padding: 16px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    color: #ffffff;
    font-weight: 600;
    font-size: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header-title .total-count {
    font-size: 13px;
    color: #94a3b8;
    font-weight: 400;
}

.history-table {
    width: 100%;
    border-collapse: collapse;
}

/* Tiêu đề cột */
.history-table thead th {
    color: #94a3b8; /* Chữ xám nhạt như ảnh */
    text-align: left;
    padding: 14px 20px;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .5px;
    font-weight: 600;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

/* Các hàng trong bảng */
.history-table tbody tr {
    border-bottom: 1px solid rgba(255, 255, 255, 0.03);
    transition: background 0.2s ease;
}

.history-table tbody tr:hover {
    background: rgba(255, 255, 255, 0.02); /* Hiệu ứng hover tối tinh tế */
}

.history-table tbody td {
    color: #ffffff;
    padding: 16px 20px;
    font-size: 14px;
    vertical-align: middle;
}

/* --- CÁC ĐỊNH DẠNG PHẦN TỬ CON TRONG TABLE --- */
.booking-code {
    color: #ffffff;
    font-weight: 600;
}

.text-soft {
    color: #94a3b8; /* Sử dụng cho ngày tháng và số lượng nhẹ nhàng */
}

.font-bold {
    font-weight: 600;
}

.price {
    color: #38bdf8; /* Màu xanh nước biển sáng làm điểm nhấn cho tiền tệ */
    font-weight: 700;
}

/* Badge trạng thái giống ô "Đang chiếu" */
.status {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

/* Trạng thái xanh lá nhẹ dịu như nhãn "Đang chiếu" */
.status-active, .status-paid {
    background: rgba(16, 185, 129, 0.15);
    color: #10b981;
}

/* Hàng trống khi không có dữ liệu */
.empty-row {
    text-align: center;
    padding: 40px !important;
    color: #64748b !important;
    font-size: 14px;
}

/* Responsive cho thiết bị di động */
@media(max-width: 768px) {
    .ticket-history-container {
        margin-top: 40px;
    }
    .profile-actions-wrapper {
        justify-content: -webkit-flex;
        justify-content: center;
    }
    .profile-actions {
        width: 100%;
    }
    .action-btn {
        flex: 1;
        justify-content: center;
    }
    .history-table {
        display: block;
        overflow-x: auto; /* Cuộn ngang nếu màn hình quá bé để tránh vỡ chữ */
    }
}
/* --- ĐỊNH DẠNG CHUNG CHO NHÃN TRẠNG THÁI (Giống ô Đang Chiếu) --- */
.badge-status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 4px 10px;        /* Độ rộng vừa vặn, ôm sát text */
    border-radius: 4px;       /* Bo góc vuông nhẹ như trong ảnh */
    font-size: 12px;          /* Cỡ chữ nhỏ gọn tinh tế */
    font-weight: 600;         /* Nét chữ dày rõ ràng */
    white-space: nowrap;
}

/* 1. Cột Booking (Trạng thái đặt): Tông xanh biển mờ quyến rũ */
.badge-booking {
    background-color: rgba(56, 189, 248, 0.16) !important; /* Nền xanh mờ */
    color: #38bdf8 !important;                             /* Chữ xanh sáng */
}

/* 2. Cột Thanh toán: Tông xanh lá mờ (Y hệt màu của ô "Đang chiếu" trong ảnh) */
.badge-payment {
    background-color: rgba(16, 185, 129, 0.16) !important; /* Nền xanh lá mờ */
    color: #10b981 !important;                             /* Chữ xanh lá sáng */
}

/* 3. Cột Số vé: Tông xám đen mờ, chữ trắng tinh rực rỡ */
.badge-count {
    background-color: rgba(255, 255, 255, 0.08) !important; /* Nền xám mờ nhẹ */
    color: #ffffff !important;                              /* Chữ trắng nổi bật */
}

/* Thiết lập màu chữ mặc định cho các ô td thông thường (như ngày tháng) */
.history-table tbody td {
    color: #94a3b8; /* Màu xám dịu để tôn lên các nhãn trạng thái phía trên */
    padding: 16px 20px;
    vertical-align: middle;
}
</style>
@endsection