<div class="ticket-history-container">

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
    margin:40px auto;
    padding:0 20px;
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
    background:#111827;
    border-radius:16px;
    overflow:hidden;
    box-shadow:0 10px 30px rgba(0,0,0,.25);
}

.history-table{
    width:100%;
    border-collapse:collapse;
}

.history-table thead{
    background:#0f172a;
}

.history-table thead th{
    color:#38bdf8;
    text-align:left;
    padding:18px;
    font-size:14px;
    text-transform:uppercase;
    letter-spacing:.5px;
}

.history-table tbody tr{
    border-bottom:1px solid rgba(255,255,255,.06);
    transition:.3s;
}

.history-table tbody tr:hover{
    background:#1e293b;
}

.history-table tbody td{
    color:#f1f5f9;
    padding:18px;
}

.price{
    color:#38bdf8;
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