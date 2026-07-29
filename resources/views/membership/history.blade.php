@extends('layout.app')

@section('title', 'Lịch Sử Coin - Membership')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    .membership-wrapper {
        background-color: #0b1120 !important;
        min-height: 80vh;
        color: #f8fafc !important;
    }

    .membership-box {
        background: #1e293b !important;
        border: 1px solid #334155 !important;
        border-radius: 24px;
        padding: 28px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5) !important;
    }

    .table-custom {
        color: #f8fafc !important;
    }

    .table-custom th {
        background: #0f172a !important;
        color: #cbd5e1 !important;
        border-bottom: 2px solid #334155 !important;
        padding: 16px;
        font-weight: 700;
    }

    .table-custom td {
        background: #1e293b !important;
        color: #e2e8f0 !important;
        border-bottom: 1px solid #334155 !important;
        padding: 16px;
        vertical-align: middle;
    }

    .table-custom tr:hover td {
        background: #334155 !important;
    }
</style>
@endpush

@section('content')
<div class="membership-wrapper py-5">
    <div class="container">
        <!-- Header -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="fw-bold mb-1 text-white"><i class="bi bi-clock-history text-warning me-2"></i>Lịch Sử Tích Điểm & Biến Động Coin</h1>
                <p class="mb-0" style="color: #cbd5e1 !important;">Nhật ký toàn bộ các lần cộng, trừ và thưởng Coin của bạn</p>
            </div>
            <a href="{{ route('membership.index') }}" class="btn btn-outline-light rounded-pill px-4 fw-bold">
                <i class="bi bi-arrow-left me-1"></i> Quay lại Membership
            </a>
        </div>

        <!-- History Table Card -->
        <div class="membership-box mb-4">
            @if(isset($transactions) && $transactions->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>Thời Gian</th>
                                <th>Loại Giao Dịch</th>
                                <th>Nội Dung</th>
                                <th>Mã Booking</th>
                                <th class="text-end">Số Coin</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transactions as $t)
                                <tr>
                                    <td>
                                        <i class="bi bi-calendar3 me-1 text-white-50"></i>
                                        {{ $t->created_at ? $t->created_at->format('H:i - d/m/Y') : 'N/A' }}
                                    </td>
                                    <td>
                                        @if($t->type === 'EARN')
                                            <span class="badge bg-success"><i class="bi bi-plus-circle me-1"></i> Cộng Coin</span>
                                        @elseif($t->type === 'REDEEM')
                                            <span class="badge bg-warning text-dark"><i class="bi bi-dash-circle me-1"></i> Sử dụng</span>
                                        @else
                                            <span class="badge bg-info text-dark"><i class="bi bi-gear-fill me-1"></i> Điều chỉnh</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($t->booking)
                                            Tích lũy từ đơn hàng đặt vé xem phim
                                        @else
                                            Điểm danh hằng ngày nhận Coin thưởng
                                        @endif
                                    </td>
                                    <td>
                                        @if($t->booking)
                                            <span class="font-monospace fw-bold text-warning">#{{ $t->booking->booking_code ?? $t->booking_id }}</span>
                                        @else
                                            <span class="text-white-50">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold fs-6">
                                        @if($t->points > 0)
                                            <span class="text-success">+{{ number_format($t->points) }} Coin</span>
                                        @else
                                            <span class="text-danger">{{ number_format($t->points) }} Coin</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($transactions->hasPages())
                    <div class="mt-4 d-flex justify-content-center">
                        {{ $transactions->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="bi bi-journal-x fs-1 text-white-50 d-block mb-3"></i>
                    <h5 class="fw-bold text-white">Bạn chưa có giao dịch biến động Coin nào</h5>
                    <p class="small mb-0" style="color: #cbd5e1 !important;">Hãy thực hiện điểm danh hoặc mua vé để tích lũy Coin nhé!</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection