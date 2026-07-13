<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $singleTicket = null;
        $singleSeat = null;
        if (!empty($singleTicketCode)) {
            $singleTicket = $booking->tickets->firstWhere('ticket_code', $singleTicketCode);
            if ($singleTicket) {
                $singleSeat = $singleTicket->bookingSeat;
            }
        }
        $isSingle = !is_null($singleTicket);
        $displaySeats = $isSingle && $singleSeat ? collect([$singleSeat]) : ($isSingle ? collect() : $booking->bookingSeats);
    @endphp
    <title>{{ $isSingle ? "Vé {$singleTicket->ticket_code}" : "Hoá đơn {$booking->booking_code}" }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #fff;
            color: #1a1a1a;
            font-size: 13px;
            line-height: 1.5;
        }
        .bill-container {
            max-width: 680px;
            margin: 0 auto;
            padding: 16px;
        }

        /* Header */
        .bill-header {
            text-align: center;
            padding-bottom: 12px;
            border-bottom: 2px solid #333;
            margin-bottom: 14px;
        }
        .bill-header h1 {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 2px;
            margin-bottom: 2px;
        }
        .bill-header .subtitle {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .bill-header .booking-code {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            font-weight: 700;
            margin-top: 6px;
            letter-spacing: 2px;
        }

        /* Section titles */
        .section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 4px;
            margin-bottom: 8px;
            margin-top: 14px;
        }

        /* Info grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 20px;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
        }
        .info-label { color: #666; font-size: 12px; }
        .info-value { font-weight: 600; font-size: 12px; text-align: right; }

        /* Movie section */
        .movie-name {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        /* Seats table */
        .seats-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        .seats-table th {
            background: #f5f5f5;
            padding: 5px 8px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #555;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .seats-table td {
            padding: 4px 8px;
            font-size: 12px;
            border-bottom: 1px solid #eee;
        }
        .seats-table .seat-type {
            text-transform: capitalize;
        }
        .seats-table .seat-price { text-align: right; }

        /* Combos */
        .combo-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            font-size: 12px;
        }

        /* Total */
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            margin-top: 8px;
            border-top: 2px solid #333;
            font-size: 16px;
            font-weight: 800;
        }

        /* QR Section */
        .qr-section {
            text-align: center;
            padding: 12px 0;
            margin-top: 10px;
            border-top: 1px dashed #ccc;
        }
        .qr-section img {
            width: 120px;
            height: 120px;
            display: block;
            margin: 0 auto 6px;
        }
        .qr-section .qr-code-text {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 2px;
        }
        .qr-section .qr-hint {
            font-size: 10px;
            color: #888;
            margin-top: 2px;
        }

        /* Transaction info */
        .transaction-info {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid #ddd;
        }
        .trans-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
            font-size: 11px;
            color: #666;
        }
        .trans-row .val { color: #333; font-weight: 500; }

        /* Footer */
        .bill-footer {
            text-align: center;
            padding-top: 10px;
            margin-top: 10px;
            border-top: 1px solid #eee;
            font-size: 10px;
            color: #999;
        }

        /* Print styles */
        @media print {
            @page { margin: 8mm; size: A4; }
            body { font-size: 12px; }
            .bill-container { max-width: 100%; padding: 0; }
            .no-print { display: none !important; }
        }

        /* Screen-only styles */
        @media screen {
            body { background: #f0f0f0; padding: 20px 0; }
            .bill-container {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="bill-container">
        {{-- Header --}}
        <div class="bill-header">
            <h1>MOVIEZONE</h1>
            @if($isSingle)
                <div class="subtitle">Vé xem phim</div>
                <div class="booking-code">{{ $singleTicket->ticket_code }}</div>
            @else
                <div class="subtitle">Hoá đơn đặt vé phim</div>
                <div class="booking-code">{{ $booking->booking_code }}</div>
            @endif
        </div>

        {{-- Movie --}}
        <div class="movie-name">
            🎬 {{ $booking->showtime?->movie?->title ?? 'N/A' }}
        </div>

        {{-- Showtime & Cinema Info --}}
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Rạp</span>
                <span class="info-value">{{ $booking->showtime?->cinema?->name ?? 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Phòng</span>
                <span class="info-value">{{ $booking->showtime?->room?->name ?? 'N/A' }} {{ $booking->showtime?->room?->room_type ? '('.$booking->showtime->room->room_type.')' : '' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Ngày chiếu</span>
                <span class="info-value">{{ $booking->showtime?->start_time ? $booking->showtime->start_time->format('d/m/Y') : 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Suất chiếu</span>
                <span class="info-value">{{ $booking->showtime?->start_time ? $booking->showtime->start_time->format('H:i') : 'N/A' }} - {{ $booking->showtime?->end_time ? $booking->showtime->end_time->format('H:i') : '' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Định dạng</span>
                <span class="info-value">{{ $booking->showtime?->format ?? 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Trạng thái</span>
                <span class="info-value" style="color: {{ $booking->status === 'PAID' ? '#059669' : '#dc2626' }};">
                    {{ $booking->status === 'PAID' ? '✓ Đã thanh toán' : $booking->status }}
                </span>
            </div>
        </div>

        {{-- Customer --}}
        <div class="section-title">👤 Khách hàng</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Họ tên</span>
                <span class="info-value">{{ $booking->user?->name ?? $booking->customer_name ?? 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">SĐT</span>
                <span class="info-value">{{ $booking->user?->phone ?? $booking->customer_phone ?? 'N/A' }}</span>
            </div>
            <div class="info-item" style="grid-column: span 2;">
                <span class="info-label">Email</span>
                <span class="info-value">{{ $booking->user?->email ?? $booking->customer_email ?? 'N/A' }}</span>
            </div>
        </div>

        {{-- Seats --}}
        @if($isSingle)
            <div class="section-title">🎟️ Ghế</div>
        @else
            <div class="section-title">🎟️ Chi tiết vé ({{ $booking->bookingSeats->count() }} ghế)</div>
        @endif
        <table class="seats-table">
            <thead>
                <tr>
                    <th>Ghế</th>
                    <th>Loại</th>
                    @if($isSingle)
                        <th>Mã vé</th>
                    @endif
                    <th style="text-align:right;">Giá</th>
                </tr>
            </thead>
            <tbody>
                @foreach($displaySeats as $seat)
                <tr>
                    <td><strong>{{ $seat->seat_code }}</strong></td>
                    <td class="seat-type">
                        @if($seat->seat_type === 'vip')
                            👑 VIP
                        @elseif($seat->seat_type === 'sweetbox')
                            💕 Sweetbox
                        @else
                            🎬 Thường
                        @endif
                    </td>
                    @if($isSingle)
                        <td style="font-family:'Courier New',monospace; font-size:11px;">{{ $singleTicket->ticket_code }}</td>
                    @endif
                    <td class="seat-price">{{ number_format($seat->price, 0, ',', '.') }}đ</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Combos (only in full booking mode) --}}
        @if(!$isSingle && $booking->bookingCombos->isNotEmpty())
        <div class="section-title">🍿 Combo</div>
        @foreach($booking->bookingCombos as $bc)
        <div class="combo-row">
            <span>{{ $bc->combo?->name ?? 'Combo' }} x{{ $bc->quantity }}</span>
            <span>{{ number_format($bc->total_price, 0, ',', '.') }}đ</span>
        </div>
        @endforeach
        @endif

        {{-- Total --}}
        @if($isSingle)
            <div class="total-row">
                <span>Giá vé</span>
                <span>{{ number_format($singleSeat->price ?? 0, 0, ',', '.') }}đ</span>
            </div>
        @else
            <div class="total-row">
                <span>Tổng thanh toán</span>
                <span>{{ number_format($booking->final_amount, 0, ',', '.') }}đ</span>
            </div>
        @endif

        {{-- QR Code --}}
        <div class="qr-section">
            @if($isSingle)
                @if($singleTicket->qr_code)
                    <img src="{{ asset('storage/' . $singleTicket->qr_code) }}"
                         alt="QR {{ $singleTicket->ticket_code }}"
                         onerror="this.src='https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($singleTicket->ticket_code) }}&color=0f172a&bgcolor=ffffff&margin=4'">
                @else
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($singleTicket->ticket_code) }}&color=0f172a&bgcolor=ffffff&margin=4"
                         alt="QR {{ $singleTicket->ticket_code }}">
                @endif
                <div class="qr-code-text">{{ $singleTicket->ticket_code }}</div>
                <div class="qr-hint">Ghế {{ $singleSeat?->seat_code ?? 'N/A' }} • Đưa mã QR này cho nhân viên tại rạp</div>
            @else
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($booking->booking_code) }}&color=0f172a&bgcolor=ffffff&margin=4"
                     alt="QR {{ $booking->booking_code }}">
                <div class="qr-code-text">{{ $booking->booking_code }}</div>
                <div class="qr-hint">Đưa mã QR này cho nhân viên tại rạp để check-in</div>
            @endif
        </div>

        {{-- Transaction --}}
        <div class="transaction-info">
            @if($isSingle)
            <div class="trans-row">
                <span>Mã Booking</span>
                <span class="val">{{ $booking->booking_code }}</span>
            </div>
            @endif
            @if($booking->payment)
            <div class="trans-row">
                <span>Phương thức TT</span>
                <span class="val">{{ $booking->payment->payment_method ?? 'N/A' }}</span>
            </div>
            <div class="trans-row">
                <span>Mã giao dịch</span>
                <span class="val">{{ $booking->payment->transaction_code ?? 'N/A' }}</span>
            </div>
            @endif
            <div class="trans-row">
                <span>Thời gian đặt</span>
                <span class="val">{{ $booking->created_at->format('d/m/Y H:i:s') }}</span>
            </div>
            <div class="trans-row">
                <span>Thời gian TT</span>
                <span class="val">{{ $booking->paid_at ? \Carbon\Carbon::parse($booking->paid_at)->format('d/m/Y H:i:s') : '—' }}</span>
            </div>
        </div>

        {{-- Footer --}}
        <div class="bill-footer">
            Powered by MovieZone • In lúc {{ now()->format('d/m/Y H:i:s') }}
        </div>
    </div>

    @if($autoPrint)
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
    @endif
</body>
</html>
