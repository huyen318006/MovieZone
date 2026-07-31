@extends('layout.staff')

@section('title', 'Thanh toán thành công - Staff')
@section('page-title', 'Thanh toán thành công')

@section('content')
<div class="container-fluid px-2 px-md-4">
    <div class="card bg-dark border-secondary">
        <div class="card-body text-center py-5">
            <div class="mb-4">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 90px; height: 90px; background: rgba(40, 167, 69, 0.2);">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                </div>
            </div>

            <h2 class="text-white mb-3">Thanh toán thành công</h2>
            <p class="text-secondary mb-4">Đơn hàng của bạn đã được xử lý thành công.</p>

            <div class="card bg-black border-secondary mx-auto" style="max-width: 560px;">
                <div class="card-body text-start">
                    <div class="d-flex justify-content-between py-2 border-bottom border-secondary">
                        <span class="text-secondary">Mã đơn hàng</span>
                        <strong class="text-white">{{ $order->order_code }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom border-secondary">
                        <span class="text-secondary">Phương thức</span>
                        <strong class="text-white">{{ $paymentLabel }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span class="text-secondary">Tổng tiền</span>
                        <strong class="text-success">{{ number_format($order->amount, 0, ',', '.') }}₫</strong>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                @if($invoice)
                    <a href="{{ \App\Helpers\TabAuthHelper::route('staff.sell-products.invoice', $order->order_code) }}" class="btn btn-outline-light px-4 me-2" target="_blank">
                        <i class="bi bi-printer me-1"></i> In hóa đơn
                    </a>
                @endif
                <a href="{{ \App\Helpers\TabAuthHelper::route('staff.sell-products') }}" class="btn btn-success px-4">Tạo đơn mới</a>
            </div>
        </div>
    </div>
</div>
@endsection
