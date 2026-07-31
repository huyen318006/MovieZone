@extends('layout.staff')

@section('title', 'Bán sản phẩm lẻ - Staff')
@section('page-title', 'Bán sản phẩm lẻ')

@section('content')
<div class="container-fluid px-2 px-md-4">
    <div class="mb-4">
        <h2 class="fw-bold text-white mb-2">Bán sản phẩm lẻ</h2>
        <p class="text-secondary mb-0">
    </div>

    @if($products->isEmpty() && $combos->isEmpty())
        <div class="alert alert-secondary">Không có sản phẩm hoặc combo để đặt hàng.</div>
    @else
        <form action="{{ \App\Helpers\TabAuthHelper::route('staff.sell-products.order') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-12 col-xl-8">
                    <div class="card bg-dark border-secondary h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <div>
                                    <h4 class="card-title text-white mb-1">Sản phẩm lẻ</h4>
                                    <p class="text-secondary mb-0">Nhập số lượng để thêm vào đơn hàng.</p>
                                </div>
                                <span class="badge bg-secondary">Sản phẩm: {{ $products->count() }}</span>
                            </div>

                            @if($products->isEmpty())
                                <div class="alert alert-secondary mb-0">Không có sản phẩm lẻ nào.</div>
                            @else
                                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-2 g-3">
                                    @foreach($products as $product)
                                        <div class="col">
                                            <div class="card h-100 bg-surface border-secondary shadow-none">
                                                <div class="card-body d-flex flex-column" style="width:100%;">
                                                    <div class="d-flex justify-content-between align-items-start mb-2 gap-2" style="width:100%;">
                                                        <span class="badge bg-primary">{{ strtoupper($product->status ?? 'ACTIVE') }}</span>
                                                        <span class="text-success fw-semibold">{{ number_format($product->price, 0, ',', '.') }}₫</span>
                                                    </div>
                                                    <h5 class="card-title text-white mb-2" style="font-size:1.1rem;">{{ $product->name }}</h5>
                                                    <p class="card-text text-secondary mb-3" style="width:100%;">{{ \Illuminate\Support\Str::limit($product->description ?? 'Không có mô tả', 100) }}</p>

                                                    <div class="mb-3" style="width:100%; max-width: 140px;">
                                                        <label for="product-{{ $product->id }}" class="form-label text-secondary small mb-1">Số lượng</label>
                                                        <input
                                                            id="product-{{ $product->id }}"
                                                            type="number"
                                                            name="products[{{ $product->id }}][quantity]"
                                                            min="0"
                                                            value="0"
                                                            class="form-control form-control-sm bg-transparent text-white border-secondary"
                                                        >
                                                    </div>

                                                    <div class="mt-auto d-flex justify-content-between align-items-center flex-wrap gap-2" style="width:100%;">
                                                        <small class="text-muted">Mã #{{ $product->id }}</small>
                                                        <small class="text-muted">Cập nhật: {{ optional($product->updated_at)->format('d/m/Y') ?? '---' }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-4">
                    <div class="card bg-dark border-secondary h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <div>
                                    <h4 class="card-title text-white mb-1">Combo & gợi ý</h4>
                                    <p class="text-secondary mb-0">Chọn combo kèm theo đơn hàng.</p>
                                </div>
                                <span class="badge bg-secondary">Combo: {{ $combos->count() }}</span>
                            </div>

                            @if($combos->isEmpty())
                                <div class="alert alert-secondary mb-0">Không có combo nào.</div>
                            @else
                                <div class="row row-cols-1 g-3">
                                    @foreach($combos as $combo)
                                        <div class="col">
                                            <div class="card bg-surface border-secondary shadow-none">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                                                        <div>
                                                            <h6 class="text-white mb-1">{{ $combo->name }}</h6>
                                                            <p class="text-secondary small mb-2">{{ \Illuminate\Support\Str::limit($combo->description ?? 'Không có mô tả', 90) }}</p>
                                                            <div class="d-flex gap-2 flex-wrap">
                                                                <span class="badge bg-info text-dark">{{ number_format($combo->price, 0, ',', '.') }}₫</span>
                                                                <span class="badge bg-secondary">{{ strtoupper($combo->status ?? 'ACTIVE') }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3" style="max-width: 140px;">
                                                        <label for="combo-{{ $combo->id }}" class="form-label text-secondary small mb-1">Số lượng</label>
                                                        <input
                                                            id="combo-{{ $combo->id }}"
                                                            type="number"
                                                            name="combos[{{ $combo->id }}][quantity]"
                                                            min="0"
                                                            value="0"
                                                            class="form-control form-control-sm bg-transparent text-white border-secondary"
                                                        >
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-success btn-lg px-4">Đặt hàng</button>
            </div>
        </form>
    @endif
</div>
@endsection

@push('styles')
<style>
.bg-surface {
    background: #111827 !important;
    color: #f8fafc !important;
}

.card.bg-dark {
    background: rgba(15, 23, 42, 0.95) !important;
}

.card-title {
    letter-spacing: 0.02em;
}

.form-label {
    color: #94a3b8 !important;
}

.table .text-end {
    text-align: right !important;
}

@media (max-width: 768px) {
    .card-body {
        padding: 1rem !important;
    }
}
</style>
@endpush
