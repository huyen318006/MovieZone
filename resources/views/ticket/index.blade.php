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
                        <span class="status booking-status">
                            {{ $booking->status }}
                        </span>
                    </td>

                    <td>
                        <span class="status payment-status">
                            {{ $booking->payment_status }}
                        </span>
                    </td>

                    <td>
                        {{ $booking->tickets->count() }}
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
    .ticket-history-container{
    max-width:1200px;
    margin: 150px auto 0px auto;
    padding:0 20px;
}
.profile-actions {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 30px;
    width: 100%;
}
.action-btn {
    text-decoration: none !important; 
    color: #000000 !important;
    background: #c4c3c3;
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
.history-header{
    margin-bottom:25px;
}

.history-header h2{
    color:#0ea5e9;
    font-size:32px;
    font-weight:700;
    margin-bottom:8px;
}

.history-header p{
    color:#94a3b8;
}

.history-card{
    background:#ffffff;
    border-radius:16px;
    overflow:hidden;
    box-shadow:0 10px 30px rgba(0,0,0,.25);
}

.history-table{
    width:100%;
    border-collapse:collapse;
}

.history-table thead{
    background:#042369;
}

.history-table thead th{
    color:#ffffff;
    text-align:left;
    padding:18px;
    font-size:14px;
    text-transform:uppercase;
    letter-spacing:.5px;
}

.history-table tbody tr{
    border-bottom:1px solid rgba(85, 93, 234, 0.06);
    transition:.3s;
}

.history-table tbody tr:hover{
    background:#cbdfff;
}

.history-table tbody td{
    color:#000000;
    padding:18px;
}

.price{
    color:#ffffff;
    font-weight:700;
}

.status{
    display:inline-block;
    padding:6px 12px;
    border-radius:30px;
    font-size:12px;
    font-weight:600;
}

.booking-status{
    background:rgba(56,189,248,.15);
    color:#38bdf8;
}

.payment-status{
    background:rgba(34,197,94,.15);
    color:#22c55e;
}

.empty-row{
    text-align:center;
    padding:30px !important;
    color:#94a3b8 !important;
}
</style>
@endsection