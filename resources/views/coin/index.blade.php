@extends('layout.app')

@section('content')
<style>
    .coin-page {
        animation: fadeInUp 0.9s ease;
        margin-top: 140px !important;
        padding-top: 1.25rem;
        position: relative;
        z-index: 1;
    }
    .top-card {
        border-radius: 1.5rem;
        background: radial-gradient(circle at top left, rgba(255, 205, 77, .25), transparent 35%),
                    linear-gradient(135deg, #fff9e6, #fff7d1);
        border: 1px solid rgba(255, 193, 7, .25);
        box-shadow: 0 24px 60px rgba(255, 186, 41, .12);
    }
    .top-card .coin-number {
        letter-spacing: -.05em;
    }
    .status-badge {
        border-radius: 999px;
        padding: .55rem 1rem;
        font-weight: 600;
    }
    .streak-card {
        border-radius: 1.2rem;
        overflow: hidden;
        transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
    }
    .streak-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 18px 48px rgba(0,0,0,.08);
    }
    .streak-card .card-body {
        min-height: 170px;
    }
    .streak-indicator {
        width: 48px;
        height: 48px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 1.1rem;
        font-weight: 700;
        transition: transform .3s ease;
    }
    .finished-indicator { background: #198754; color: #fff; }
    .today-indicator { background: #ffb84d; color: #212529; }
    .future-indicator { background: #f1f3f5; color: #6c757d; }
    .animate-pop { animation: popIn .55s ease both; }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(24px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes popIn {
        from { opacity: 0; transform: scale(.92); }
        to { opacity: 1; transform: scale(1); }
    }
    .progress-modern {
        height: 8px;
        border-radius: 999px;
        background: rgba(13, 110, 253, .1);
    }
    .progress-modern .progress-bar {
        border-radius: 999px;
    }
    .streak-row {
        display: flex;
        flex-wrap: nowrap;
        overflow-x: auto;
        padding-bottom: 8px;
        margin-left: -24px;
        margin-right: -24px;
        width: calc(100% + 48px);
    }
    .streak-row::-webkit-scrollbar {
        height: 8px;
    }
    .streak-row::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,.18);
        border-radius: 999px;
    }
    .streak-box {
        width: 148px;
        min-width: 148px;
        flex: 0 0 auto;
    }
</style>

<div class="container py-5 coin-page">
    <div class="row gx-4 gy-4 justify-content-center">
        @php
            $displayTodayReward = $todayReward ?? null;
            $streakVal = isset($streak) ? (int) $streak : 0;
            $completedInCycle = $streakVal > 0 ? ($streakVal % 7 === 0 ? 7 : $streakVal % 7) : 0;
            $nextRaw = !empty($checkedToday) ? $streakVal : ($streakVal + 1);
            $nextInCycle = (($nextRaw - 1) % 7) + 1;
        @endphp
        <div class="col-12 col-xl-10">
            <div class="card top-card p-4 d-flex flex-column flex-lg-row align-items-center gap-4">
                <div class="flex-grow-1">
                    <small class="text-uppercase text-muted fw-bold">Số dư COIN</small>
                    <h1 class="display-5 fw-bold mb-2 coin-number">🪙 {{ number_format($coin->balance ?? $coin ?? 0) }}</h1>
                    <p class="text-muted mb-0">Số dư của bạn được dùng để đổi vé, nhận ưu đãi và quà tặng mỗi ngày.</p>
                </div>
                <div class="text-lg-end">
                    <div class="status-badge bg-dark text-warning mb-2">Chuỗi hiện tại</div>
                    <h2 class="fw-bold mb-1">{{ $streak ?? 0 }} ngày</h2>
                    <p class="text-muted mb-0">Điểm danh liên tiếp để nhận thưởng cao hơn.</p>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-10">
            <div class="card border-0 shadow-sm p-4 animate-pop">
                <div class="row align-items-center gy-3">
                    <div class="col-md-7">
                        <h5 class="fw-semibold mb-2">Thưởng hôm nay</h5>
                        <p class="text-muted mb-3">Nhận thêm coin khi điểm danh và tiếp tục chuỗi thành công.</p>
                        <div class="d-inline-flex align-items-center gap-2 mb-3">
                            <span class="badge bg-warning text-dark status-badge">+ {{ number_format($displayTodayReward ?? 0) }} Coin</span>
                            @if(!empty($checkedToday))
                                <span class="badge bg-success text-white status-badge">Đã điểm danh</span>
                            @else
                                <span class="badge bg-secondary text-dark status-badge">Chưa điểm danh</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-5 text-center text-md-end">
                        @if(!empty($checkedToday))
                            <button class="btn btn-success btn-lg px-5 fw-semibold" disabled>✅ Đã điểm danh hôm nay</button>
                        @else
                            <form method="POST" action="{{ \App\Helpers\TabAuthHelper::route('coin.checkin', ['id' => \App\Helpers\TabAuthHelper::currentUser()->id]) }}">
                                @csrf
                                <input type="hidden" name="reward_coin" value="{{ $todayReward }}" />
                                <input type="hidden" name="todayStep" value="{{ $todayStep }}" />
                                <input type="hidden" name="checkin_date" value="{{ now()->toDateString() }}" />
                                <button class="btn btn-warning btn-lg px-5 fw-semibold">🎯 Điểm danh ngay</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm p-4 animate-pop">
                <div class="d-flex justify-content-between align-items-center mb-4 gap-3 flex-column flex-md-row">
                    <div>
                        <h5 class="fw-semibold mb-1">Chu kỳ điểm danh 7 ngày</h5>
                        <p class="text-muted mb-0">reset tự động theo chu kỳ khi vượt quá ngày 7.</p>
                    </div>
                    <div class="text-md-end">
                        <span class="badge bg-primary text-white status-badge">Chu kỳ 7 ngày</span>
                    </div>
                </div>
                <div class="streak-row">
                    @foreach($rewardTable as $day => $reward)
                        @php
                            $isCompleted = $day <= $completedInCycle;
                            $isToday = $day == $nextInCycle;
                        @endphp
                        <div class="streak-box">
                            <div class="card streak-card h-100 border-0 {{ $isCompleted ? 'shadow-lg' : 'shadow-sm' }}">
                                <div class="card-body d-flex flex-column justify-content-between p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <p class="text-muted mb-1 small">Ngày {{ $day }}</p>
                                            <h6 class="fw-bold mb-0">+{{ number_format($reward) }} coin</h6>
                                        </div>
                                        <div class="streak-indicator {{ $isCompleted ? 'finished-indicator' : ($isToday ? 'today-indicator' : 'future-indicator') }}">
                                            @if($isCompleted)✔@elseif($isToday)⭐@else○@endif
                                        </div>
                                    </div>
                                    <div class="progress-modern mb-3">
                                        <div class="progress-bar {{ $isCompleted ? 'bg-success' : ($isToday ? 'bg-warning' : 'bg-secondary') }}" role="progressbar" style="width: {{ $isCompleted ? '100%' : ($isToday ? '57%' : '20%') }};"></div>
                                    </div>
                                    <div>
                                        @if($isCompleted)
                                            <span class="text-success small">Hoàn thành</span>
                                        @elseif($isToday)
                                            <span class="text-warning small">Ngày hiện tại</span>
                                        @else
                                            <span class="text-muted small">Chưa tới</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
