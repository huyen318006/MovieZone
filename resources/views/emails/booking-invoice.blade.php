<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hoá Đơn Vé Phim - MovieZone</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0f172a; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #0f172a; padding: 30px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #1e293b; border-radius: 16px; overflow: hidden; box-shadow: 0 25px 50px rgba(0,0,0,0.5);">

                    {{-- HEADER --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #6366f1, #8b5cf6); padding: 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">🎬 MovieZone</h1>
                            <p style="margin: 8px 0 0; color: #e0e7ff; font-size: 14px;">Hoá đơn đặt vé phim điện tử</p>
                        </td>
                    </tr>

                    {{-- SUCCESS BADGE --}}
                    <tr>
                        <td style="padding: 30px 30px 15px; text-align: center;">
                            <div style="display: inline-block; background: #065f46; color: #34d399; padding: 10px 24px; border-radius: 50px; font-weight: 600; font-size: 14px;">
                                ✅ Thanh toán thành công
                            </div>
                        </td>
                    </tr>

                    {{-- ORDER CODE --}}
                    <tr>
                        <td style="padding: 0 30px 20px; text-align: center;">
                            <p style="color: #94a3b8; font-size: 12px; margin: 0 0 4px; text-transform: uppercase; letter-spacing: 1px;">Mã hoá đơn</p>
                            <p style="color: #f1f5f9; font-size: 22px; font-weight: 700; margin: 0; letter-spacing: 2px;">{{ $order->order_code }}</p>
                        </td>
                    </tr>

                    {{-- QR CODE --}}
                    <tr>
                        <td style="padding: 0 30px 25px; text-align: center;">
                            <div style="display: inline-block; background: #ffffff; padding: 12px; border-radius: 12px;">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($order->generateTicketQrData()) }}&color=0f172a&bgcolor=ffffff&margin=4"
                                     alt="QR Code" width="200" height="200" style="display: block;">
                            </div>
                            <p style="color: #64748b; font-size: 12px; margin: 10px 0 0;">Quét mã QR để xem thông tin vé</p>
                        </td>
                    </tr>

                    {{-- DIVIDER --}}
                    <tr>
                        <td style="padding: 0 30px;">
                            <div style="height: 1px; background: linear-gradient(90deg, transparent, #334155, transparent);"></div>
                        </td>
                    </tr>

                    {{-- MOVIE INFO --}}
                    <tr>
                        <td style="padding: 25px 30px;">
                            <h3 style="color: #f1f5f9; margin: 0 0 16px; font-size: 18px;">🎬 Thông tin phim</h3>
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="color: #94a3b8; padding: 6px 0; font-size: 14px; width: 120px;">Tên phim</td>
                                    <td style="color: #f1f5f9; padding: 6px 0; font-size: 14px; font-weight: 600;">{{ $order->getBookingInfo('movie_title') }}</td>
                                </tr>
                                <tr>
                                    <td style="color: #94a3b8; padding: 6px 0; font-size: 14px;">Rạp chiếu</td>
                                    <td style="color: #f1f5f9; padding: 6px 0; font-size: 14px;">{{ $order->getBookingInfo('cinema') }}</td>
                                </tr>
                                <tr>
                                    <td style="color: #94a3b8; padding: 6px 0; font-size: 14px;">Phòng chiếu</td>
                                    <td style="color: #f1f5f9; padding: 6px 0; font-size: 14px;">{{ $order->getBookingInfo('room') }}</td>
                                </tr>
                                <tr>
                                    <td style="color: #94a3b8; padding: 6px 0; font-size: 14px;">Ngày chiếu</td>
                                    <td style="color: #f1f5f9; padding: 6px 0; font-size: 14px;">{{ $order->getBookingInfo('show_date') }}</td>
                                </tr>
                                <tr>
                                    <td style="color: #94a3b8; padding: 6px 0; font-size: 14px;">Suất chiếu</td>
                                    <td style="color: #f1f5f9; padding: 6px 0; font-size: 14px;">{{ $order->getBookingInfo('showtime') }}</td>
                                </tr>
                                <tr>
                                    <td style="color: #94a3b8; padding: 6px 0; font-size: 14px;">Ghế ngồi</td>
                                    <td style="color: #f1f5f9; padding: 6px 0; font-size: 14px; font-weight: 600;">{{ $order->getSeatCodesFormatted() }}</td>
                                </tr>
                                <tr>
                                    <td style="color: #94a3b8; padding: 6px 0; font-size: 14px;">Số lượng vé</td>
                                    <td style="color: #f1f5f9; padding: 6px 0; font-size: 14px;">{{ $order->metadata['seat_count'] ?? count($order->getBookingSeats()) }}</td>
                                </tr>
                                <tr>
                                    <td style="color: #94a3b8; padding: 6px 0; font-size: 14px;">Định dạng</td>
                                    <td style="color: #f1f5f9; padding: 6px 0; font-size: 14px;">{{ $order->getBookingInfo('format') }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- DIVIDER --}}
                    <tr>
                        <td style="padding: 0 30px;">
                            <div style="height: 1px; background: linear-gradient(90deg, transparent, #334155, transparent);"></div>
                        </td>
                    </tr>

                    {{-- SEAT PRICE DETAILS --}}
                    <tr>
                        <td style="padding: 25px 30px;">
                            <h3 style="color: #f1f5f9; margin: 0 0 16px; font-size: 18px;">🎟️ Chi tiết giá vé</h3>
                            <table width="100%" cellpadding="0" cellspacing="0">
                                @foreach($order->getBookingSeats() as $seat)
                                <tr>
                                    <td style="color: #cbd5e1; padding: 6px 0; font-size: 14px;">
                                        @if($seat['type'] === 'vip') 👑 VIP
                                        @elseif($seat['type'] === 'sweetbox') 💕 Sweetbox
                                        @else 🎬 Thường
                                        @endif
                                        — {{ $seat['code'] }}
                                    </td>
                                    <td style="color: #f1f5f9; padding: 6px 0; font-size: 14px; text-align: right; font-weight: 500;">{{ number_format($seat['price'], 0, ',', '.') }}đ</td>
                                </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    {{-- COMBO --}}
                    @if(!empty($order->metadata['combos']))
                    <tr>
                        <td style="padding: 0 30px 20px;">
                            <h3 style="color: #f1f5f9; margin: 0 0 16px; font-size: 18px;">🍿 Combo</h3>
                            <table width="100%" cellpadding="0" cellspacing="0">
                                @foreach($order->metadata['combos'] as $combo)
                                <tr>
                                    <td style="color: #cbd5e1; padding: 6px 0; font-size: 14px;">{{ $combo['name'] }} x{{ $combo['quantity'] }}</td>
                                    <td style="color: #f1f5f9; padding: 6px 0; font-size: 14px; text-align: right; font-weight: 500;">{{ number_format($combo['total_price'], 0, ',', '.') }}đ</td>
                                </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>
                    @endif

                    {{-- TOTAL --}}
                    <tr>
                        <td style="padding: 0 30px 25px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #312e81, #4c1d95); border-radius: 12px; padding: 20px;">
                                <tr>
                                    <td style="padding: 20px; text-align: center;">
                                        <p style="color: #c4b5fd; font-size: 13px; margin: 0 0 4px; text-transform: uppercase; letter-spacing: 1px;">Tổng thanh toán</p>
                                        <p style="color: #ffffff; font-size: 32px; font-weight: 700; margin: 0;">{{ number_format($order->amount, 0, ',', '.') }}đ</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- TRANSACTION INFO --}}
                    <tr>
                        <td style="padding: 0 30px 25px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="background: #0f172a; border-radius: 10px; padding: 16px;">
                                <tr>
                                    <td style="padding: 16px;">
                                        @if($order->transaction_id)
                                        <p style="color: #94a3b8; font-size: 12px; margin: 0 0 6px;">Mã giao dịch: <span style="color: #e2e8f0;">{{ $order->transaction_id }}</span></p>
                                        @endif
                                        <p style="color: #94a3b8; font-size: 12px; margin: 0 0 6px;">Thời gian đặt: <span style="color: #e2e8f0;">{{ $order->created_at->format('d/m/Y H:i:s') }}</span></p>
                                        <p style="color: #94a3b8; font-size: 12px; margin: 0;">Thanh toán lúc: <span style="color: #e2e8f0;">{{ $order->paid_at ? $order->paid_at->format('d/m/Y H:i:s') : '—' }}</span></p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- NOTE --}}
                    <tr>
                        <td style="padding: 0 30px 25px;">
                            <div style="background: #1e3a5f; border-left: 4px solid #3b82f6; padding: 14px 16px; border-radius: 0 8px 8px 0;">
                                <p style="color: #93c5fd; font-size: 13px; margin: 0; line-height: 1.5;">
                                    📌 <strong>Lưu ý:</strong> Vui lòng đưa mã QR hoặc mã hoá đơn <strong>{{ $order->order_code }}</strong> cho lễ tân tại rạp để nhận vé. Vé chỉ có giá trị trong suất chiếu đã đặt.
                                </p>
                            </div>
                        </td>
                    </tr>

                    {{-- FOOTER --}}
                    <tr>
                        <td style="background: #0f172a; padding: 20px 30px; text-align: center; border-top: 1px solid #1e293b;">
                            <p style="color: #475569; font-size: 12px; margin: 0;">Powered by SePay • MovieZone</p>
                            <p style="color: #334155; font-size: 11px; margin: 6px 0 0;">Email tự động — Vui lòng không trả lời email này</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
