<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hoá đơn {{ $booking->booking_code }}</title>
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
        .seats-table .seat-type { text-transform: capitalize; }
        .seats-table .seat-price { text-align: right; }
        .seats-table tfoot td {
            font-weight: 700;
            border-top: 2px solid #333;
            padding-top: 6px;
        }

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
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="bill-container">
        {{-- Header --}}
        <div class="bill-header">
            <h1>MOVIEZONE</h1>
            <div class="subtitle">Hoá đơn đặt vé</div>
            <div class="booking-code">{{ $booking->booking_code }}</div>
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

        {{-- All Tickets --}}
        <div class="section-title">🎟️ Chi tiết vé ({{ $booking->tickets->count() }} vé)</div>
        <table class="seats-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Ghế</th>
                    <th>Loại</th>
                    <th>Mã vé</th>
                    <th style="text-align:right;">Giá</th>
                </tr>
            </thead>
            <tbody>
                @php $totalTicketPrice = 0; @endphp
                @foreach($booking->tickets as $index => $ticket)
                    @php
                        $seat = $ticket->bookingSeat;
                        $ticketPrice = $seat?->price ?? 0;
                        $totalTicketPrice += $ticketPrice;
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><strong>{{ $seat?->seat_code ?? 'N/A' }}</strong></td>
                        <td class="seat-type">
                            @if($seat?->seat_type === 'vip' || $seat?->seat_type === 'VIP')
                                👑 VIP
                            @elseif($seat?->seat_type === 'sweetbox' || $seat?->seat_type === 'COUPLE')
                                💕 Sweetbox
                            @else
                                🎬 Thường
                            @endif
                        </td>
                        <td style="font-family:'Courier New',monospace; font-size:11px;">{{ $ticket->ticket_code }}</td>
                        <td class="seat-price">{{ number_format($ticketPrice, 0, ',', '.') }}đ</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align:right;">Tổng vé:</td>
                    <td class="seat-price">{{ number_format($totalTicketPrice, 0, ',', '.') }}đ</td>
                </tr>
            </tfoot>
        </table>

        {{-- Combos --}}
        @if($booking->bookingCombos && $booking->bookingCombos->count() > 0)
            <div class="section-title">🍿 Combo / Bắp nước</div>
            @php $totalComboPrice = 0; @endphp
            @foreach($booking->bookingCombos as $bookingCombo)
                @php $totalComboPrice += $bookingCombo->total_price ?? 0; @endphp
                <div class="combo-row">
                    <span>{{ $bookingCombo->combo?->name ?? 'Combo' }} x{{ $bookingCombo->quantity }}</span>
                    <span style="font-weight:600;">{{ number_format($bookingCombo->total_price ?? 0, 0, ',', '.') }}đ</span>
                </div>
            @endforeach
            <div class="combo-row" style="border-top:1px solid #ddd; padding-top:4px; margin-top:4px; font-weight:600;">
                <span>Tổng combo:</span>
                <span>{{ number_format($totalComboPrice, 0, ',', '.') }}đ</span>
            </div>
        @endif

        {{-- Total --}}
        <div class="total-row">
            <span>TỔNG THANH TOÁN</span>
            <span>{{ number_format($booking->final_amount ?? 0, 0, ',', '.') }}đ</span>
        </div>

        {{-- Booking code section --}}
        <div style="text-align: center; padding: 10px 0; margin-top: 10px; border-top: 1px dashed #ccc;">
            <div style="font-size: 11px; color: #666; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Mã Booking</div>
            <div style="font-family: 'Courier New', monospace; font-size: 14px; font-weight: 700; letter-spacing: 2px;">{{ $booking->booking_code }}</div>
            <div style="font-size: 10px; color: #888; margin-top: 2px;">{{ $booking->tickets->count() }} vé — {{ $booking->bookingSeats->pluck('seat_code')->implode(', ') }}</div>
        </div>

        {{-- Transaction --}}
        <div class="transaction-info">
            <div class="trans-row">
                <span>Mã Booking</span>
                <span class="val">{{ $booking->booking_code }}</span>
            </div>
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
