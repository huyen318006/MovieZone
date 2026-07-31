<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hóa đơn {{ $invoice->invoice_code }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; }
        .header { border-bottom: 3px solid #198754; padding-bottom: 14px; }
        .brand { color: #198754; font-size: 24px; font-weight: bold; }
        .muted { color: #6b7280; }
        .section { margin-top: 22px; }
        .info { width: 100%; border-collapse: collapse; }
        .info td { padding: 5px 0; vertical-align: top; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .items th { background: #198754; color: #fff; padding: 9px 7px; text-align: left; }
        .items td { border-bottom: 1px solid #d1d5db; padding: 9px 7px; }
        .right { text-align: right; }
        .total { margin-top: 18px; width: 100%; border-top: 2px solid #198754; padding-top: 12px; }
        .total td { font-size: 16px; font-weight: bold; }
        .footer { margin-top: 35px; text-align: center; color: #6b7280; }
        @media print {
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">MovieZone</div>
        <div>HÓA ĐƠN BÁN SẢN PHẨM</div>
    </div>

    <div class="section">
        <table class="info">
            <tr>
                <td><strong>Mã hóa đơn:</strong> {{ $invoice->invoice_code }}</td>
                <td class="right"><strong>Ngày thanh toán:</strong> {{ $invoice->paid_at?->format('d/m/Y H:i:s') ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Khách hàng:</strong> {{ $invoice->customer_name ?: 'Khách lẻ' }}</td>
                <td class="right"><strong>Thanh toán:</strong> {{ $invoice->payment_method }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <table class="items">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Sản phẩm / Combo</th>
                    <th class="right">SL</th>
                    <th class="right">Đơn giá</th>
                    <th class="right">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item['name'] ?? 'Sản phẩm' }}</td>
                        <td class="right">{{ $item['quantity'] ?? 0 }}</td>
                        <td class="right">{{ number_format($item['price'] ?? 0, 0, ',', '.') }} đ</td>
                        <td class="right">{{ number_format($item['total'] ?? 0, 0, ',', '.') }} đ</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <table class="total">
        <tr>
            <td>Tổng thanh toán</td>
            <td class="right">{{ number_format($invoice->total_amount, 0, ',', '.') }} đ</td>
        </tr>
    </table>

    <div class="footer">Cảm ơn quý khách đã sử dụng dịch vụ MovieZone.</div>

    @if($autoPrint ?? false)
        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    @endif
</body>
</html>
