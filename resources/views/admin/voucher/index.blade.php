@extends('layout.admin')

@section('title', 'Quản lý Mã giảm giá (Voucher)')

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle-fill me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="col-12">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h3 class="mb-1">Quản lý Mã giảm giá (Voucher)</h3>
            <p class="text-muted mb-0">Tạo và cấu hình mã giảm giá khi thanh toán vé/combo.</p>
        </div>
        <div>
            <a href="{{ route('admin.vouchers.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Thêm Voucher mới
            </a>
        </div>
    </div>
</div>

<div class="col-12 mt-3">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="fw-semibold">Danh sách mã giảm giá</div>
            <form method="GET" action="{{ route('admin.vouchers.index') }}" class="d-flex gap-2 align-items-center">
                <input type="text" name="search" class="form-control form-control-sm" style="width: 200px;" placeholder="Tìm mã giảm giá..." value="{{ request('search') }}">
                <select name="status" class="form-select form-select-sm" style="width: 150px;" onchange="this.form.submit()">
                    <option value="">Tất cả trạng thái</option>
                    <option value="ACTIVE" {{ request('status') == 'ACTIVE' ? 'selected' : '' }}>Hoạt động</option>
                    <option value="DISABLED" {{ request('status') == 'DISABLED' ? 'selected' : '' }}>Vô hiệu hóa</option>
                    <option value="EXPIRED" {{ request('status') == 'EXPIRED' ? 'selected' : '' }}>Hết hạn</option>
                </select>
                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-search"></i></button>
                <a href="{{ route('admin.vouchers.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-clockwise"></i></a>
            </form>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Mã code</th>
                            <th>Kiểu giảm</th>
                            <th>Mức giảm</th>
                            <th>Đơn tối thiểu</th>
                            <th>Giới hạn dùng</th>
                            <th>Đã dùng</th>
                            <th>Thời gian áp dụng</th>
                            <th>Trạng thái</th>
                            <th style="width: 180px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vouchers as $voucher)
                            <tr>
                                <td class="fw-bold text-primary">{{ $voucher->code }}</td>
                                <td>
                                    @if($voucher->discount_type === 'PERCENT')
                                        <span class="badge text-bg-info">Phần trăm (%)</span>
                                    @else
                                        <span class="badge text-bg-warning">Số tiền cố định</span>
                                    @endif
                                </td>
                                <td>
                                    @if($voucher->discount_type === 'PERCENT')
                                        <strong>{{ (int)$voucher->discount_value }}%</strong>
                                        @if($voucher->max_discount)
                                            <br><small class="text-muted">Tối đa: {{ number_format($voucher->max_discount, 0, ',', '.') }} đ</small>
                                        @endif
                                    @else
                                        <strong>{{ number_format($voucher->discount_value, 0, ',', '.') }} đ</strong>
                                    @endif
                                </td>
                                <td>{{ number_format($voucher->min_order_amount, 0, ',', '.') }} đ</td>
                                <td>
                                    @if($voucher->usage_limit === -1)
                                        <span class="text-muted">Không giới hạn</span>
                                    @elseif($voucher->usage_limit > 0)
                                        {{ $voucher->usage_limit }} lần
                                    @else
                                        <span class="text-danger">Không hợp lệ</span>
                                    @endif
                                    <br><small class="text-muted">Mỗi user: {{ $voucher->usage_per_user }} lần</small>
                                </td>
                                <td>
                                    <span class="badge text-bg-light fw-semibold">{{ $voucher->usages()->count() }} lần</span>
                                </td>
                                <td>
                                    <small>Từ: {{ \Carbon\Carbon::parse($voucher->start_date)->format('d/m/Y H:i') }}</small>
                                    <br>
                                    <small>Đến: {{ \Carbon\Carbon::parse($voucher->end_date)->format('d/m/Y H:i') }}</small>
                                </td>
                                <td>
                                    @if($voucher->status === 'ACTIVE')
                                        <span class="badge text-bg-success">Hoạt động</span>
                                    @elseif($voucher->status === 'DISABLED')
                                        <span class="badge text-bg-secondary">Vô hiệu hóa</span>
                                    @else
                                        <span class="badge text-bg-danger">Hết hạn</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.vouchers.edit', $voucher->id) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-pencil"></i> Sửa
                                        </a>
                                        <form method="POST" action="{{ route('admin.vouchers.destroy', $voucher->id) }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa mã giảm giá này không?');" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                <i class="bi bi-trash"></i> Xóa
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    Không tìm thấy mã giảm giá nào.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex align-items-center justify-content-between mt-3">
                <div class="text-muted">Tổng số: {{ $vouchers->total() }}</div>
                {{ $vouchers->links() }}
            </div>
        </div>
    </div>
</div>

@endsection
