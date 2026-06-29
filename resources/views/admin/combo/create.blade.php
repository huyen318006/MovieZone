@extends('layout.admin')

@section('title', 'Thêm Combo bắp nước')

@section('content')

<div class="col-12">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h3 class="mb-1">Thêm Combo bắp nước</h3>
            <p class="text-muted mb-0">Tạo gói combo mới từ các sản phẩm lẻ hiện có.</p>
        </div>
        <div>
            <a href="{{ route('admin.combos.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>
</div>

<div class="col-12 mt-3">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if($products->isEmpty())
                <div class="alert alert-warning py-3 text-center">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-4"></i>
                    Không có sản phẩm lẻ hoạt động nào. Hãy tạo <a href="{{ route('admin.products.create') }}">Sản phẩm lẻ</a> trước khi tạo combo.
                </div>
            @else
                <form action="{{ route('admin.combos.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-semibold">Tên combo <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Nhập tên combo, ví dụ: Combo Couple, Combo Family...">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="price" class="form-label fw-semibold">Giá combo (VNĐ) <span class="text-danger">*</span></label>
                            <input type="number" name="price" id="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price') }}" min="0" placeholder="Thường rẻ hơn mua tổng giá lẻ. Ví dụ: 99000">
                            @error('price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="status" class="form-label fw-semibold">Trạng thái <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="ACTIVE" {{ old('status') == 'ACTIVE' ? 'selected' : '' }}>Hoạt động</option>
                                <option value="INACTIVE" {{ old('status') == 'INACTIVE' ? 'selected' : '' }}>Đã ẩn (Không bán)</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="image" class="form-label fw-semibold">Ảnh combo</label>
                            <input type="file" name="image" id="image" class="form-control @error('image') is-invalid @enderror">
                            <small class="text-muted">Định dạng hỗ trợ: jpeg, png, jpg, webp. Tối đa 2MB.</small>
                            @error('image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Chọn sản phẩm thành phần <span class="text-danger">*</span></label>
                            @error('product_ids')
                                <div class="text-danger small mb-2"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                            @enderror
                            <div class="border rounded p-3 bg-light">
                                <div class="row g-3">
                                    @foreach($products as $product)
                                        <div class="col-md-6 col-lg-4">
                                            <div class="card h-100 border-0 shadow-sm p-3">
                                                <div class="form-check d-flex align-items-center justify-content-between">
                                                    <div>
                                                        <input class="form-check-input product-checkbox" type="checkbox" name="product_ids[]" id="product_{{ $product->id }}" value="{{ $product->id }}" {{ is_array(old('product_ids')) && in_array($product->id, old('product_ids')) ? 'checked' : '' }}>
                                                        <label class="form-check-label fw-semibold ms-2" for="product_{{ $product->id }}">
                                                            {{ $product->name }}
                                                        </label>
                                                        <div class="text-muted small ms-2">{{ number_format($product->price, 0, ',', '.') }} đ</div>
                                                    </div>
                                                    <div style="width: 80px;">
                                                        <input type="number" name="quantities[{{ $product->id }}]" class="form-control form-control-sm quantity-input" min="1" value="{{ old('quantities.' . $product->id, 1) }}" {{ is_array(old('product_ids')) && in_array($product->id, old('product_ids')) ? '' : 'disabled' }}>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label fw-semibold">Mô tả combo</label>
                            <textarea name="description" id="description" class="form-control" rows="4" placeholder="Ví dụ: 1 bắp rang vừa ngọt + 2 ly coca cỡ vừa...">{{ old('description') }}</textarea>
                        </div>

                        <div class="col-12 text-end mt-4">
                            <button type="reset" class="btn btn-secondary me-2">Nhập lại</button>
                            <button type="submit" class="btn btn-primary">Lưu combo</button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkboxes = document.querySelectorAll('.product-checkbox');
    checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            const quantityInput = this.closest('.form-check').querySelector('.quantity-input');
            if (this.checked) {
                quantityInput.disabled = false;
            } else {
                quantityInput.disabled = true;
            }
        });
    });
});
</script>
@endpush
