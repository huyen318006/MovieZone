@extends('layout.app')

@section('title', 'Membership - Thẻ Thành Viên')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    .membership-wrapper {
        background-color: #0b1120 !important;
        min-height: 80vh;
        color: #f8fafc !important;
    }

    /* Main Banner Card */
    .membership-card-hero {
        position: relative;
        overflow: hidden;
        border-radius: 24px;
        padding: 32px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.7) !important;
        border: 2px solid rgba(255, 255, 255, 0.25) !important;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .membership-card-hero:hover {
        transform: translateY(-4px);
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.8) !important;
    }

    .card-bronze {
        background: linear-gradient(135deg, #78350f 0%, #b45309 50%, #d97706 100%) !important;
    }
    .card-silver {
        background: linear-gradient(135deg, #334155 0%, #64748b 50%, #94a3b8 100%) !important;
    }
    .card-gold {
        background: linear-gradient(135deg, #92400e 0%, #d97706 50%, #f59e0b 100%) !important;
    }
    .card-platinum {
        background: linear-gradient(135deg, #0369a1 0%, #0284c7 50%, #6366f1 100%) !important;
    }
    .card-diamond {
        background: linear-gradient(135deg, #581c87 0%, #7c3aed 50%, #2563eb 100%) !important;
    }

    .membership-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 18px;
        border-radius: 999px;
        background: rgba(0, 0, 0, 0.45) !important;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.35) !important;
        font-size: 14px;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #ffffff !important;
    }

    .coin-balance-display {
        font-size: clamp(36px, 5vw, 52px);
        font-weight: 900;
        line-height: 1;
        letter-spacing: -0.02em;
        color: #ffffff !important;
        text-shadow: 0 4px 12px rgba(0,0,0,0.5);
    }

    /* Box Container */
    .membership-box {
        background: #1e293b !important;
        border: 1px solid #334155 !important;
        border-radius: 24px;
        padding: 28px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5) !important;
    }

    .custom-progress-bar {
        height: 16px;
        border-radius: 999px;
        background: #0f172a !important;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
    }

    .custom-progress-fill {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #3b82f6, #8b5cf6, #ec4899) !important;
        transition: width 0.6s ease;
    }

    /* Checkin Steps Widget */
    .checkin-step-pill {
        background: #0f172a;
        border: 1px solid #334155;
        border-radius: 16px;
        padding: 12px;
        text-align: center;
        transition: all 0.2s ease;
    }

    .checkin-step-pill.active {
        background: rgba(245, 158, 11, 0.15);
        border-color: #f59e0b;
    }

    .checkin-step-pill.completed {
        background: rgba(34, 197, 94, 0.15);
        border-color: #22c55e;
    }

    /* Voucher Cards Styling */
    .voucher-card {
        background: #1e293b !important;
        border: 1px dashed #475569 !important;
        border-radius: 18px;
        padding: 20px;
        position: relative;
        transition: all 0.3s ease;
    }

    .voucher-card:hover {
        border-color: #f59e0b !important;
        transform: translateY(-2px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.4) !important;
    }

    .voucher-code-pill {
        font-family: monospace;
        font-size: 16px;
        font-weight: 800;
        letter-spacing: 1px;
        color: #fbbf24 !important;
        background: rgba(245, 158, 11, 0.15);
        padding: 4px 12px;
        border-radius: 8px;
        border: 1px solid rgba(245, 158, 11, 0.3);
        display: inline-block;
    }

    /* 5 Tier Cards Grid */
    .tier-card {
        background: #1e293b !important;
        border: 1px solid #334155 !important;
        border-radius: 20px;
        padding: 24px;
        transition: all 0.3s ease;
        position: relative;
        box-shadow: 0 10px 25px rgba(0,0,0,0.3) !important;
    }

    .tier-card:hover {
        border-color: #3b82f6 !important;
        transform: translateY(-4px);
        box-shadow: 0 18px 36px rgba(0, 0, 0, 0.6) !important;
    }

    .tier-card.active-tier {
        border: 2px solid #3b82f6 !important;
        background: linear-gradient(180deg, #1e293b 0%, #1e3a8a 100%) !important;
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

    .icon-bronze { background: rgba(180, 83, 9, 0.35) !important; color: #fbbf24 !important; border: 1px solid rgba(180, 83, 9, 0.6) !important; }
    .icon-silver { background: rgba(148, 163, 184, 0.35) !important; color: #f1f5f9 !important; border: 1px solid rgba(148, 163, 184, 0.6) !important; }
    .icon-gold { background: rgba(217, 119, 6, 0.35) !important; color: #fbbf24 !important; border: 1px solid rgba(217, 119, 6, 0.6) !important; }
    .icon-platinum { background: rgba(2, 132, 199, 0.35) !important; color: #38bdf8 !important; border: 1px solid rgba(2, 132, 199, 0.6) !important; }
    .icon-diamond { background: rgba(124, 58, 237, 0.35) !important; color: #c084fc !important; border: 1px solid rgba(124, 58, 237, 0.6) !important; }
</style>

<div class="membership-wrapper py-5">
    <div class="container">
        <!-- Flash Alert Messages -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-4 mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show rounded-4 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Hero Header -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="fw-bold mb-1 text-white"><i class="bi bi-shield-check text-warning me-2"></i>Thành Viên MovieZone</h1>
                <p class="mb-0" style="color: #cbd5e1 !important;">Quản lý hạng thành viên, theo dõi tích điểm và hưởng ưu đãi độc quyền</p>
            </div>
            <a href="{{ route('profile') }}" class="btn btn-outline-light rounded-pill px-4 fw-bold">
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
                        <span class="text-white small fw-bold" style="letter-spacing: 1px;">MOVIEZONE VIP MEMBER</span>
                    </div>

                    <div class="mb-4">
                        <span class="text-white-50 text-uppercase small fw-bold d-block mb-1">Số dư Coin tích lũy</span>
                        <div class="coin-balance-display">
                            🪙 {{ number_format($coins) }} <span class="fs-6 fw-normal text-white-50">Coin</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between pt-3 border-top border-white-10 text-white small">
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
                <div class="membership-box h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="fw-bold mb-0 text-white"><i class="bi bi-graph-up-arrow text-info me-2"></i>Tiến Độ Nâng Hạng</h5>
                            <span class="badge bg-info text-dark fw-bold fs-6">{{ $progress }}%</span>
                        </div>

                        @if ($nextLevel)
                            <p class="small mb-3" style="color: #e2e8f0 !important; line-height: 1.6;">
                                Bạn đang có <strong class="text-white">{{ number_format($coins) }} Coin</strong>.
                                Cần tích lũy thêm <strong class="text-warning fw-bold">{{ number_format($pointsNeeded) }} Coin</strong>
                                để thăng hạng <strong class="text-info fw-bold">{{ $nextLevel->name }}</strong> (Mốc {{ number_format($nextLevel->min_points) }} Coin).
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

                    <div class="pt-3 border-top border-secondary border-opacity-25 d-flex justify-content-between small" style="color: #cbd5e1 !important;">
                        <span>Hạng hiện tại: <strong class="text-white">{{ $levelName }}</strong></span>
                        <span>Hạng tiếp: <strong class="text-info fw-bold">{{ $nextLevel->name ?? 'Tối đa' }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Check-in Widget Box -->
        <div class="membership-box mb-5">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div>
                    <h4 class="fw-bold mb-1 text-white"><i class="bi bi-calendar-check-fill text-warning me-2"></i>Điểm Danh Hằng Ngày Nhận Coin</h4>
                    <p class="small mb-0" style="color: #cbd5e1 !important;">Điểm danh mỗi ngày để tích lũy Coin thăng hạng nhanh hơn! Chuỗi hiện tại: <strong class="text-warning">{{ $streak }} ngày</strong></p>
                </div>

                <div>
                    @if(!$checkedToday)
                        <form action="{{ route('coin.checkin', $user->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="reward_coin" value="{{ $todayReward }}">
                            <input type="hidden" name="todayStep" value="{{ $todayStep }}">
                            <input type="hidden" name="checkin_date" value="{{ date('Y-m-d') }}">
                            <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold shadow">
                                <i class="bi bi-hand-thumbs-up-fill me-1"></i> Điểm Danh Ngay (+{{ $todayReward }} Coin)
                            </button>
                        </form>
                    @else
                        <button class="btn btn-success rounded-pill px-4 fw-bold" disabled>
                            <i class="bi bi-check-circle-fill me-1"></i> Đã Điểm Danh Hôm Nay (+{{ $todayReward }} Coin)
                        </button>
                    @endif
                </div>
            </div>

            <!-- 7-Day Steps Bar -->
            <div class="row g-2">
                @for ($i = 1; $i <= 7; $i++)
                    @php
                        $isCurrentStep = ($i == $todayStep);
                        $isDone = ($i < $todayStep) || ($isCurrentStep && $checkedToday);
                        $stepClass = $isDone ? 'completed' : ($isCurrentStep ? 'active' : '');
                        $reward = $rewardTable[$i] ?? 100;
                    @endphp
                    <div class="col">
                        <div class="checkin-step-pill {{ $stepClass }}">
                            <div class="small fw-bold text-white-50 mb-1">Ngày {{ $i }}</div>
                            <div class="fw-bold text-warning small">🪙 {{ $reward }}</div>
                            <div class="mt-1">
                                @if($isDone)
                                    <i class="bi bi-check-circle-fill text-success fs-6"></i>
                                @elseif($isCurrentStep)
                                    <i class="bi bi-arrow-up-circle-fill text-warning fs-6"></i>
                                @else
                                    <i class="bi bi-circle text-white-50 fs-6"></i>
                                @endif
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
        </div>

        <!-- Customer Voucher Wallet Section (Commit 3.2) -->
        <div class="mb-5">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h3 class="fw-bold mb-1 text-white"><i class="bi bi-ticket-perforated-fill text-warning me-2"></i>Kho Voucher Cá Nhân</h3>
                    <p class="small mb-0" style="color: #cbd5e1 !important;">Mã giảm giá và ưu đãi độc quyền sẵn sàng sử dụng khi mua vé</p>
                </div>
            </div>

            @if(isset($vouchers) && $vouchers->isNotEmpty())
                <div class="row g-3">
                    @foreach($vouchers as $v)
                        <div class="col-md-6 col-lg-4">
                            <div class="voucher-card h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <span class="voucher-code-pill">{{ $v->code }}</span>
                                        @if($v->status_state === 'AVAILABLE')
                                            @if(!is_null($v->days_remaining) && $v->days_remaining <= 3)
                                                <span class="badge bg-danger">🔥 Sắp hết hạn (Còn {{ $v->days_remaining }} ngày)</span>
                                            @else
                                                <span class="badge bg-success">Chưa dùng</span>
                                            @endif
                                        @elseif($v->status_state === 'USED')
                                            <span class="badge bg-secondary">Đã sử dụng</span>
                                        @else
                                            <span class="badge bg-danger">Hết hạn</span>
                                        @endif
                                    </div>

                                    <h5 class="fw-bold text-white mb-2">
                                        @if($v->discount_type === 'percent')
                                            Giảm {{ number_format($v->discount_value, 0) }}% tổng hóa đơn
                                        @else
                                            Giảm {{ number_format($v->discount_value, 0) }}đ khi mua vé
                                        @endif
                                    </h5>

                                    <p class="small mb-2" style="color: #cbd5e1 !important;">
                                        Áp dụng cho đơn từ {{ number_format($v->min_order_amount ?? 0) }}đ
                                    </p>
                                </div>

                                <div class="pt-3 border-top border-secondary border-opacity-25 d-flex align-items-center justify-content-between">
                                    <span class="small" style="color: #94a3b8 !important;">
                                        <i class="bi bi-clock me-1"></i> HSD: {{ $v->end_date ? $v->end_date->format('d/m/Y') : 'Vô thời hạn' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="membership-box text-center py-5">
                    <i class="bi bi-ticket-perforated fs-1 text-white-50 d-block mb-3"></i>
                    <h5 class="fw-bold text-white">Bạn chưa có Voucher nào trong kho</h5>
                    <p class="small mb-0" style="color: #cbd5e1 !important;">Theo dõi các chương trình khuyến mãi của MovieZone để nhận ưu đãi nhé!</p>
                </div>
            @endif
        </div>

        <!-- Tiers & Perks List (Commit 2.3) -->
        <div class="mb-4">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h3 class="fw-bold mb-1 text-white"><i class="bi bi-award text-warning me-2"></i>Danh Sách Hạng & Quyền Lợi</h3>
                    <p class="small mb-0" style="color: #cbd5e1 !important;">Tích lũy Coin khi mua vé để mở khóa các mức giảm giá và đặc quyền độc quyền</p>
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
                                <span class="position-absolute top-0 end-0 m-3 badge bg-primary fw-bold">Hạng hiện tại</span>
                            @endif

                            <div class="tier-icon {{ $iconClass }}">
                                <i class="bi bi-gem"></i>
                            </div>

                            <h5 class="fw-bold mb-1 text-white">{{ $lvl->name }}</h5>
                            <div class="small fw-bold text-warning mb-3">
                                {{ number_format($lvl->min_points) }} Coin
                            </div>

                            <div class="pt-3 border-top border-secondary border-opacity-25">
                                <div class="d-flex align-items-center gap-2 mb-2 small" style="color: #f1f5f9 !important;">
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                    <span>Giảm <strong>{{ number_format($lvl->discount_percent, 0) }}%</strong> khi mua vé</span>
                                </div>
                                <div class="d-flex align-items-center gap-2 mb-2 small" style="color: #f1f5f9 !important;">
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                    <span>Tích lũy 10.000đ = 1 Coin</span>
                                </div>
                                <div class="d-flex align-items-center gap-2 small" style="color: #f1f5f9 !important;">
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
</div>
@endsection