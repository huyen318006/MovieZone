@extends('layout.staff')

@section('title', 'Bán Vé - Chọn Combo')
@section('page-title', 'Bán Vé — Chọn Combo')

@section('content')
<div class="sell-combo-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 text-white">Chọn combo / đồ ăn lẻ</h4>
        <a href="{{ route('staff.sell-tickets') }}" class="btn btn-outline-light btn-sm">
            <i class="bi bi-arrow-left"></i> Quay lại
        </a>
    </div>

    <div class="container-fluid px-0">
        @if (session('error'))
            <div class="alert alert-danger" role="alert" style="background:#dc3545;color:#fff;border:none;">
                {{ session('error') }}
            </div>
        @endif
        @if (session('warning'))
            <div class="alert alert-warning" role="alert" style="background:#f59e0b;color:#111827;border:none;">
                {{ session('warning') }}
            </div>
        @endif

        <div class="combo-layout">
            {{-- LEFT: Summary --}}
            <div class="combo-panel">
                <h3>THÔNG TIN</h3>

                <div class="summary-row">
                    <span>Suất chiếu</span>
                    <strong>{{ session('booking.showtime_id', '—') }}</strong>
                </div>

                <div class="summary-row">
                    <span>Ghế đã chọn</span>
                    <strong>{{ count(session('booking.seats', [])) }} ghế</strong>
                </div>

                <div class="summary-row">
                    <span>Combo / Đồ ăn lẻ</span>
                    <strong id="comboTotal">0 VNĐ</strong>
                </div>

                <div class="summary-divider"></div>

                <div class="summary-note">
                    Bấm vào sản phẩm để chọn, nhập số lượng, sau đó bấm <b>Tiếp tục</b>.
                </div>
            </div>

            {{-- RIGHT: Combo list --}}
            <div class="combo-main">
                <form action="{{ route('staff.sell-tickets.savecombo') }}" method="POST" id="comboForm">
                    @csrf

                    {{-- giữ nguyên flow seats/showtime --}}
                    <input type="hidden" name="showtime_id" value="{{ session('booking.showtime_id') }}">

                    {{-- giữ lại ghế đã chọn để submit về confirm --}}
                    @foreach((array) session('booking.seats', []) as $sid)
                        <input type="hidden" name="seats[]" value="{{ $sid }}">
                    @endforeach

                    <div class="section-title">COMBO</div>
                    <div class="combo-grid">
                        @forelse($combo as $c)
                            @php
                                $cPrice = (int) ($c->price ?? 0);
                            @endphp
                            <div class="combo-item" data-type="combo" data-id="{{ $c->id }}" data-unit="{{ $cPrice }}">
                                <div class="combo-item-top">
                                    <div class="combo-item-name">{{ $c->name }}</div>
                                    <div class="combo-item-price">{{ number_format($cPrice, 0, ',', '.') }} VNĐ</div>
                                </div>
                                <div class="combo-item-desc">{{ $c->description ?? '' }}</div>

                                <div class="combo-qty">
                                    <button type="button" class="qty-btn" data-action="dec">-</button>
                                    <input type="number" name="combo_quantities[{{ $c->id }}]" class="qty-input" value="0" min="0" max="10" step="1">
                                    <button type="button" class="qty-btn" data-action="inc">+</button>
                                </div>

                                {{-- để dễ debug UI; controller hiện chỉ cần combo_quantities/product_quantities --}}
                                <input type="hidden" name="combo_selections[]" class="combo-selections" value="">
                            </div>
                        @empty
                            <div class="empty-box">Không có combo.</div>
                        @endforelse
                    </div>

                    <div class="section-title" style="margin-top:26px;">ĐỒ ĂN LẺ (PRODUCT)</div>
                    <div class="combo-grid">
                        @forelse($product as $p)
                            @php
                                $pPrice = (int) ($p->price ?? 0);
                            @endphp
                            <div class="combo-item" data-type="product" data-id="{{ $p->id }}" data-unit="{{ $pPrice }}">
                                <div class="combo-item-top">
                                    <div class="combo-item-name">{{ $p->name }}</div>
                                    <div class="combo-item-price">{{ number_format($pPrice, 0, ',', '.') }} VNĐ</div>
                                </div>
                                <div class="combo-item-desc">{{ $p->description ?? '' }}</div>

                                <div class="combo-qty">
                                    <button type="button" class="qty-btn" data-action="dec">-</button>
                                    <input type="number" name="product_quantities[{{ $p->id }}]" class="qty-input" value="0" min="0" max="10" step="1">
                                    <button type="button" class="qty-btn" data-action="inc">+</button>
                                </div>

                                <input type="hidden" name="product_selections[]" class="product-selections" value="">
                            </div>
                        @empty
                            <div class="empty-box">Không có sản phẩm.</div>
                        @endforelse
                    </div>

                    <div class="combo-actions">
                        <button type="submit" class="btn-continue" id="btnContinue" disabled>
                            Tiếp tục
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .sell-combo-wrapper{min-height:100vh;background:#0f172a;padding:28px 0;}
    .combo-layout{display:flex;gap:22px;max-width:1400px;margin:0 auto;}
    .combo-panel{width:360px;background:#111827;border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:20px;height:fit-content;color:#fff;}
    .combo-panel h3{color:#60a5fa;margin:0 0 16px;font-weight:800;}
    .summary-row{display:flex;justify-content:space-between;gap:12px;margin-bottom:12px;color:#e5e7eb;}
    .summary-row span{color:#94a3b8;}
    .summary-divider{height:1px;background:rgba(255,255,255,.08);margin:14px 0;}
    .summary-note{color:#cbd5e1;font-size:14px;line-height:1.5;}

    .combo-main{flex:1;background:transparent;color:#fff;}
    .section-title{color:#fff;font-weight:800;margin:0 0 12px;font-size:18px;}

    .combo-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;}
    @media(max-width:1100px){.combo-grid{grid-template-columns:repeat(2,minmax(0,1fr));}.combo-layout{flex-direction:column;}.combo-panel{width:100%;}}
    @media(max-width:640px){.combo-grid{grid-template-columns:1fr;}}

    .combo-item{background:#111827;border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:14px;}
    .combo-item-top{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;}
    .combo-item-name{font-weight:800;color:#fff;}
    .combo-item-price{color:#60a5fa;font-weight:800;white-space:nowrap;}
    .combo-item-desc{margin-top:6px;color:#94a3b8;font-size:13px;min-height:34px;}

    .combo-qty{display:flex;align-items:center;gap:10px;margin-top:12px;}
    .qty-btn{width:34px;height:34px;border-radius:10px;border:1px solid rgba(255,255,255,.12);background:transparent;color:#fff;cursor:pointer;}
    .qty-btn:hover{border-color:#60a5fa;}
    .qty-input{width:90px;background:transparent;border:1px solid rgba(255,255,255,.16);border-radius:10px;color:#fff;padding:6px 10px;}

    .combo-actions{margin-top:18px;display:flex;flex-direction:column;align-items:flex-start;gap:10px;}
    .btn-continue{padding:14px 18px;border:none;border-radius:12px;background:linear-gradient(90deg,#2563eb,#60a5fa);color:#fff;font-weight:800;cursor:pointer;min-width:220px;opacity:.6;}
    .btn-continue:enabled{opacity:1;}
    .empty-box{grid-column:1/-1;color:#94a3b8;background:#111827;border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:18px;text-align:center;}
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const comboTotalEl = document.getElementById('comboTotal');
        const btnContinue = document.getElementById('btnContinue');
        const comboItems = document.querySelectorAll('.combo-item');

        function calcTotal() {
            let total = 0;
            comboItems.forEach(item => {
                const unit = parseInt(item.dataset.unit || '0');
                const input = item.querySelector('.qty-input');
                const qty = parseInt(input.value || '0');
                total += unit * qty;
            });
            comboTotalEl.textContent = total.toLocaleString('vi-VN') + ' VNĐ';

            // Bỏ ràng buộc bắt buộc phải chọn combo/product.
            // Luồng chỉ cần đã chọn ghế ở bước trước.
            btnContinue.disabled = false;
        }

        comboItems.forEach(item => {
            const input = item.querySelector('.qty-input');
            item.querySelectorAll('.qty-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const action = btn.dataset.action;
                    let v = parseInt(input.value || '0');
                    const max = parseInt(input.max || '10');
                    if (action === 'inc') v = Math.min(max, v + 1);
                    if (action === 'dec') v = Math.max(0, v - 1);
                    input.value = v;
                    calcTotal();
                });
            });
            input.addEventListener('input', calcTotal);
        });

        calcTotal();
    });
</script>
@endsection

