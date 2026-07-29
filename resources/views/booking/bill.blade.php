@extends('layout.app')

@section('content')
    <section class="bill-page">
        <div class="bill-wrapper">

            {{-- Success Header --}}
            <div class="bill-success-header">
                <div class="success-circle-mz">
                    <i class="fa-solid fa-check"></i>
                </div>
                <h1>Đặt Vé Thành Công!</h1>
                <p>Giao dịch của bạn đã được xác nhận</p>
            </div>

            {{-- Thông báo email đã gửi --}}
            @if($order->isEmailSent())
            <div class="bill-email-notice">
                <i class="fa-solid fa-envelope-circle-check"></i>
                <div>
                    <strong>📧 Hoá đơn đã được gửi tới email</strong>
                    <span>{{ $order->metadata['email_sent_to'] ?? $order->getCustomerEmail() }}</span>
                </div>
            </div>
            @endif

            {{-- Bill Card --}}
            <div class="bill-card-mz">

                <div class="bill-card-top">
                    <div class="bill-card-top-left">
                        <span class="bill-label-sm">HÓA ĐƠN VÉ PHIM</span>
                        <span class="bill-order-code">{{ $order->order_code }}</span>
                    </div>
                    <div class="bill-status-badge">
                        <i class="fa-solid fa-circle-check"></i>
                        Đã thanh toán
                    </div>
                </div>

                {{-- QR Xác Nhận Vé --}}
                <div class="bill-qr-section">
                    <div class="bill-qr-header">
                        <i class="fa-solid fa-qrcode"></i>
                        <div>
                            <h4>Mã QR Xác Nhận Vé</h4>
                            <p>Đưa mã QR này cho lễ tân tại rạp để nhận vé</p>
                        </div>
                    </div>
                    <div class="bill-qr-body">
                        <div class="bill-qr-frame">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=280x280&data={{ urlencode($order->generateTicketQrData()) }}&color=0f172a&bgcolor=ffffff&margin=8"
                                alt="QR Xác nhận vé {{ $order->order_code }}" id="confirmQr">
                        </div>
                        <div class="bill-qr-code-text">{{ $order->order_code }}</div>
                        <div class="bill-qr-cinema">
                            <i class="fa-solid fa-location-dot"></i>
                            {{ $order->getBookingInfo('cinema') }}
                        </div>
                    </div>
                </div>

                {{-- Thông tin phim --}}
                <div class="bill-movie-section">
                    <div class="bill-movie-icon">🎬</div>
                    <div class="bill-movie-detail">
                        <h3>{{ $order->getBookingInfo('movie_title') }}</h3>
                        <div class="bill-movie-meta">
                            <span><i class="fa-solid fa-building"></i> {{ $order->getBookingInfo('cinema') }}</span>
                            <span><i class="fa-solid fa-door-open"></i> {{ $order->getBookingInfo('room') }}</span>
                            <span><i class="fa-solid fa-tv"></i> {{ $order->getBookingInfo('format') }}</span>
                        </div>
                    </div>
                </div>


                {{-- Thông tin chi tiết --}}
                <div class="bill-detail-grid">
                    <div class="bill-detail-item">
                        <i class="fa-solid fa-calendar"></i>
                        <div>
                            <span class="label">Ngày chiếu</span>
                            <span class="value">{{ $order->getBookingInfo('show_date') }}</span>
                        </div>
                    </div>
                    <div class="bill-detail-item">
                        <i class="fa-solid fa-clock"></i>
                        <div>
                            <span class="label">Suất chiếu</span>
                            <span class="value">{{ $order->getBookingInfo('showtime') }}</span>
                        </div>
                    </div>
                    <div class="bill-detail-item">
                        <i class="fa-solid fa-tv"></i>
                        <div>
                            <span class="label">Định dạng</span>
                            <span class="value">{{ $order->getBookingInfo('format') }}</span>
                        </div>
                    </div>
                    <div class="bill-detail-item">
                        <i class="fa-solid fa-couch"></i>
                        <div>
                            <span class="label">Ghế ngồi</span>
                            <span class="value">{{ $order->getSeatCodesFormatted() }}</span>
                        </div>
                    </div>
                </div>

                {{-- Thông tin khách hàng --}}
                <div class="bill-customer-section" style="padding: 20px; background: rgba(59, 130, 246, 0.05); border-radius: 12px; border: 1px solid rgba(59, 130, 246, 0.2); margin-bottom: 25px;">
                    <h4 style="margin: 0 0 15px 0; color: #60a5fa; font-size: 15px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fa-solid fa-user"></i> Thông tin khách hàng
                    </h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <span style="display: block; color: #94a3b8; font-size: 12px; margin-bottom: 4px;">Họ và Tên</span>
                            <strong style="color: #f8fafc; font-size: 14px;">{{ $order->getCustomerName() }}</strong>
                        </div>
                        <div>
                            <span style="display: block; color: #94a3b8; font-size: 12px; margin-bottom: 4px;">Số điện thoại</span>
                            <strong style="color: #f8fafc; font-size: 14px;">{{ $order->getCustomerPhone() }}</strong>
                        </div>
                        <div style="grid-column: span 2;">
                            <span style="display: block; color: #94a3b8; font-size: 12px; margin-bottom: 4px;">Email</span>
                            <strong style="color: #f8fafc; font-size: 14px;">{{ $order->getCustomerEmail() }}</strong>
                        </div>
                    </div>
                </div>

                {{-- Chi tiết giá --}}
                <div class="bill-price-section">
                    <h4><i class="fa-solid fa-receipt"></i> Chi tiết thanh toán</h4>
                    @foreach ($order->getBookingSeats() as $seat)
                        <div class="bill-price-row">
                            <span>
                                @if ($seat['type'] === 'vip')
                                    👑 VIP
                                @elseif($seat['type'] === 'sweetbox')
                                    💕 Sweetbox
                                @else
                                    🎬 Thường
                                @endif
                                — {{ $seat['code'] }}
                            </span>
                            <span>{{ number_format($seat['price'], 0, ',', '.') }}đ</span>
                        </div>
                    @endforeach

                    @if(!empty($order->metadata['combos']))
                        <div style="margin-top: 12px; padding-top: 12px; border-top: 1px dashed rgba(255,255,255,0.1);">
                            @foreach($order->metadata['combos'] as $combo)
                                <div class="bill-price-row">
                                    <span>🍿 {{ $combo['name'] }} x{{ $combo['quantity'] }}</span>
                                    <span>{{ number_format($combo['total_price'], 0, ',', '.') }}đ</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="bill-price-total">
                        <span>Tổng thanh toán</span>
                        <span class="total-amount">{{ number_format($order->amount, 0, ',', '.') }}đ</span>
                    </div>
                </div>



                {{-- Thông tin giao dịch --}}
                <div class="bill-transaction-info">
                    @if ($order->transaction_id)
                        <div class="trans-row">
                            <span>Mã giao dịch</span>
                            <span>{{ $order->transaction_id }}</span>
                        </div>
                    @endif
                    <div class="trans-row">
                        <span>Thời gian đặt</span>
                        <span>{{ $order->created_at->format('d/m/Y H:i:s') }}</span>
                    </div>
                    <div class="trans-row">
                        <span>Thời gian TT</span>
                        <span>{{ $order->paid_at ? $order->paid_at->format('d/m/Y H:i:s') : '—' }}</span>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="bill-card-bottom">
                    <span>Powered by SePay • MovieZone</span>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="bill-actions-mz">
                <a href="{{ \App\Helpers\TabAuthHelper::route('home') }}" class="bill-btn-primary" id="btn-home">
                    <i class="fa-solid fa-house"></i> Về Trang Chủ
                </a>
            </div>

        </div>
    </section>

    @if(request()->query('print') == 'true')
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
    @endif
@endsection
