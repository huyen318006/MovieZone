@extends('layout.staff')

@section('title', 'Thanh toán QR - Bán sản phẩm lẻ')
@section('page-title', 'Bán sản phẩm lẻ — Thanh toán QR')

@section('content')
<div class="container-fluid px-2 px-md-4">
    <div class="card bg-dark border-secondary">
        <div class="card-body">
            <h3 class="text-white mb-3">Thanh toán đơn hàng</h3>
            <p class="text-secondary">Mã đơn: {{ $order->order_code }}</p>

            <div class="row g-4">
                <div class="col-12 col-lg-7">
                    <div class="card bg-black border-secondary h-100">
                        <div class="card-body">
                            <h5 class="text-white mb-3">Chi tiết đơn hàng</h5>
                            @foreach($order->metadata['items'] ?? [] as $item)
                                <div class="d-flex justify-content-between text-white py-2 border-bottom border-secondary">
                                    <span>{{ $item['name'] }} x{{ $item['quantity'] }}</span>
                                    <span>{{ number_format($item['total'], 0, ',', '.') }}₫</span>
                                </div>
                            @endforeach
                            <div class="d-flex justify-content-between text-success fw-bold pt-3">
                                <span>Tổng</span>
                                <span>{{ number_format($order->amount, 0, ',', '.') }}₫</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-5">
                    <div class="card bg-black border-secondary h-100">
                        <div class="card-body text-center">
                            @if(($order->metadata['payment_method'] ?? null) === 'CASH')
                                <h5 class="text-white mb-3">Thanh toán tiền mặt</h5>
                                <div class="alert alert-warning text-start">
                                    Vui lòng thu đủ tiền mặt từ khách hàng, sau đó xác nhận bên dưới.
                                </div>
                                <p class="text-white fw-semibold">Số tiền cần thu: {{ number_format($order->amount, 0, ',', '.') }}₫</p>
                                <form method="POST" action="{{ \App\Helpers\TabAuthHelper::route('staff.sell-products.cash-confirm', $order->order_code) }}" class="mt-4">
                                    @csrf
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="bi bi-check-circle me-1"></i> Xác nhận đã thu tiền mặt
                                    </button>
                                </form>
                            @else
                                <h5 class="text-white mb-3">Quét mã QR</h5>
                                <img src="{{ $qrUrl }}" alt="QR thanh toán" class="img-fluid rounded" style="max-width: 280px;">
                                <p class="text-secondary mt-3 mb-2">Ngân hàng: {{ $bankCode }}</p>
                                <p class="text-secondary mb-3">Số tài khoản: {{ $bankAccount }}</p>
                                <p class="text-white fw-semibold">Số tiền: {{ number_format($order->amount, 0, ',', '.') }}₫</p>
                                <p class="text-secondary small">Nội dung chuyển khoản: {{ $order->order_code }}</p>

                                <div class="mt-4 border-top border-secondary pt-3">
                                    <p class="text-secondary mb-2" id="statusText">Đang chờ thanh toán...</p>
                                    <p class="text-muted small" id="statusSubtext">Hệ thống sẽ tự động kiểm tra mỗi {{ $pollingInterval / 1000 }} giây.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if(($order->metadata['payment_method'] ?? null) !== 'CASH')
                <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const statusText = document.getElementById('statusText');
                    const statusSubtext = document.getElementById('statusSubtext');
                    const checkUrl = '{{ \App\Helpers\TabAuthHelper::route('staff.sell-products.check-status', $order->order_code) }}';
                    const successUrl = '{{ \App\Helpers\TabAuthHelper::route('staff.sell-products.success', ['orderCode' => $order->order_code, 'paymentMethod' => 'ONLINE']) }}';
                    const intervalMs = {{ $pollingInterval }};

                    function checkPayment() {
                        fetch(checkUrl)
                            .then(function (response) {
                                if (!response.ok) {
                                    throw new Error('Payment status request failed');
                                }

                                return response.json();
                            })
                            .then(function (data) {
                                if (data.status === 'paid') {
                                    clearInterval(timer);
                                    statusText.textContent = 'Thanh toán thành công';
                                    statusSubtext.textContent = 'Đang chuyển tới trang xác nhận...';
                                    window.location.href = successUrl;
                                } else if (data.status === 'expired') {
                                    clearInterval(timer);
                                    statusText.textContent = 'Đơn hàng đã hết hạn';
                                    statusSubtext.textContent = 'Vui lòng tạo lại đơn hàng.';
                                }
                            })
                            .catch(function () {
                                statusSubtext.textContent = 'Đang kiểm tra lại trạng thái thanh toán...';
                            });
                    }

                    const timer = setInterval(checkPayment, intervalMs);
                    checkPayment();
                });
                </script>
            @endif
        </div>
    </div>
</div>
@endsection
