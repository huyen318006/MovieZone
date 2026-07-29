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

            <a href="{{ \App\Helpers\TabAuthHelper::route('staff.sell-tickets') }}" class="cancel-link">
                <i class="fa-solid fa-arrow-left"></i> Huỷ và quay lại
            </a>
        </div>

    </div>

    <style>
        .staff-payment-wrapper {
            padding: 20px;
        }

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

        // Payment Polling — Reuse logic từ Customer payment.blade.php
        const checkUrl = '{{ route('booking.check', $order->order_code) }}';
        const billUrl = '{{ route('staff.print-bill', $order->booking?->booking_code ?? $order->order_code) }}';
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
