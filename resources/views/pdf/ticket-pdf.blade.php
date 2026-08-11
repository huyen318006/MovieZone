<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Vé Xem Phim — MovieZone</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: A4 portrait;
            margin: 0;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            background: #0f172a;
            color: #f1f5f9;
        }

        .ticket-page {
            page-break-after: always;
            width: 100%;
            min-height: 100vh;
            background: #0f172a;
            padding: 30px;
            position: relative;
        }

        .ticket-page:last-child {
            page-break-after: avoid;
        }

        .ticket-card {
            background: #1e293b;
            border-radius: 16px;
            overflow: hidden;
            max-width: 520px;
            margin: 0 auto;
            border: 1px solid #334155;
        }

        /* Header */
        .ticket-header {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            padding: 24px 28px;
            text-align: center;
        }

        .ticket-header h1 {
            font-size: 26px;
            font-weight: 700;
            color: #ffffff;
            margin: 0 0 4px;
        }

        .ticket-header p {
            font-size: 12px;
            color: #e0e7ff;
            margin: 0;
        }

        /* Status Badge */
        .status-badge {
            text-align: center;
            padding: 20px 28px 10px;
        }

        .status-badge span {
            display: inline-block;
            background: #065f46;
            color: #34d399;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 13px;
        }

        /* Booking Code */
        .booking-code {
            text-align: center;
            padding: 10px 28px 16px;
        }

        .booking-code .label {
            font-size: 10px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 4px;
        }

        .booking-code .code {
            font-size: 20px;
            font-weight: 700;
            color: #f1f5f9;
            letter-spacing: 2px;
        }

        /* QR Section */
        .qr-section {
            text-align: center;
            padding: 10px 28px 20px;
        }

        .qr-wrapper {
            display: inline-block;
            background: #ffffff;
            padding: 12px;
            border-radius: 12px;
        }

        .qr-wrapper img {
            display: block;
            width: 180px;
            height: 180px;
        }

        .qr-note {
            font-size: 11px;
            color: #64748b;
            margin-top: 8px;
        }

        /* Ticket Code */
        .ticket-code-section {
            text-align: center;
            padding: 0 28px 16px;
        }

        .ticket-code-section .label {
            font-size: 10px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 2px;
        }

        .ticket-code-section .code {
            font-size: 16px;
            font-weight: 700;
            color: #fbbf24;
            letter-spacing: 1.5px;
            font-family: monospace, DejaVu Sans;
        }

        /* Divider */
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #334155, transparent);
            margin: 0 28px;
        }

        /* Info Grid */
        .info-section {
            padding: 18px 28px;
        }

        .info-section h3 {
            font-size: 15px;
            color: #f1f5f9;
            margin-bottom: 12px;
        }

        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 6px;
        }

        .info-label {
            display: table-cell;
            width: 110px;
            font-size: 12px;
            color: #94a3b8;
            padding: 3px 0;
            vertical-align: top;
        }

        .info-value {
            display: table-cell;
            font-size: 12px;
            color: #f1f5f9;
            font-weight: 600;
            padding: 3px 0;
        }

        /* Seat Highlight */
        .seat-highlight {
            text-align: center;
            padding: 14px 28px;
        }

        .seat-badge {
            display: inline-block;
            background: linear-gradient(135deg, #312e81, #4c1d95);
            padding: 14px 28px;
            border-radius: 12px;
            text-align: center;
        }

        .seat-badge .seat-label {
            font-size: 10px;
            color: #c4b5fd;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .seat-badge .seat-code {
            font-size: 24px;
            font-weight: 700;
            color: #ffffff;
        }

        .seat-badge .seat-type {
            font-size: 11px;
            color: #a78bfa;
            margin-top: 2px;
        }

        /* Note */
        .note-section {
            padding: 0 28px 20px;
        }

        .note-box {
            background: #1e3a5f;
            border-left: 4px solid #3b82f6;
            padding: 12px 14px;
            border-radius: 0 8px 8px 0;
        }

        .note-box p {
            font-size: 11px;
            color: #93c5fd;
            line-height: 1.5;
        }

        /* Footer */
        .ticket-footer {
            background: #0f172a;
            padding: 14px 28px;
            text-align: center;
            border-top: 1px solid #1e293b;
        }

        .ticket-footer p {
            font-size: 10px;
            color: #475569;
        }

        /* Page indicator */
        .page-indicator {
            text-align: center;
            padding: 8px;
            font-size: 10px;
            color: #475569;
        }
    </style>
</head>
<body>
@foreach($tickets as $index => $ticket)
    <div class="ticket-page">
        <div class="ticket-card">

            {{-- HEADER --}}
            <div class="ticket-header">
                <h1>🎬 MovieZone</h1>
                <p>Vé xem phim điện tử</p>
            </div>

            {{-- STATUS --}}
            <div class="status-badge">
                <span>✅ Thanh toán thành công</span>
            </div>

            {{-- BOOKING CODE --}}
            <div class="booking-code">
                <div class="label">Mã Booking</div>
                <div class="code">{{ $booking->booking_code }}</div>
            </div>

            {{-- QR CODE --}}
            <div class="qr-section">
                <div class="qr-wrapper">
                    {!! $qrImages[$ticket->id] !!}
                </div>
                <div class="qr-note">Quét mã QR để check-in tại rạp</div>
            </div>

            {{-- TICKET CODE --}}
            <div class="ticket-code-section">
                <div class="label">Mã Vé</div>
                <div class="code">{{ $ticket->ticket_code }}</div>
            </div>

            <div class="divider"></div>

            {{-- SEAT HIGHLIGHT --}}
            <div class="seat-highlight">
                <div class="seat-badge">
                    <div class="seat-label">Ghế</div>
                    <div class="seat-code">{{ $ticket->bookingSeat->seat_code ?? 'N/A' }}</div>
                    <div class="seat-type">
                        @php
                            $seatType = $ticket->bookingSeat->seat_type ?? 'STANDARD';
                        @endphp
                        @if($seatType === 'VIP') 👑 VIP
                        @elseif($seatType === 'COUPLE') 💕 Sweetbox
                        @else 🎬 Thường
                        @endif
                        — {{ number_format($ticket->bookingSeat->price ?? 0, 0, ',', '.') }}đ
                    </div>
                </div>
            </div>

            <div class="divider"></div>

            {{-- MOVIE INFO --}}
            <div class="info-section">
                <h3>🎬 Thông tin suất chiếu</h3>

                <div class="info-row">
                    <div class="info-label">Tên phim</div>
                    <div class="info-value">{{ $showtime->movie->title ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Phòng chiếu</div>
                    <div class="info-value">{{ $showtime->room->name ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Ngày chiếu</div>
                    <div class="info-value">{{ $showtime->start_time ? $showtime->start_time->format('d/m/Y') : 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Suất chiếu</div>
                    <div class="info-value">
                        {{ $showtime->start_time ? $showtime->start_time->format('H:i') : '' }}
                        —
                        {{ $showtime->end_time ? $showtime->end_time->format('H:i') : '' }}
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Định dạng</div>
                    <div class="info-value">{{ $showtime->format ?? '2D' }} / {{ $showtime->language_type ?? 'Phụ đề' }}</div>
                </div>
            </div>

            <div class="divider"></div>

            {{-- NOTE --}}
            <div class="note-section" style="margin-top: 12px;">
                <div class="note-box">
                    <p>
                        📌 <strong>Lưu ý:</strong> Vui lòng xuất trình mã QR cho nhân viên tại rạp để check-in.
                        Mỗi vé chỉ sử dụng được <strong>một lần</strong>. Vé chỉ có giá trị trong suất chiếu đã đặt.
                    </p>
                </div>
            </div>

            {{-- FOOTER --}}
            <div class="ticket-footer">
                <p>MovieZone — Hệ thống bán vé xem phim online</p>
                <p style="margin-top: 3px;">Vé {{ $index + 1 }}/{{ count($tickets) }} • In lúc {{ now()->format('d/m/Y H:i') }}</p>
            </div>

        </div>
    </div>
@endforeach
</body>
</html>
