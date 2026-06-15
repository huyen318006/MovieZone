@extends('layout.app')

@vite('resources/css/booking/combo.css')

@section('content')

<div class="combo-page">

    <div class="booking-step">

    {{-- Bước 1 --}}
    <div class="step done">
        <span>✓</span>
        <p>Chọn ghế</p>
    </div>

    <div class="line"></div>

    {{-- Bước 2 --}}
    <div class="step done">
        <span>✓</span>
        <p>Chọn combo</p>
    </div>

    <div class="line"></div>

    {{-- Bước 3 --}}
    <div class="step {{ session('booking_tam.voucher_code') ? 'done' : 'active' }}">
        <span>
            {{ session('booking_tam.voucher_code') ? '✓' : '3' }}
        </span>
        <p>Chọn voucher</p>
    </div>

    <div class="line"></div>

    {{-- Bước 4 --}}
    <div class="step {{ session('booking_tam.voucher_code') ? 'active' : '' }}">
        <span>4</span>
        <p>Xác nhận</p>
    </div>

    <div class="line"></div>

    {{-- Bước 5 --}}
    <div class="step">
        <span>5</span>
        <p>Thanh toán</p>
    </div>

</div>

    <div class="combo-layout">

        <div class="combo-main">

            <div class="combo-header">
                <h1>Chọn Combo</h1>
                <p>Thêm bắp, nước uống để có trải nghiệm xem phim trọn vẹn hơn.</p>
            </div>

            <form id="comboForm" action="{{ route('booking.combo.save') }}" method="POST">
                @csrf

                <div class="combo-grid">

                    @foreach($combos as $combo)

                        <div class="combo-card">

                            <div class="combo-selected">✓</div>

                            <div class="combo-image">
                                @if(!empty($combo->image_url))
                                    <img src="{{ asset($combo->image_url) }}" alt="{{ $combo->name }}">
                                @else
                                    <img src="https://placehold.co/260x180/111827/7fa6ff?text=MovieZone" alt="{{ $combo->name }}">
                                @endif
                            </div>

                            <div class="combo-body">

                                <h3>{{ $combo->name }}</h3>

                                <p>{{ $combo->description }}</p>

                                <div class="combo-price">
                                    {{ number_format($combo->price, 0, ',', '.') }}đ
                                </div>

                                <div class="qty-control">

                                    <button type="button" class="qty-btn minus">−</button>

                                    <input
                                        type="number"
                                        min="0"
                                        value="0"
                                        class="qty-input"
                                        name="combos[{{ $combo->id }}][quantity]"
                                        data-id="{{ $combo->id }}"
                                        data-name="{{ $combo->name }}"
                                        data-price="{{ $combo->price }}"
                                    >

                                    <button type="button" class="qty-btn plus">+</button>

                                </div>

                            </div>

                        </div>

                    @endforeach

                </div>

                <div class="bottom-action">
                    <a href="{{ url()->previous() }}" class="btn-back">
                        ← Quay lại chọn ghế
                    </a>
                </div>

            </form>

        </div>

        <aside class="order-sidebar">

            <div class="sidebar-card">

                <h2>Đơn hàng của bạn</h2>

                <div class="movie-box">

                    <h4>Thông tin suất chiếu</h4>

                    <div class="movie-info">

                        <div class="movie-thumb"></div>

                        <div>
                            <strong>{{ session('booking_movie_name', 'Tên phim') }}</strong>
                            <span>{{ session('booking_cinema_name', 'MovieZone Cinema') }}</span>
                            <span>{{ session('booking_room_name', 'Phòng chiếu') }}</span>
                            <span>{{ session('booking_time', '14:30') }}</span>
                        </div>

                    </div>

                </div>

                <div class="seat-section">

                    <div class="seat-title">
                        <span>Ghế đã chọn</span>
                        <a href="{{ url()->previous() }}">Sửa</a>
                    </div>

                    <div class="seat-list">

                        @forelse(session('booking_tam.seats', []) as $seat)

                            <span class="seat-tag">{{ $seat }}</span>

                        @empty

                            <span class="empty-seat">Chưa có ghế</span>

                        @endforelse

                    </div>

                </div>

                <div class="divider"></div>

                <div id="summaryCombos">
                    <div class="empty-combo">
                        Chưa chọn combo
                    </div>
                </div>
                <div class="divider"></div>
                {{-- Áp dụng voucher nếu có để được giảm giá cho combo. --}}
                <div class="voucher-box">
                    <h4>Mã giảm giá</h4>
                    @if(session('booking_tam.voucher_code'))

                        <div class="voucher-applied">

                            <span>
                                {{ session('booking_tam.voucher_code') }}
                            </span>

                            <form action="{{ route('voucher.remove') }}"method="POST">
                                @csrf
                                <button type="submit">Huỷ</button>
                            </form>
                        </div>
                    @else

                        <form
                            action="{{ route('voucher.apply') }}"
                            method="POST"
                        >
                            @csrf

                            <div class="voucher-form">

                                <input
                                    type="text"
                                    name="code"
                                    placeholder="Nhập mã giảm giá"
                                >

                                <button type="submit">
                                    Áp dụng
                                </button>

                            </div>

                        </form>

                    @endif

                </div>
                <div class="divider"></div>
                {{-- Hiển thị giảm giá combo nếu có voucher được áp dụng. --}}
                <div class="price-summary">
                <div class="summary-row">
                    <span>Tiền vé</span>

                    <strong>
                        {{ number_format(session('booking_tam.total_seat_amount',0),0,',','.') }}đ
                    </strong>
                </div>

                <div class="summary-row">
                    <span>Combo</span>

                    <strong id="comboAmount">
                        {{ number_format(session('booking_tam.total_combo_amount',0),0,',','.') }}đ
                    </strong>
                </div>

                @if(session('booking_tam.discount_amount',0) > 0)

                    <div class="summary-row discount">
                        <span>Giảm giá</span>

                        <strong>
                            -{{ number_format(session('booking_tam.discount_amount'),0,',','.') }}đ
                        </strong>
                    </div>

                @endif

            </div>
                <div class="divider"></div>

                <div class="total-box">
                    <span>Tổng thanh toán</span>

                    <strong id="grandTotal">
                        {{ number_format(session('booking_tam.total',0),0,',','.') }}đ
                    </strong>
                </div>

                <button type="submit" form="comboForm" class="btn-next">
                    Xác nhận combo & tiếp tục
                </button>

                <p class="skip-note">
                    Có thể tiếp tục mà không chọn combo.
                </p>

            </div>

        </aside>

    </div>

