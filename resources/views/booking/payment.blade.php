@extends('layout.app')

@section('content')

<section class="payment-page">
<div class="payment-wrapper">

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
        <div class="customer-info-breakdown" style="background: rgba(59, 130, 246, 0.05); padding: 15px; border-radius: 12px; margin-bottom: 20px; border: 1px solid rgba(59, 130, 246, 0.2);">
            <h4 style="margin-top: 0; margin-bottom: 12px; color: #60a5fa; font-size: 14px; text-transform: uppercase;">
                <i class="fa-solid fa-user"></i> Thông tin khách hàng
            </h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div>
                    <span style="display: block; color: #9ca3af; font-size: 12px;">Họ và Tên</span>
                    <strong style="color: #f8fafc; font-size: 14px;">{{ $order->getCustomerName() }}</strong>
                </div>
                <div>
                    <span style="display: block; color: #9ca3af; font-size: 12px;">Số điện thoại</span>
                    <strong style="color: #f8fafc; font-size: 14px;">{{ $order->getCustomerPhone() }}</strong>
                </div>
                <div style="grid-column: span 2;">
                    <span style="display: block; color: #9ca3af; font-size: 12px;">Email</span>
                    <strong style="color: #f8fafc; font-size: 14px;">{{ $order->getCustomerEmail() }}</strong>
                </div>
            </div>
        </div>

        {{-- Chi tiết giá ghế --}}
        <div class="seat-price-breakdown">
            <h4>Chi tiết giá vé</h4>
            @foreach($order->getBookingSeats() as $seat)
            <div class="price-row">
                <span>
                    @if($seat['type'] === 'vip') 👑
                    @elseif($seat['type'] === 'sweetbox') 💕
                    @else 🎬
                    @endif
                    {{ $seat['code'] }}
                    <small>({{ $seat['type'] === 'vip' ? 'VIP' : ($seat['type'] === 'sweetbox' ? 'Sweetbox' : 'Thường') }})</small>
                </span>
                <span>{{ number_format($seat['price'], 0, ',', '.') }}đ</span>
            </div>
            @endforeach

            @if(!empty($order->metadata['combos']))
            <h4 style="margin-top: 16px;">Combo</h4>
            @foreach($order->metadata['combos'] as $combo)
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
    <div class="payment-qr-panel" style="position: relative;">

        {{-- Expired overlay --}}
        <div class="payment-expired-overlay" id="expiredOverlay">
            <div class="expired-content">
                <i class="fa-solid fa-clock-rotate-left expired-icon-big"></i>
                <h3>Đơn hàng đã hết hạn</h3>
                <p>Vui lòng quay lại chọn ghế và thử lại</p>
                <a href="{{ route('home') }}" class="btn-back-seat">
                    <i class="fa-solid fa-arrow-left"></i> Chọn ghế lại
                </a>
            </div>
        </div>

        {{-- Timer --}}
        <div class="payment-timer-wrapper">
            <div class="payment-timer" id="timer">
                <i class="fa-solid fa-stopwatch"></i>
                <span id="timerText">15:00</span>
            </div>
        </div>

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
                    <button class="copy-btn-mz" onclick="copyText('{{ $bankAccount }}', this)">
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
                    <button class="copy-btn-mz" onclick="copyText('{{ $order->order_code }}', this)">
                        <i class="fa-solid fa-copy"></i>
                    </button>
                </span>
            </div>
        </div>

        {{-- Trạng thái --}}
        <div class="payment-status-box" id="statusSection">
            <div class="status-spinner-mz"></div>
            <div class="status-text-mz" id="statusText">Đang chờ thanh toán...</div>
            <div class="status-sub-mz" id="statusSubtext">Hệ thống tự động kiểm tra mỗi vài giây</div>
        </div>

        <a href="{{ route('home') }}" class="cancel-link-mz">
            <i class="fa-solid fa-arrow-left"></i> Huỷ và chọn ghế khác
        </a>
    </div>

</div>
</section>

<script>
    // ============================
    // Countdown Timer
    // ============================
    const expiresAt = new Date('{{ $expiresAt }}');
    const timerEl = document.getElementById('timer');
    const timerTextEl = document.getElementById('timerText');
    const expiredOverlay = document.getElementById('expiredOverlay');

    function updateTimer() {
        const now = new Date();
        const diff = expiresAt - now;

        if (diff <= 0) {
            timerTextEl.textContent = 'Hết hạn';
            timerEl.classList.add('timer-danger');
            expiredOverlay.classList.add('show');
            clearInterval(timerInterval);
            clearInterval(pollingInterval);
            return;
        }

        const minutes = Math.floor(diff / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);
        timerTextEl.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

        if (diff < 120000) {
            timerEl.classList.add('timer-danger');
        }
    }

    const timerInterval = setInterval(updateTimer, 1000);
    updateTimer();

    // ============================
    // Copy to Clipboard
    // ============================
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

    // ============================
    // Payment Polling
    // ============================
    const checkUrl = '{{ route("booking.check", $order->order_code) }}';
    const billUrl = '{{ route("booking.bill", $order->order_code) }}';
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
                    statusSubtext.textContent = 'Đang chuyển tới hoá đơn...';
                    document.getElementById('statusSection').classList.add('status-success');
                    clearInterval(pollingInterval);

                    setTimeout(() => {
                        window.location.href = billUrl;
                    }, 1500);
                } else if (data.status === 'expired') {
                    statusText.textContent = '⏰ Đơn hàng đã hết hạn';
                    statusSubtext.textContent = '';
                    clearInterval(pollingInterval);
                    expiredOverlay.classList.add('show');
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
