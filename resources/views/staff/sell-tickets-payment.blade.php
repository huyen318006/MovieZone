@extends('layout.staff')

@section('title', 'Thanh toán QR - Bán vé')
@section('page-title', 'Bán Vé — Thanh Toán QR')

@section('content')
    <div class="staff-payment-wrapper">
        <div class="payment-shell">
            <div class="payment-card payment-summary-card">
                <div class="card-header">
                    <div class="icon-badge"><i class="bi bi-ticket-perforated"></i></div>
                    <div>
                        <h2>Chi tiết đơn hàng</h2>
                        <p>Mã đơn: {{ $order->order_code }}</p>
                    </div>
                </div>

                <div class="order-chip">
                    <i class="bi bi-hash"></i>
                    <span>{{ $order->order_code }}</span>
                </div>

                <div class="detail-list">
                    <div class="detail-row">
                        <span class="detail-label"><i class="bi bi-film"></i> Phim</span>
                        <span class="detail-value">{{ $order->getBookingInfo('movie_title') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label"><i class="bi bi-door-open"></i> Phòng</span>
                        <span class="detail-value">{{ $order->getBookingInfo('room') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label"><i class="bi bi-calendar3"></i> Ngày</span>
                        <span class="detail-value">{{ $order->getBookingInfo('show_date') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label"><i class="bi bi-clock"></i> Suất</span>
                        <span class="detail-value">{{ $order->getBookingInfo('showtime') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label"><i class="bi bi-chair"></i> Ghế</span>
                        <span class="detail-value">{{ $order->getSeatCodesFormatted() }}</span>
                    </div>
                </div>

                <div class="customer-box">
                    <h3><i class="bi bi-person"></i> Thông tin khách hàng</h3>
                    <div class="customer-grid">
                        <div>
                            <span class="label">Họ và tên</span>
                            <strong>{{ $order->getCustomerName() }}</strong>
                        </div>
                        <div>
                            <span class="label">Số điện thoại</span>
                            <strong>{{ $order->getCustomerPhone() }}</strong>
                        </div>
                        @if ($order->getCustomerEmail())
                            <div class="full-width">
                                <span class="label">Email</span>
                                <strong>{{ $order->getCustomerEmail() }}</strong>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="price-box">
                    <h3><i class="bi bi-cash-stack"></i> Chi tiết giá</h3>
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

                    @php $combos = data_get($order->metadata ?? [], 'combos', []); @endphp
                    @if (!empty($combos))
                        <h4>Combo</h4>
                        @foreach ($combos as $combo)
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

            <div class="payment-card payment-qr-card">
                <div class="amount-pill">
                    <span class="amount-label">Số tiền cần thanh toán</span>
                    <div class="amount-value">{{ number_format($order->amount, 0, ',', '.') }} <span>VND</span></div>
                </div>

                <div class="qr-box">
                    <div class="qr-frame">
                        <img src="{{ $qrUrl }}" alt="QR Code thanh toán" id="qrImage">
                    </div>
                    <p class="qr-hint">Quét mã QR bằng app ngân hàng hoặc ví điện tử</p>
                </div>

                <div class="bank-transfer-info">
                    <div class="transfer-row">
                        <span>Ngân hàng</span>
                        <span class="fw-bold">{{ $bankCode }}</span>
                    </div>
                    <div class="transfer-row">
                        <span>Số tài khoản</span>
                        <span class="fw-bold">
                            {{ $bankAccount }}
                            <button type="button" class="copy-btn" onclick="copyText('{{ $bankAccount }}', this)">
                                <i class="bi bi-clipboard"></i>
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
                            <button type="button" class="copy-btn" onclick="copyText('{{ $order->order_code }}', this)">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </span>
                    </div>
                </div>

                <div class="payment-status-box" id="statusSection">
                    <div class="status-spinner"></div>
                    <div class="status-text" id="statusText">Đang chờ thanh toán...</div>
                    <div class="status-sub" id="statusSubtext">Hệ thống tự động kiểm tra mỗi vài giây</div>
                </div>

                <div class="action-row">
                    <a href="{{ \App\Helpers\TabAuthHelper::route('staff.sell-tickets') }}" class="cancel-link">
                        <i class="bi bi-arrow-left"></i> Huỷ và quay lại
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .staff-payment-wrapper {
            padding: 0;
        }

        .payment-shell {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 24px;
            align-items: start;
        }

        .payment-card {
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.95));
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 18px 45px rgba(2, 6, 23, 0.28);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
        }

        .icon-badge {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--staff-primary), #6d28d9);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 22px;
        }

        .card-header h2,
        .customer-box h3,
        .price-box h3 {
            margin: 0 0 4px;
            font-size: 18px;
            font-weight: 700;
            color: #f8fafc;
        }

        .card-header p {
            margin: 0;
            color: var(--staff-text-muted);
            font-size: 13px;
        }

        .order-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(139, 92, 246, 0.16);
            border: 1px solid rgba(139, 92, 246, 0.25);
            color: #ddd6fe;
            padding: 8px 12px;
            border-radius: 999px;
            font-weight: 600;
            margin-bottom: 18px;
        }

        .detail-list,
        .customer-box,
        .price-box {
            margin-bottom: 18px;
        }

        .detail-row,
        .price-row,
        .transfer-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(148, 163, 184, 0.12);
            color: #e2e8f0;
        }

        .detail-row:last-child,
        .price-row:last-child,
        .transfer-row:last-child {
            border-bottom: 0;
        }

        .detail-label,
        .label {
            color: var(--staff-text-muted);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .detail-value,
        .customer-grid strong {
            text-align: right;
            color: #f8fafc;
            font-weight: 600;
        }

        .customer-box,
        .price-box {
            background: rgba(15, 23, 42, 0.55);
            border: 1px solid rgba(148, 163, 184, 0.14);
            border-radius: 16px;
            padding: 16px;
        }

        .customer-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .customer-grid .full-width {
            grid-column: 1 / -1;
        }

        .price-box h4 {
            margin: 12px 0 8px;
            color: #cbd5e1;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .price-total-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-top: 10px;
            padding-top: 12px;
            border-top: 1px solid rgba(148, 163, 184, 0.2);
            font-size: 16px;
            font-weight: 700;
            color: #f8fafc;
        }

        .amount-pill {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.20), rgba(59, 130, 246, 0.16));
            border: 1px solid rgba(129, 140, 248, 0.25);
            border-radius: 18px;
            padding: 18px;
            text-align: center;
            margin-bottom: 16px;
        }

        .amount-label {
            display: block;
            color: var(--staff-text-muted);
            font-size: 13px;
            margin-bottom: 6px;
        }

        .amount-value {
            font-size: 34px;
            font-weight: 800;
            color: #fff;
        }

        .amount-value span {
            font-size: 16px;
            color: #cbd5e1;
            margin-left: 6px;
        }

        .qr-box {
            background: #fff;
            border-radius: 18px;
            padding: 16px;
            text-align: center;
            margin-bottom: 16px;
        }

        .qr-frame img {
            width: 230px;
            max-width: 100%;
            height: auto;
            border-radius: 12px;
        }

        .qr-hint {
            margin: 10px 0 0;
            color: #334155;
            font-size: 13px;
        }

        .bank-transfer-info {
            background: rgba(15, 23, 42, 0.55);
            border: 1px solid rgba(148, 163, 184, 0.14);
            border-radius: 16px;
            padding: 14px 16px;
            margin-bottom: 16px;
        }

        .copy-btn {
            margin-left: 8px;
            border: 0;
            border-radius: 8px;
            background: rgba(139, 92, 246, 0.16);
            color: #ddd6fe;
            padding: 4px 8px;
            cursor: pointer;
        }

        .copy-btn.copied {
            background: rgba(16, 185, 129, 0.18);
            color: #bbf7d0;
        }

        .payment-status-box {
            background: rgba(59, 130, 246, 0.12);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 16px;
            padding: 14px 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 6px;
            margin-bottom: 16px;
        }

        .status-spinner {
            width: 22px;
            height: 22px;
            border: 3px solid rgba(255,255,255,0.2);
            border-top-color: #60a5fa;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .status-text {
            font-weight: 700;
            color: #f8fafc;
        }

        .status-sub {
            color: var(--staff-text-muted);
            font-size: 13px;
        }

        .payment-status-box.status-success {
            background: rgba(16, 185, 129, 0.14);
            border-color: rgba(16, 185, 129, 0.26);
        }

        .action-row {
            display: flex;
            justify-content: center;
        }

        .cancel-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 999px;
            color: #cbd5e1;
            text-decoration: none;
            background: rgba(148, 163, 184, 0.12);
            transition: all 0.2s ease;
        }

        .cancel-link:hover {
            color: #fff;
            background: rgba(148, 163, 184, 0.2);
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 1024px) {
            .payment-shell {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .payment-card {
                padding: 18px;
            }

            .customer-grid {
                grid-template-columns: 1fr;
            }

            .detail-row,
            .price-row,
            .transfer-row {
                flex-direction: column;
                align-items: flex-start;
            }

            .detail-value {
                text-align: left;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        function copyText(text, btn) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(() => {
                    const icon = btn.querySelector('i');
                    if (icon) {
                        icon.className = 'bi bi-check2';
                    }
                    btn.classList.add('copied');
                    setTimeout(() => {
                        if (icon) {
                            icon.className = 'bi bi-clipboard';
                        }
                        btn.classList.remove('copied');
                    }, 2000);
                }).catch(() => {
                    btn.classList.add('copied');
                });
            }
        }

        const checkUrl = '{{ route("booking.check", $order->order_code) }}';
        const billUrl = '{{ \App\Helpers\TabAuthHelper::route("staff.print-bill", $order->booking->booking_code ?? "") }}';
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
@endpush
