@extends('layout.app')

@section('title', 'Membership - Thẻ Thành Viên')

@push('styles')
<style>
    .membership-page {
        color: #f8fafc;
    }

    .membership-card-hero {
        position: relative;
        overflow: hidden;
        border-radius: 24px;
        padding: 32px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.15);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .membership-card-hero:hover {
        transform: translateY(-4px);
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
    }

    .card-bronze {
        background: linear-gradient(135deg, #451a03 0%, #78350f 50%, #92400e 100%);
    }
    .card-silver {
        background: linear-gradient(135deg, #1e293b 0%, #475569 50%, #64748b 100%);
    }
    .card-gold {
        background: linear-gradient(135deg, #78350f 0%, #b45309 50%, #d97706 100%);
    }
    .card-platinum {
        background: linear-gradient(135deg, #0369a1 0%, #0284c7 50%, #6366f1 100%);
    }
    .card-diamond {
        background: linear-gradient(135deg, #581c87 0%, #7c3aed 50%, #2563eb 100%);
    }

    .membership-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 16px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.18);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .coin-balance-display {
        font-size: clamp(32px, 5vw, 48px);
        font-weight: 900;
        line-height: 1;
        letter-spacing: -0.02em;
    }

    .progress-box {
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        padding: 24px;
    }

    .custom-progress-bar {
        height: 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.1);
        overflow: hidden;
    }

    .custom-progress-fill {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #3b82f6, #8b5cf6, #ec4899);
        transition: width 0.6s ease;
    }
</style>
@endpush

@section('content')
<div class="container py-5 membership-page">
    <!-- Hero Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="fw-bold mb-1"><i class="bi bi-shield-check text-warning me-2"></i>Thành Viên MovieZone</h1>
            <p class="text-muted mb-0">Quản lý hạng thành viên, theo dõi tích điểm và hưởng ưu đãi độc quyền</p>
        </div>
        <a href="{{ route('profile') }}" class="btn btn-outline-light rounded-pill px-4">
            <i class="bi bi-person me-1"></i> Hồ sơ
        </a>
    </div>

    @php
        $levelName = strtoupper($currentLevel->name ?? 'BRONZE');
        $cardClass = match($levelName) {
            'SILVER' => 'card-silver',
            'GOLD' => 'card-gold',
            'PLATINUM' => 'card-platinum',
            'DIAMOND' => 'card-diamond',
            default => 'card-bronze',
        };
    @endphp

    <!-- Membership Card & Progress Row -->
    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="membership-card-hero {{ $cardClass }}">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <span class="membership-badge">
                        <i class="bi bi-gem"></i> HẠNG {{ $levelName }}
                    </span>
                    <span class="text-white-50 small fw-bold">MOVIEZONE VIP MEMBER</span>
                </div>

                <div class="mb-4">
                    <span class="text-white-50 text-uppercase small fw-bold d-block mb-1">Số dư Coin tích lũy</span>
                    <div class="coin-balance-display">
                        🪙 {{ number_format($coins) }} <span class="fs-6 fw-normal text-white-50">Coin</span>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between pt-3 border-top border-white-10 text-white-50 small">
                    <div>
                        <i class="bi bi-person-circle me-1"></i> {{ $user->name }}
                    </div>
                    <div>
                        @if (!empty($userMembership->level_expired_at))
                            <i class="bi bi-clock-history me-1"></i> Hạn duy trì: {{ $userMembership->level_expired_at->format('d/m/Y') }}
                        @else
                            <i class="bi bi-shield-lock me-1"></i> Thành viên chính thức
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress & Target Box -->
        <div class="col-lg-5">
            <div class="progress-box h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="fw-bold mb-0"><i class="bi bi-graph-up-arrow text-info me-2"></i>Tiến Độ Nâng Hạng</h5>
                        <span class="badge bg-info text-dark fw-bold">{{ $progress }}%</span>
                    </div>

                    @if ($nextLevel)
                        <p class="text-muted small mb-3">
                            Bạn đang có <strong class="text-white">{{ number_format($coins) }} Coin</strong>.
                            Cần tích lũy thêm <strong class="text-warning">{{ number_format($pointsNeeded) }} Coin</strong>
                            để thăng hạng <strong class="text-info">{{ $nextLevel->name }}</strong> (Mốc {{ number_format($nextLevel->min_points) }} Coin).
                        </p>
                    @else
                        <p class="text-success small mb-3">
                            <i class="bi bi-trophy-fill me-1"></i> Tuyệt vời! Bạn đã đạt hạng thành viên cao nhất <strong>DIAMOND</strong>.
                        </p>
                    @endif

                    <div class="custom-progress-bar mb-2">
                        <div class="custom-progress-fill" style="width: {{ $progress }}%;"></div>
                    </div>
                </div>

                <div class="pt-3 border-top border-secondary border-opacity-25 d-flex justify-content-between text-muted small">
                    <span>Hạng hiện tại: <strong class="text-white">{{ $levelName }}</strong></span>
                    <span>Hạng tiếp: <strong class="text-info">{{ $nextLevel->name ?? 'Tối đa' }}</strong></span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection