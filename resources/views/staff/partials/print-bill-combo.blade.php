{{-- Partial: Phiếu nhận Combo --}}
<div class="bill-header">
    <h1>MOVIEZONE</h1>
    <div class="subtitle">Phiếu Nhận Combo</div>
    <div class="booking-code">{{ $booking->booking_code }}</div>
</div>

<div class="info-grid">
    <div class="info-item">
        <span class="info-label">Trạng thái TT</span>
        <span class="info-value" style="color: {{ $booking->status === 'PAID' ? '#059669' : '#dc2626' }};">
            {{ $booking->status === 'PAID' ? '✓ Đã thanh toán' : $booking->status }}
        </span>
    </div>
    <div class="info-item">
        <span class="info-label">Thời gian in</span>
        <span class="val">{{ now()->format('d/m/Y H:i:s') }}</span>
    </div>
</div>

<div class="section-title">🍿 Chi tiết Combo</div>
<table class="seats-table" style="margin-top: 10px;">
    <thead>
        <tr>
            <th>Tên Combo / Thành phần</th>
            <th style="text-align:center;">SL</th>
            <th style="text-align:right;">Giá</th>
        </tr>
    </thead>
    <tbody>
        @foreach($booking->bookingCombos as $bc)
        <tr>
            <td>
                <strong>{{ $bc->combo?->name ?? 'Combo' }}</strong>
                <div style="font-size: 11px; color: #666; margin-top: 4px;">
                    @if($bc->combo && $bc->combo->products && $bc->combo->products->isNotEmpty())
                        @foreach($bc->combo->products as $product)
                            - {{ $product->name }} (x{{ $product->pivot->quantity }})<br>
                        @endforeach
                    @else
                        {{ $bc->combo?->description ?? '' }}
                    @endif
                </div>
            </td>
            <td style="text-align:center; font-weight: bold; font-size: 14px; vertical-align: top;">
                {{ $bc->quantity }}
            </td>
            <td style="text-align:right; vertical-align: top;">
                {{ number_format($bc->total_price, 0, ',', '.') }}đ
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="total-row">
    <span>Tổng tiền Combo</span>
    <span>{{ number_format($booking->total_combo_amount, 0, ',', '.') }}đ</span>
</div>

<div style="text-align: center; padding: 15px 0; margin-top: 20px; border-top: 1px dashed #ccc;">
    <div style="font-size: 11px; color: #666; margin-bottom: 5px;">
        Vui lòng đưa phiếu này cho quầy bắp nước để nhận phần ăn của bạn.
    </div>
</div>

<div class="bill-footer">
    Powered by MovieZone • Phiếu Bắp Nước
</div>
