@extends('layout.admin')

@section('title', 'Cấu Hình Quy Tắc Mốc Hạng Thành Viên')

@section('content')
<div class="container-fluid py-4">
    <!-- Flash Messages -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-white mb-1"><i class="bi bi-sliders text-warning me-2"></i>Cấu Hình Mốc Hạng & Quyền Lợi</h3>
            <p class="text-muted small mb-0">Quản lý các mốc chi tiêu tích lũy thăng hạng và phần trăm giảm giá vé dành cho Admin</p>
        </div>
        <div>
            <a href="{{ \App\Helpers\TabAuthHelper::route('admin.memberships.index') }}" class="btn btn-outline-light fw-bold">
                <i class="bi bi-arrow-left me-1"></i> Quản lý Khách Hàng
            </a>
        </div>
    </div>

    <!-- Cards Grid -->
    <div class="row g-4">
        @foreach($levels as $lvl)
            @php
                $lvlName = strtoupper($lvl->name);
                $badgeClass = match($lvlName) {
                    'SILVER' => 'bg-secondary text-white',
                    'GOLD' => 'bg-warning text-dark',
                    'PLATINUM' => 'bg-info text-dark',
                    'DIAMOND' => 'bg-primary text-white',
                    default => 'bg-dark text-warning border border-warning',
                };
                $earnRate = match($lvlName) {
                    'SILVER' => '3%',
                    'GOLD' => '5%',
                    'PLATINUM' => '7%',
                    'DIAMOND' => '10%',
                    default => '1%',
                };
            @endphp
            <div class="col-md-6 col-lg-4">
                <div class="card bg-dark text-white border-secondary h-100 shadow-sm">
                    <div class="card-header bg-secondary bg-opacity-25 border-secondary d-flex align-items-center justify-content-between py-3">
                        <span class="badge {{ $badgeClass }} px-3 py-2 fw-bold fs-6">
                            <i class="bi bi-gem me-1"></i> Hạng {{ $lvlName }}
                        </span>
                        <span class="small text-white-50">Tích {{ $earnRate }} Coin</span>
                    </div>
                    <div class="card-body">
                        <form action="{{ \App\Helpers\TabAuthHelper::route('admin.membership_levels.update', $lvl->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="tab_token" value="{{ request('tab_token') }}">

                            <div class="mb-3">
                                <label class="form-label text-white-50 small fw-bold">Mốc chi tiêu tối thiểu (VNĐ)</label>
                                <div class="input-group">
                                    <input type="number" name="min_points" class="form-control bg-dark text-white border-secondary" value="{{ (int) $lvl->min_points }}" min="0" required>
                                    <span class="input-group-text bg-secondary text-white border-secondary">đ</span>
                                </div>
                                <div class="form-text text-muted small">Khách đạt mốc này sẽ tự động thăng hạng.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-white-50 small fw-bold">% Giảm giá vé trực tiếp</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" name="discount_percent" class="form-control bg-dark text-white border-secondary" value="{{ $lvl->discount_percent }}" min="0" max="100" required>
                                    <span class="input-group-text bg-secondary text-white border-secondary">%</span>
                                </div>
                                <div class="form-text text-muted small">Ưu đãi trừ thẳng vào tổng tiền vé khi đặt.</div>
                            </div>

                            <div class="pt-2 border-top border-secondary mt-4 d-flex justify-content-end">
                                <button type="submit" class="btn btn-warning fw-bold px-4">
                                    <i class="bi bi-check-lg me-1"></i> Lưu Thay Đổi
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
