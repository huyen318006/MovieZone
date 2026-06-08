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

        {{-- Thông tin phim --}}
        <div class="bill-movie-section">
            <div class="bill-movie-icon">🎬</div>
            <div class="bill-movie-detail">
                <h3>{{ $order->getBookingInfo('movie_title') }}</h3>
                <div class="bill-movie-meta">
                    <span><i class="fa-solid fa-building"></i> {{ $order->getBookingInfo('cinema') }}</span>
                    <span><i class="fa-solid fa-door-open"></i> {{ $order->getBookingInfo('room') }}</span>
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

        {{-- Chi tiết giá --}}
        <div class="bill-price-section">
            <h4><i class="fa-solid fa-receipt"></i> Chi tiết thanh toán</h4>
            @foreach($order->getBookingSeats() as $seat)
            <div class="bill-price-row">
                <span>
                    @if($seat['type'] === 'vip') 👑 VIP
                    @elseif($seat['type'] === 'sweetbox') 💕 Sweetbox
                    @else 🎬 Thường
                    @endif
                    — {{ $seat['code'] }}
                </span>
                <span>{{ number_format($seat['price'], 0, ',', '.') }}đ</span>
            </div>
            @endforeach

            <div class="bill-price-total">
                <span>Tổng thanh toán</span>
                <span class="total-amount">{{ number_format($order->amount, 0, ',', '.') }}đ</span>
            </div>
        </div>

        {{-- Thông tin giao dịch --}}
        <div class="bill-transaction-info">
            @if($order->transaction_id)
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
        <button class="bill-btn-secondary" onclick="window.print()" id="btn-print">
            <i class="fa-solid fa-print"></i> In Hoá Đơn
        </button>
        <a href="{{ route('home') }}" class="bill-btn-primary" id="btn-home">
            <i class="fa-solid fa-house"></i> Về Trang Chủ
        </a>
    </div>

</div>
</section>

@endsection
