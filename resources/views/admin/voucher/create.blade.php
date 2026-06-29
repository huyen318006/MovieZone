@extends('layout.admin')

@section('title', 'Thêm Voucher mới')

@section('content')

<div class="col-12">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h3 class="mb-1">Thêm Voucher mới</h3>
            <p class="text-muted mb-0">Cấu hình mã ưu đãi giảm giá mới.</p>
        </div>
        <div>
            <a href="{{ route('admin.vouchers.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>
</div>

<div class="col-12 mt-3">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.vouchers.store') }}" method="POST">
                @csrf

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="code" class="form-label fw-semibold">Mã giảm giá <span class="text-danger">*</span></label>
                        <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="Ví dụ: MOVIE2026, STUDENT10" style="text-transform: uppercase;">
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="discount_type" class="form-label fw-semibold">Loại giảm giá <span class="text-danger">*</span></label>
                        <select name="discount_type" id="discount_type" class="form-select @error('discount_type') is-invalid @enderror">
                            <option value="PERCENT" {{ old('discount_type') == 'PERCENT' ? 'selected' : '' }}>Giảm theo Phần trăm (%)</option>
                            <option value="FIXED" {{ old('discount_type') == 'FIXED' ? 'selected' : '' }}>Giảm theo Số tiền cố định (đ)</option>
                        </select>
                        @error('discount_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="discount_value" class="form-label fw-semibold">Giá trị giảm <span class="text-danger">*</span></label>
                        <input type="number" name="discount_value" id="discount_value" class="form-control @error('discount_value') is-invalid @enderror" value="{{ old('discount_value') }}" min="0" placeholder="Ví dụ: 10 (%) hoặc 50000 (đ)">
                        @error('discount_value')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="max_discount" class="form-label fw-semibold">Giảm tối đa (đ)</label>
                        <input type="number" name="max_discount" id="max_discount" class="form-control @error('max_discount') is-invalid @enderror" value="{{ old('max_discount') }}" min="0" placeholder="Chỉ áp dụng với giảm theo %">
                        @error('max_discount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="min_order_amount" class="form-label fw-semibold">Đơn tối thiểu áp dụng (đ) <span class="text-danger">*</span></label>
                        <input type="number" name="min_order_amount" id="min_order_amount" class="form-control @error('min_order_amount') is-invalid @enderror" value="{{ old('min_order_amount', 0) }}" min="0">
                        @error('min_order_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="usage_limit" class="form-label fw-semibold">Tổng lượt sử dụng tối đa <span class="text-danger">*</span></label>
                        <input type="number" name="usage_limit" id="usage_limit" class="form-control @error('usage_limit') is-invalid @enderror" value="{{ old('usage_limit', 0) }}" min="0">
                        <small class="text-muted">Nhập 0 nếu không giới hạn hệ thống.</small>
                        @error('usage_limit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="usage_per_user" class="form-label fw-semibold">Lượt dùng mỗi người dùng <span class="text-danger">*</span></label>
                        <input type="number" name="usage_per_user" id="usage_per_user" class="form-control @error('usage_per_user') is-invalid @enderror" value="{{ old('usage_per_user', 1) }}" min="1">
                        @error('usage_per_user')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="start_date" class="form-label fw-semibold">Ngày bắt đầu <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="start_date" id="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date') }}">
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="end_date" class="form-label fw-semibold">Ngày kết thúc <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="end_date" id="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}">
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="status" class="form-label fw-semibold">Trạng thái <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="ACTIVE" {{ old('status') == 'ACTIVE' ? 'selected' : '' }}>Hoạt động</option>
                            <option value="DISABLED" {{ old('status') == 'DISABLED' ? 'selected' : '' }}>Vô hiệu hóa</option>
                            <option value="EXPIRED" {{ old('status') == 'EXPIRED' ? 'selected' : '' }}>Hết hạn</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 text-end mt-4">
                        <button type="reset" class="btn btn-secondary me-2">Nhập lại</button>
                        <button type="submit" class="btn btn-primary">Lưu Voucher</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
