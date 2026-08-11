@extends('layout.app')

@section('content')

{{-- COUNTDOWN TIMER THANH TOÁN (timer riêng 5 phút từ lúc tạo đơn) --}}
@include('booking._payment_countdown', ['expiresAt' => $expiresAt])

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

        {{-- Timer cũ 15 phút đã được thay bằng countdown chung ở đầu trang --}}

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

        <form action="{{ \App\Helpers\TabAuthHelper::route('booking.cancel', $order->order_code) }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" class="cancel-link-mz" onclick="return confirm('Bạn có chắc muốn hủy đơn hàng và chọn ghế khác?')">
                <i class="fa-solid fa-arrow-left"></i> Huỷ và chọn ghế khác
            </button>
        </form>
    </div>

</div>
</section>

<script>
    const releaseOnBackUrl = '{{ \App\Helpers\TabAuthHelper::route('booking.releaseOnBack') }}';
    const paymentShowtimeId = '{{ $order->getBookingInfo('showtime_id') ?? null }}';
    const paymentSeatIds = @json($order->getBookingSeats() ? collect($order->getBookingSeats())->map(fn($s) => $s['showtime_seat_id'] ?? null)->filter()->values()->all() : []);

    function releaseBookingSessionOnBack() {
        if (!paymentShowtimeId || !paymentSeatIds.length) {
            return;
        }

        const payload = {
            showtime_id: paymentShowtimeId,
            seat_ids: paymentSeatIds
        };

        const init = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify(payload),
            keepalive: true
        };

        try {
            fetch(releaseOnBackUrl, {
                ...init,
                keepalive: true,
                credentials: 'same-origin'
            });
        } catch (e) {
            console.log('Release-on-back fallback error:', e);
        }
    }

    window.addEventListener('pagehide', releaseBookingSessionOnBack);
    window.addEventListener('beforeunload', releaseBookingSessionOnBack);

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
    // Payment Polling (giữ nguyên)
    // ============================
    const checkUrl = '{{ \App\Helpers\TabAuthHelper::route("booking.check", $order->order_code) }}';
    const billUrl = '{{ \App\Helpers\TabAuthHelper::route("booking.bill", $order->order_code) }}';
    const pollMs = {{ $pollingInterval }};

    const statusText = document.getElementById('statusText');
    const statusSubtext = document.getElementById('statusSubtext');

    let pollCount = 0;
    let paymentExpired = false;

    function checkPaymentStatus() {
        if (paymentExpired) return; // Không kiểm tra nữa nếu đã hết hạn

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

    // ============================
    // Đóng QR khi hết 5 phút countdown
    // ============================
    window.addEventListener('countdownExpired', function() {
        paymentExpired = true;
        clearInterval(pollingInterval);

        // Cập nhật trạng thái
        if (statusText) {
            statusText.textContent = '⏰ Hết thời gian thanh toán';
        }
        if (statusSubtext) {
            statusSubtext.textContent = 'Mã QR đã hết hạn, vui lòng đặt vé lại.';
        }

        // Thêm overlay hết hạn lên QR panel
        const qrPanel = document.querySelector('.payment-qr-panel');
        if (qrPanel) {
            // Blur toàn bộ nội dung QR
            qrPanel.classList.add('qr-expired');

            // Thêm overlay thông báo hết hạn
            const expiredOverlay = document.createElement('div');
            expiredOverlay.className = 'qr-expired-overlay';
            expiredOverlay.innerHTML = `
                <div class="qr-expired-content">
                    <div class="qr-expired-icon">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <h3>Mã QR đã hết hạn</h3>
                    <p>Thời gian thanh toán 5 phút đã hết.<br>Vui lòng quay lại đặt vé mới.</p>
                </div>
            `;
            qrPanel.appendChild(expiredOverlay);

            // Animation hiện overlay
            requestAnimationFrame(() => {
                expiredOverlay.classList.add('show');
            });
        }
    });
</script>

{{-- CSS cho QR expired overlay --}}
<style>
    .payment-qr-panel.qr-expired > *:not(.qr-expired-overlay) {
        filter: blur(6px) grayscale(0.7);
        opacity: 0.3;
        pointer-events: none;
        user-select: none;
        transition: all 0.6s ease;
    }

    .qr-expired-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 50;
        opacity: 0;
        transition: opacity 0.5s ease;
        border-radius: inherit;
    }

    .qr-expired-overlay.show {
        opacity: 1;
    }

    .qr-expired-content {
        text-align: center;
        padding: 32px 24px;
        animation: qrExpiredSlideUp 0.6s ease 0.2s both;
    }

    @keyframes qrExpiredSlideUp {
        from {
            transform: translateY(20px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .qr-expired-icon {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background: rgba(239, 68, 68, 0.12);
        border: 2px solid rgba(239, 68, 68, 0.35);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 18px;
        font-size: 32px;
        color: #ef4444;
        animation: qrExpiredPulse 2s infinite;
    }

    @keyframes qrExpiredPulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.25); }
        50% { box-shadow: 0 0 0 12px rgba(239, 68, 68, 0); }
    }

    .qr-expired-content h3 {
        color: #f8fafc;
        font-size: 20px;
        font-weight: 700;
        margin: 0 0 8px;
    }

    .qr-expired-content p {
        color: #9ca3af;
        font-size: 14px;
        line-height: 1.6;
        margin: 0;
    }
</style>

@endsection
