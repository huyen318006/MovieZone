<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Thông báo hủy vé xem phim</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; border: 1px solid #eee; padding: 20px; border-radius: 8px;">
        <h2 style="color: #d9534f; border-bottom: 2px solid #d9534f; padding-bottom: 10px;">
            Thông Báo Hủy Suất Chiếu & Hoàn Tiền
        </h2>

        <p>Xin chào <strong>{{ $bookings[0]->user->name ?? 'Khách hàng' }}</strong>,</p>

        <p>Chúng tôi rất tiếc phải thông báo rằng vì một số vấn đề, bộ phim bạn đặt vé đã ngừng chiếu. Do đó, các suất chiếu liên quan đã bị hủy bỏ.</p>

        <p><strong>Danh sách đơn hàng bị ảnh hưởng:</strong></p>

        @foreach($bookings as $booking)
        <div style="background-color: #f9f9f9; padding: 15px; border-left: 4px solid #d9534f; margin: 15px 0;">

             <p style="margin: 0 0 8px 0;"><strong>Mã hóa đơn:</strong> #{{ $booking->id }}</p>
             <p><strong>Phim:</strong> {{ $booking->showtime->movie->title ?? 'Không xác định' }}</p>

    <p><strong>Suất chiếu:</strong> {{ $booking->showtime->start_time->format('d/m/Y H:i') ?? 'N/A' }}</p>

            <p style="margin: 0 0 8px 0;"><strong>Trạng thái đơn:</strong> Đã hủy (CANCELLED)</p>


            @if($booking->payment_status === 'REFUNDED')
                <p style="margin: 0; color: #5cb85c;"><strong>Trạng thái thanh toán:</strong> <strong style="text-transform: uppercase;">Hoàn tiền</strong></p>
                <p style="margin: 5px 0 0 0; font-size: 13px; color: #666;">
                    <i>(Hệ thống sẽ cập nhật quy đổi số tiền của quý khách vào điểm COIN trên hệ thống. 1 coin = 1đ).</i><br>
                    <i>* Nếu quý khách có sử dụng Mã giảm giá (Voucher) cho đơn hàng này, lượt sử dụng của mã đã được hoàn lại tự động để quý khách dùng cho lần sau.</i>
                </p>
            @endif
        </div>
        @endforeach

        <p>Mọi thắc mắc xin vui lòng liên hệ hotline tổng đài chăm sóc khách hàng của rạp.</p>
        <p>Trân trọng,<br><strong>Ban quản lý rạp chiếu phim</strong></p>
    </div>
</body>
</html>
