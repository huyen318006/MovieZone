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
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .membership-card-hero:hover {
        transform: translateY(-4px);
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.6);
    }

    .card-bronze {
        background: linear-gradient(135deg, #78350f 0%, #92400e 50%, #b45309 100%);
    }
    .card-silver {
        background: linear-gradient(135deg, #334155 0%, #475569 50%, #64748b 100%);
    }
    .card-gold {
        background: linear-gradient(135deg, #92400e 0%, #b45309 50%, #d97706 100%);
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
        padding: 8px 18px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.22);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.35);
        font-size: 14px;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #ffffff;
    }

    .coin-balance-display {
        font-size: clamp(36px, 5vw, 52px);
        font-weight: 900;
        line-height: 1;
        letter-spacing: -0.02em;
        color: #ffffff;
        text-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }

    .progress-box {
        background: #1e293b;
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 24px;
        padding: 28px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
    }

    .custom-progress-bar {
        height: 14px;
        border-radius: 999px;
        background: #0f172a;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .custom-progress-fill {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #3b82f6, #8b5cf6, #ec4899);
        transition: width 0.6s ease;
    }

    /* Tier Grid Styling */
    .tier-card {
        background: #1e293b;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        padding: 24px;
        transition: all 0.3s ease;
        position: relative;
    }

    .tier-card:hover {
        border-color: rgba(59, 130, 246, 0.5);
        transform: translateY(-4px);
        box-shadow: 0 16px 32px rgba(0, 0, 0, 0.4);
    }

    .tier-card.active-tier {
        border: 2px solid #3b82f6;
        background: linear-gradient(180deg, rgba(30, 41, 59, 1) 0%, rgba(30, 58, 138, 0.3) 100%);
    }

    .tier-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: grid;
        place-items: center;
        font-size: 22px;
        font-weight: bold;
        margin-bottom: 16px;
    }

    .icon-bronze { background: rgba(180, 83, 9, 0.2); color: #f59e0b; border: 1px solid rgba(180, 83, 9, 0.4); }
    .icon-silver { background: rgba(148, 163, 184, 0.2); color: #cbd5e1; border: 1px solid rgba(148, 163, 184, 0.4); }
    .icon-gold { background: rgba(217, 119, 6, 0.2); color: #fbbf24; border: 1px solid rgba(217, 119, 6, 0.4); }
    .icon-platinum { background: rgba(2, 132, 199, 0.2); color: #38bdf8; border: 1px solid rgba(2, 132, 199, 0.4); }
    .icon-diamond { background: rgba(124, 58, 237, 0.2); color: #c084fc; border: 1px solid rgba(124, 58, 237, 0.4); }
</style>
@endpush

@section('content')
<div class="container py-5 membership-page">
    <!-- Hero Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="fw-bold mb-1"><i class="bi bi-shield-check text-warning me-2"></i>Thành Viên MovieZone</h1>
            <p class="text-slate-400 mb-0" style="color: #94a3b8;">Quản lý hạng thành viên, theo dõi tích điểm và hưởng ưu đãi độc quyền</p>
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
    <div class="row g-4 mb-5">
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
                        <h5 class="fw-bold mb-0 text-white"><i class="bi bi-graph-up-arrow text-info me-2"></i>Tiến Độ Nâng Hạng</h5>
                        <span class="badge bg-info text-dark fw-bold fs-6">{{ $progress }}%</span>
                    </div>

                    @if ($nextLevel)
                        <p class="small mb-3" style="color: #cbd5e1; line-height: 1.6;">
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

                <div class="pt-3 border-top border-secondary border-opacity-25 d-flex justify-content-between small" style="color: #94a3b8;">
                    <span>Hạng hiện tại: <strong class="text-white">{{ $levelName }}</strong></span>
                    <span>Hạng tiếp: <strong class="text-info">{{ $nextLevel->name ?? 'Tối đa' }}</strong></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tiers & Perks List (Commit 2.3) -->
    <div class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-award text-warning me-2"></i>Danh Sách Hạng & Quyền Lợi</h3>
                <p class="small mb-0" style="color: #94a3b8;">Tích lũy Coin khi mua vé để mở khóa các mức giảm giá và đặc quyền độc quyền</p>
            </div>
        </div>

        <div class="row g-3">
            @foreach ($levels as $lvl)
                @php
                    $isCurrent = ($currentLevel && $currentLevel->id === $lvl->id);
                    $lvlName = strtoupper($lvl->name);
                    $iconClass = match($lvlName) {
                        'SILVER' => 'icon-silver',
                        'GOLD' => 'icon-gold',
                        'PLATINUM' => 'icon-platinum',
                        'DIAMOND' => 'icon-diamond',
                        default => 'icon-bronze',
                    };
                @endphp
                <div class="col-md-6 col-lg-4 col-xl">
                    <div class="tier-card h-100 {{ $isCurrent ? 'active-tier' : '' }}">
                        @if ($isCurrent)
                            <span class="position-absolute top-0 end-0 m-3 badge bg-primary">Hạng hiện tại</span>
                        @endif

                        <div class="tier-icon {{ $iconClass }}">
                            <i class="bi bi-gem"></i>
                        </div>

                        <h5 class="fw-bold mb-1 text-white">{{ $lvl->name }}</h5>
                        <div class="small fw-bold text-warning mb-3">
                            {{ number_format($lvl->min_points) }} Coin
                        </div>

                        <div class="pt-3 border-top border-secondary border-opacity-25">
                            <div class="d-flex align-items-center gap-2 mb-2 text-slate-300 small" style="color: #e2e8f0;">
                                <i class="bi bi-check-circle-fill text-success"></i>
                                <span>Giảm <strong>{{ number_format($lvl->discount_percent, 0) }}%</strong> khi mua vé</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-2 text-slate-300 small" style="color: #e2e8f0;">
                                <i class="bi bi-check-circle-fill text-success"></i>
                                <span>Tích lũy 10.000đ = 1 Coin</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 text-slate-300 small" style="color: #e2e8f0;">
                                <i class="bi bi-check-circle-fill text-success"></i>
                                <span>Duy trì hạng trong 6 tháng</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection