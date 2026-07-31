@extends('layout.staff')

@section('title', 'Xác nhận đặt sản phẩm - Staff')
@section('page-title', 'Xác nhận đơn hàng sản phẩm')

@section('content')
<div class="container-fluid px-2 px-md-4">
    <div class="mb-4">
        <h2 class="fw-bold text-white mb-2">Xác nhận đơn hàng</h2>
        <p class="text-secondary mb-0">Kiểm tra lại số lượng, tổng tiền và chọn phương thức thanh toán trước khi tiếp tục.</p>
    </div>

    <div class="card bg-dark border-secondary">
        <div class="card-body">
            @if(empty($orderItems))
                <div class="alert alert-warning">Không có sản phẩm nào được chọn. Vui lòng quay lại và chọn sản phẩm.</div>
                <a href="{{ \App\Helpers\TabAuthHelper::route('staff.sell-products') }}" class="btn btn-light">Quay lại</a>
            @else
                <form action="{{ \App\Helpers\TabAuthHelper::route('staff.sell-products.checkout') }}" method="POST">
                    @csrf
                    @foreach($orderItems as $index => $item)
                        <input type="hidden" name="items[{{ $index }}][type]" value="{{ $item['type'] }}">
                        <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item['id'] }}">
                        <input type="hidden" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] }}">
                    @endforeach

                    <div class="row g-4">
                        <div class="col-12 col-lg-8">
                            <div class="table-responsive">
                                <table class="table table-borderless text-white align-middle mb-4" style="width:100%;">
                                    <thead>
                                    <tr class="text-secondary small text-uppercase">
                                        <th scope="col">Sản phẩm</th>
                                        <th scope="col" class="text-end">Giá ₫</th>
                                        <th scope="col" class="text-center">Số lượng</th>
                                        <th scope="col" class="text-end">Thành tiền ₫</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($orderItems as $item)
                                        <tr>
                                            <td>{{ $item['name'] }}</td>
                                            <td class="text-end">{{ number_format($item['price'], 0, ',', '.') }}</td>
                                            <td class="text-center">{{ $item['quantity'] }}</td>
                                            <td class="text-end">{{ number_format($item['total'], 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="col-12 col-lg-4">
                            <div class="card bg-dark border-secondary" style="background: linear-gradient(135deg, #111827 0%, #1f2937 100%);">
                                <div class="card-body">
                                    <h5 class="card-title text-white mb-3">Thông tin khách hàng</h5>

                                    <div class="mb-3">
                                        <label class="form-label text-light fw-semibold">Tên khách hàng</label>
                                        <input type="text" name="customer_name" class="form-control bg-transparent text-white border-secondary">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label text-light fw-semibold">Số điện thoại</label>
                                        <input type="text" name="customer_phone" class="form-control bg-transparent text-white border-secondary">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label text-light fw-semibold">Email</label>
                                        <input type="email" name="customer_email" class="form-control bg-transparent text-white border-secondary">
                                    </div>

                                    <div class="border-top border-secondary pt-3 mt-3">
                                        <div class="d-flex justify-content-between text-light">
                                            <span>Tổng thanh toán</span>
                                            <strong class="text-success">{{ number_format(array_sum(array_column($orderItems, 'total')), 0, ',', '.') }}₫</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center flex-column flex-md-row gap-3 mt-4">
                        <div>
                            <h5 class="text-white">Tổng thanh toán:</h5>
                            <p class="fs-4 text-success mb-0">{{ number_format(array_sum(array_column($orderItems, 'total')), 0, ',', '.') }}₫</p>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ \App\Helpers\TabAuthHelper::route('staff.sell-products') }}" class="btn btn-secondary">Sửa lại</a>
                            <button type="submit" name="payment_method" value="ONLINE" class="btn btn-primary">
                                <i class="bi bi-qr-code me-1"></i> Thanh toán online
                            </button>
                            <button type="submit" name="payment_method" value="CASH" class="btn btn-success">
                                <i class="bi bi-cash-coin me-1"></i> Thanh toán tiền mặt
                            </button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
