<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hoá đơn MovieZone</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0f172a; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">

    <!-- Container -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #0f172a; padding: 30px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">

                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #6366f1, #8b5cf6, #ec4899); padding: 30px 40px; border-radius: 16px 16px 0 0; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: 700; letter-spacing: 1px;">
                                🎬 MovieZone
                            </h1>
                            <p style="color: rgba(255,255,255,0.9); margin: 8px 0 0; font-size: 14px;">
                                Hoá đơn thanh toán vé xem phim
                            </p>
                        </td>
                    </tr>

                    <!-- Success Banner -->
                    <tr>
                        <td style="background-color: #1e293b; padding: 24px 40px; text-align: center; border-bottom: 1px solid #334155;">
                            <div style="display: inline-block; background-color: #065f46; color: #6ee7b7; padding: 8px 20px; border-radius: 20px; font-size: 14px; font-weight: 600;">
                                ✅ Thanh toán thành công
                            </div>
                            <p style="color: #94a3b8; margin: 12px 0 0; font-size: 13px;">
                                Mã hoá đơn: <strong style="color: #e2e8f0;">{{ $invoice->invoice_code }}</strong>
                            </p>
                        </td>
                    </tr>

                    <!-- Movie Info -->
                    <tr>
                        <td style="background-color: #1e293b; padding: 28px 40px;">
                            <h2 style="color: #f8fafc; margin: 0 0 16px; font-size: 20px; font-weight: 700;">
                                🎬 {{ $invoice->movie_title }}
                            </h2>
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="50%" style="padding: 8px 0;">
                                        <span style="color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Rạp chiếu</span><br>
                                        <span style="color: #e2e8f0; font-size: 14px; font-weight: 500;">{{ $invoice->cinema }}</span>
                                    </td>
                                    <td width="50%" style="padding: 8px 0;">
                                        <span style="color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Phòng chiếu</span><br>
                                        <span style="color: #e2e8f0; font-size: 14px; font-weight: 500;">{{ $invoice->room }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="50%" style="padding: 8px 0;">
                                        <span style="color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Ngày chiếu</span><br>
                                        <span style="color: #e2e8f0; font-size: 14px; font-weight: 500;">{{ $invoice->show_date }}</span>
                                    </td>
                                    <td width="50%" style="padding: 8px 0;">
                                        <span style="color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Suất chiếu</span><br>
                                        <span style="color: #e2e8f0; font-size: 14px; font-weight: 500;">{{ $invoice->showtime }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="50%" style="padding: 8px 0;">
                                        <span style="color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Định dạng</span><br>
                                        <span style="color: #e2e8f0; font-size: 14px; font-weight: 500;">{{ $invoice->format }}</span>
                                    </td>
                                    <td width="50%" style="padding: 8px 0;">
                                        <span style="color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Ghế ngồi</span><br>
                                        <span style="color: #e2e8f0; font-size: 14px; font-weight: 500;">{{ $invoice->seat_codes }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="background-color: #1e293b; padding: 0 40px;">
                            <div style="border-top: 1px dashed #475569;"></div>
                        </td>
                    </tr>

                    <!-- Seat Details -->
                    <tr>
                        <td style="background-color: #1e293b; padding: 24px 40px;">
                            <h3 style="color: #f8fafc; margin: 0 0 16px; font-size: 16px; font-weight: 600;">
                                🎫 Chi tiết vé
                            </h3>
                            <table width="100%" cellpadding="0" cellspacing="0">
                                @foreach($invoice->seats as $seat)
                                <tr>
                                    <td style="padding: 10px 12px; background-color: #0f172a; border-radius: 8px; margin-bottom: 6px;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="color: #e2e8f0; font-size: 14px;">
                                                    @if($seat['type'] === 'vip')
                                                        👑 VIP
                                                    @elseif($seat['type'] === 'sweetbox')
                                                        💕 Sweetbox
                                                    @else
                                                        🎬 Thường
                                                    @endif
                                                    — <strong>{{ $seat['code'] }}</strong>
                                                </td>
                                                <td align="right" style="color: #a78bfa; font-size: 14px; font-weight: 600;">
                                                    {{ number_format($seat['price'], 0, ',', '.') }}đ
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr><td style="height: 6px;"></td></tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    <!-- Total -->
                    <tr>
                        <td style="background-color: #1e293b; padding: 0 40px 24px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #4c1d95, #6d28d9); border-radius: 12px; padding: 16px 20px;">
                                <tr>
                                    <td style="color: rgba(255,255,255,0.8); font-size: 14px; padding: 12px 16px;">
                                        Tổng thanh toán
                                    </td>
                                    <td align="right" style="color: #ffffff; font-size: 24px; font-weight: 700; padding: 12px 16px;">
                                        {{ $invoice->formatted_amount }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- QR Code -->
                    <tr>
                        <td style="background-color: #1e293b; padding: 0 40px 24px; text-align: center;">
                            <div style="background-color: #0f172a; border-radius: 12px; padding: 20px; display: inline-block;">
                                <p style="color: #94a3b8; margin: 0 0 12px; font-size: 13px;">
                                    📱 Mã QR xác nhận vé — Đưa cho lễ tân tại rạp
                                </p>
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode($invoice->invoice_code) }}&color=0f172a&bgcolor=ffffff&margin=8"
                                     alt="QR Code" width="180" height="180"
                                     style="border-radius: 8px; background: #fff; padding: 8px;">
                                <p style="color: #e2e8f0; margin: 12px 0 0; font-size: 16px; font-weight: 600; letter-spacing: 1px;">
                                    {{ $invoice->invoice_code }}
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- Transaction Info -->
                    <tr>
                        <td style="background-color: #1e293b; padding: 0 40px 28px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #0f172a; border-radius: 10px; padding: 4px;">
                                @if($invoice->transaction_id)
                                <tr>
                                    <td style="color: #94a3b8; font-size: 13px; padding: 10px 16px; border-bottom: 1px solid #1e293b;">Mã giao dịch</td>
                                    <td align="right" style="color: #e2e8f0; font-size: 13px; padding: 10px 16px; border-bottom: 1px solid #1e293b; font-weight: 500;">{{ $invoice->transaction_id }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="color: #94a3b8; font-size: 13px; padding: 10px 16px; border-bottom: 1px solid #1e293b;">Phương thức</td>
                                    <td align="right" style="color: #e2e8f0; font-size: 13px; padding: 10px 16px; border-bottom: 1px solid #1e293b; font-weight: 500;">{{ $invoice->payment_method }}</td>
                                </tr>
                                <tr>
                                    <td style="color: #94a3b8; font-size: 13px; padding: 10px 16px;">Thời gian thanh toán</td>
                                    <td align="right" style="color: #e2e8f0; font-size: 13px; padding: 10px 16px; font-weight: 500;">
                                        {{ $invoice->paid_at ? $invoice->paid_at->format('d/m/Y H:i:s') : '—' }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #0f172a; padding: 24px 40px; border-top: 1px solid #334155; border-radius: 0 0 16px 16px; text-align: center;">
                            <p style="color: #64748b; margin: 0 0 8px; font-size: 13px;">
                                Cảm ơn bạn đã sử dụng dịch vụ <strong style="color: #a78bfa;">MovieZone</strong>!
                            </p>
                            <p style="color: #475569; margin: 0; font-size: 12px;">
                                Vui lòng đến rạp trước giờ chiếu 15 phút và đưa mã QR cho nhân viên.
                            </p>
                            <p style="color: #475569; margin: 12px 0 0; font-size: 11px;">
                                © {{ date('Y') }} MovieZone • Powered by SePay
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
