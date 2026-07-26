@extends('layout.staff')

@section('title', 'Thanh toán QR - Bán vé')
@section('page-title', 'Bán Vé — Thanh Toán QR')

@section('content')
    <div class="staff-payment-wrapper">

        <div class="payment-container">

            {{-- LEFT: Thông tin đơn hàng --}}
            <div class="payment-info-panel">
                <div class="panel-header">
                    <i class="fa-solid fa-ticket"></i>
                    <h2>Chi Tiết Đơn Hàng</h2>
                </div>

                <div class="order-badge">
                    <i class="fa-solid fa-hashtag"></i>
                    <span>{{ $order->order_code }}</span>
                </div>

                <div class="booking-detail-list">
                    <div class="detail-row">
                        <span class="detail-label"><i class="fa-solid fa-film"></i> Phim</span>
                        <span class="detail-value">{{ $order->getBookingInfo('movie_title') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label"><i class="fa-solid fa-door-open"></i> Phòng</span>
                        <span class="detail-value">{{ $order->getBookingInfo('room') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label"><i class="fa-solid fa-calendar"></i> Ngày</span>
                        <span class="detail-value">{{ $order->getBookingInfo('show_date') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label"><i class="fa-solid fa-clock"></i> Suất</span>
                        <span class="detail-value">{{ $order->getBookingInfo('showtime') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label"><i class="fa-solid fa-chair"></i> Ghế</span>
                        <span class="detail-value">{{ $order->getSeatCodesFormatted() }}</span>
                    </div>
                </div>

                {{-- Thông tin khách hàng --}}
                <div class="customer-info-box">
                    <h4><i class="fa-solid fa-user"></i> Thông tin khách hàng</h4>
                    <div class="customer-grid">
                        <div>
                            <span class="label">Họ và Tên</span>
                            <strong>{{ $order->getCustomerName() }}</strong>
                        </div>
                        <div>
                            <span class="label">Số điện thoại</span>
                            <strong>{{ $order->getCustomerPhone() }}</strong>
                        </div>
                        @if ($order->getCustomerEmail())
                            <div style="grid-column: span 2;">
                                <span class="label">Email</span>
                                <strong>{{ $order->getCustomerEmail() }}</strong>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Chi tiết giá --}}
                <div class="seat-price-breakdown">
                    <h4>Chi tiết giá vé</h4>
                    @foreach ($order->getBookingSeats() as $seat)
                        <div class="price-row">
                            <span>
                                @if ($seat['type'] === 'vip')
                                    👑
                                @elseif($seat['type'] === 'sweetbox')
                                    💕
                                @else
                                    🎬
                                @endif
                                {{ $seat['code'] }}
                            </span>
                            <span>{{ number_format($seat['price'], 0, ',', '.') }}đ</span>
                        </div>
                    @endforeach

                    @if (!empty($order->metadata['combos']))
                        <h4 style="margin-top: 16px;">Combo</h4>
                        @foreach ($order->metadata['combos'] as $combo)
                            <div class="price-row">
                                <span>🍿 {{ $combo['name'] }} x{{ $combo['quantity'] }}</span>
                                <span>{{ number_format($combo['total_price'], 0, ',', '.') }}đ</span>
                            </div>
                        @endforeach
                    @endif

                    <div class="price-total-row">
                        <span>Tổng cộng</span>
                        <span>{{ number_format($order->amount, 0, ',', '.') }}đ</span>
                    </div>
                </div>
            </div>

            {{-- RIGHT: QR + Thanh toán --}}
            <div class="payment-qr-panel">

                {{-- Số tiền --}}
                <div class="payment-amount-big">
                    <span class="amount-value">{{ number_format($order->amount, 0, ',', '.') }}</span>
                    <span class="amount-currency">VND</span>
                </div>

                {{-- QR Code --}}
                <div class="qr-box">
                    <div class="qr-frame">
                        <img src="{{ $qrUrl }}" alt="QR Code thanh toán" id="qrImage">
                    </div>
                    <p class="qr-hint">Quét mã QR bằng app ngân hàng</p>
                </div>

                {{-- Thông tin CK --}}
                <div class="bank-transfer-info">
                    <div class="transfer-row">
                        <span>Ngân hàng</span>
                        <span class="fw-bold">{{ $bankCode }}</span>
                    </div>
                    <div class="transfer-row">
                        <span>Số tài khoản</span>
                        <span class="fw-bold">
                            {{ $bankAccount }}
                            <button class="copy-btn" onclick="copyText('{{ $bankAccount }}', this)">
                                <i class="fa-solid fa-copy"></i>
                            </button>
                        </span>
                    </div>
                    <div class="transfer-row">
                        <span>Số tiền</span>
                        <span class="fw-bold">{{ number_format($order->amount, 0, ',', '.') }}đ</span>
                    </div>
                    <div class="transfer-row">
                        <span>Nội dung CK</span>
                        <span class="fw-bold">
                            {{ $order->order_code }}
                            <button class="copy-btn" onclick="copyText('{{ $order->order_code }}', this)">
                                <i class="fa-solid fa-copy"></i>
                            </button>
                        </span>
                    </div>
                </div>

                {{-- Trạng thái --}}
                <div class="payment-status-box" id="statusSection">
                    <div class="status-spinner"></div>
                    <div class="status-text" id="statusText">Đang chờ thanh toán...</div>
                    <div class="status-sub" id="statusSubtext">Hệ thống tự động kiểm tra mỗi vài giây</div>
                </div>

                <a href="{{ route('staff.sell-tickets') }}" class="cancel-link">
                    <i class="fa-solid fa-arrow-left"></i> Huỷ và quay lại
                </a>
            </div>

        </div>

    </div>

    <style>
        .staff-payment-wrapper {
            padding: 20px;
        }

        .payment-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .payment-info-panel,
        .payment-qr-panel {
            background: #1e293b;
            border-radius: 16px;
            padding: 30px;
            border: 1px solid #334155;
        }

        .panel-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .panel-header i {
            color: #3b82f6;
            font-size: 24px;
        }

        .panel-header h2 {
            margin: 0;
            font-size: 22px;
            color: #f8fafc;
            font-weight: 700;
        }

        .order-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            padding: 8px 16px;
            border-radius: 8px;
            color: #60a5fa;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .booking-detail-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 20px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 14px;
            background: #111827;
            border-radius: 8px;
            border: 1px solid #1f2937;
        }

        .detail-label {
            color: #9ca3af;
            font-size: 14px;
        }

        .detail-label i {
            color: #3b82f6;
            margin-right: 6px;
        }

        .detail-value {
            color: #f8fafc;
            font-weight: 600;
            font-size: 14px;
        }

        .customer-info-box {
            background: rgba(59, 130, 246, 0.05);
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .customer-info-box h4 {
            margin: 0 0 12px;
            color: #60a5fa;
            font-size: 14px;
            text-transform: uppercase;
        }

        .customer-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .customer-grid .label {
            display: block;
            color: #9ca3af;
            font-size: 12px;
        }

        .customer-grid strong {
            color: #f8fafc;
            font-size: 14px;
        }

        .seat-price-breakdown {
            background: #111827;
            padding: 16px;
            border-radius: 10px;
            border: 1px solid #1f2937;
        }

        .seat-price-breakdown h4 {
            margin: 0 0 12px;
            color: #9ca3af;
            font-size: 13px;
            text-transform: uppercase;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            color: #cbd5e1;
            font-size: 14px;
        }

        .price-total-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0 0;
            margin-top: 10px;
            border-top: 1px solid #374151;
            color: #ef4444;
            font-weight: 700;
            font-size: 18px;
        }

        /* QR Panel */
        .payment-amount-big {
            text-align: center;
            margin-bottom: 20px;
        }

        .amount-value {
            font-size: 42px;
            font-weight: 800;
            color: #f8fafc;
            letter-spacing: -1px;
        }

        .amount-currency {
            font-size: 20px;
            color: #9ca3af;
            margin-left: 6px;
        }

        .qr-box {
            text-align: center;
            margin-bottom: 24px;
        }

        .qr-frame {
            display: inline-block;
            padding: 16px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
        }

        .qr-frame img {
            width: 220px;
            height: 220px;
            display: block;
        }

        .qr-hint {
            color: #9ca3af;
            font-size: 14px;
            margin-top: 12px;
        }

        .bank-transfer-info {
            background: #111827;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
            border: 1px solid #1f2937;
        }

        .transfer-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #1f2937;
            color: #cbd5e1;
            font-size: 14px;
        }

        .transfer-row:last-child {
            border-bottom: none;
        }

        .fw-bold {
            font-weight: 600;
            color: #f8fafc;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .copy-btn {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #60a5fa;
            padding: 4px 8px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 12px;
        }

        .copy-btn:hover {
            background: rgba(59, 130, 246, 0.2);
        }

        .copy-btn.copied {
            background: rgba(34, 197, 94, 0.1);
            border-color: rgba(34, 197, 94, 0.3);
            color: #22c55e;
        }

        .payment-status-box {
            text-align: center;
            padding: 20px;
            background: #111827;
            border-radius: 12px;
            border: 1px solid #1f2937;
            margin-bottom: 16px;
        }

        .status-spinner {
            width: 28px;
            height: 28px;
            border: 3px solid #334155;
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 12px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .status-text {
            color: #f8fafc;
            font-size: 16px;
            font-weight: 600;
        }

        .status-sub {
            color: #6b7280;
            font-size: 13px;
            margin-top: 4px;
        }

        .payment-status-box.status-success {
            background: rgba(34, 197, 94, 0.1);
            border-color: rgba(34, 197, 94, 0.3);
        }

        .payment-status-box.status-success .status-spinner {
            display: none;
        }

        .payment-status-box.status-success .status-text {
            color: #22c55e;
        }

        .cancel-link {
            display: block;
            text-align: center;
            color: #9ca3af;
            font-size: 14px;
            text-decoration: none;
            padding: 10px;
            transition: color 0.2s;
        }

        .cancel-link:hover {
            color: #f8fafc;
        }

        @media (max-width: 768px) {
            .payment-container {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        // Copy to Clipboard
        function copyText(text, btn) {
            navigator.clipboard.writeText(text).then(() => {
                const icon = btn.querySelector('i');
                icon.className = 'fa-solid fa-check';
                btn.classList.add('copied');
                setTimeout(() => {
                    icon.className = 'fa-solid fa-copy';
                    btn.classList.remove('copied');
                }, 2000);
            });
        }

        // Payment Polling — Reuse logic từ Customer payment.blade.php
        const checkUrl = '{{ route('booking.check', $order->order_code) }}';
        const billUrl = '{{ route('staff.print-bill', $order->booking->booking_code ?? '') }}';
        const pollMs = {{ $pollingInterval }};

        const statusText = document.getElementById('statusText');
        const statusSubtext = document.getElementById('statusSubtext');

        let pollCount = 0;

        function checkPaymentStatus() {
            pollCount++;

            fetch(checkUrl)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'paid') {
                        statusText.textContent = '✅ Thanh toán thành công!';
                        statusSubtext.textContent = 'Đang chuyển tới hóa đơn...';
                        document.getElementById('statusSection').classList.add('status-success');
                        clearInterval(pollingInterval);

                        setTimeout(() => {
                            window.location.href = billUrl;
                        }, 1500);
                    } else if (data.status === 'expired') {
                        statusText.textContent = '⏰ Đơn hàng đã hết hạn';
                        statusSubtext.textContent = 'Vui lòng đặt vé lại.';
                        clearInterval(pollingInterval);
                    } else {
                        const dots = '.'.repeat((pollCount % 3) + 1);
                        statusText.textContent = `Đang chờ thanh toán${dots}`;
                        statusSubtext.textContent = `Kiểm tra lần ${pollCount} • Tự động mỗi ${pollMs/1000}s`;
                    }
                })
                .catch(error => {
                    console.error('Polling error:', error);
                    statusSubtext.textContent = 'Lỗi kết nối, đang thử lại...';
                });
        }

        const pollingInterval = setInterval(checkPaymentStatus, pollMs);
        setTimeout(checkPaymentStatus, 3000);
    </script>

@endsection