</div>

@endsection

@push('scripts')

<script>
const seatAmount = {{ session('booking_tam.total_seat_amount',0) }};
const discountAmount = {{ session('booking_tam.discount_amount',0) }};
document.addEventListener('DOMContentLoaded', () => {

    const inputs = document.querySelectorAll('.qty-input');

    function formatMoney(number) {
        return number.toLocaleString('vi-VN') + 'đ';
    }

    function updateSummary() {

        let html = '';
        let total = 0;

        inputs.forEach(input => {

            let qty = parseInt(input.value) || 0;

            if (qty < 0) {
                qty = 0;
                input.value = 0;
            }

            const card = input.closest('.combo-card');

            if (qty > 0) {

                card.classList.add('selected');

                const name = input.dataset.name;
                const price = parseInt(input.dataset.price) || 0;
                const subtotal = qty * price;

                total += subtotal;

                html += `
                    <div class="combo-summary-row">
                        <span>${name}</span>
                        <span>x${qty}</span>
                        <span>${formatMoney(subtotal)}</span>
                    </div>
                `;

            } else {
                card.classList.remove('selected');
            }

        });

        if (html === '') {
            html = `
                <div class="empty-combo">
                    Chưa chọn combo
                </div>
            `;
        }

        document.getElementById('summaryCombos').innerHTML = html;
        document.getElementById('comboAmount').innerText = formatMoney(total);
        const grandTotal =
        seatAmount +
        total -
        discountAmount;

    document.getElementById('grandTotal').innerText =formatMoney(grandTotal);}

    document.querySelectorAll('.plus').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.parentElement.querySelector('.qty-input');
            input.value = (parseInt(input.value) || 0) + 1;
            updateSummary();
        });
    });

    document.querySelectorAll('.minus').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.parentElement.querySelector('.qty-input');
            const currentValue = parseInt(input.value) || 0;

            if (currentValue > 0) {
                input.value = currentValue - 1;
                updateSummary();
            }
        });
    });

    inputs.forEach(input => {
        input.addEventListener('input', updateSummary);
    });

    updateSummary();

});
</script>

@endpush