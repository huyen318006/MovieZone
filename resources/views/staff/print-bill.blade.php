<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        // Tạo danh sách từng vé + ghế tương ứng (mỗi vé 1 trang)
        $ticketPages = $booking->tickets->map(function ($ticket) {
            return [
                'ticket' => $ticket,
                'seat' => $ticket->bookingSeat,
            ];
        });
    @endphp
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
            .page-break { page-break-after: always; }
            .page-break:last-child { page-break-after: avoid; }
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
    {{-- 1. PHIẾU NHẬN COMBO (Chỉ in nếu có mua combo) --}}
    @if($booking->bookingCombos->isNotEmpty())
        <div class="bill-container page-break">
            @include('staff.partials.print-bill-combo', ['booking' => $booking])
        </div>
    @endif

    {{-- 2. MỖI VÉ 1 TRANG RIÊNG (1 ghế / 1 vé) ═══ --}}
    @foreach($ticketPages as $index => $page)
        <div class="bill-container {{ $loop->last ? '' : 'page-break' }}">
            @include('staff.partials.print-bill-ticket', [
                'booking' => $booking,
                'ticket' => $page['ticket'],
                'seat' => $page['seat'],
                'pageIndex' => $index + 1,
                'totalPages' => $ticketPages->count(),
            ])
        </div>
    @endforeach

    @if($autoPrint)
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
    @endif
</body>
</html>
