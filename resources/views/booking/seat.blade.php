@extends('layout.app')

@section('content')

<section class="seat-page">

<div class="seat-wrapper">
<div class="seat-info">

    <img src="{{ asset('assets/hero/avatar.jpg') }}" class="movie-poster" alt="Movie Poster">

    <h2>THÔNG TIN ĐẶT VÉ</h2>

    <div class="booking-summary">
        <p><i class="fa-solid fa-film"></i> Phim: Avatar: Dòng Chảy Của Nước</p>
        <p><i class="fa-solid fa-building"></i> Rạp: CGV Vincom</p>
        <p><i class="fa-solid fa-door-open"></i> Phòng chiếu: P05 - 2D</p>
        <p><i class="fa-solid fa-clock"></i> Khung giờ: 20:00 - 23:15</p>
        <p><i class="fa-solid fa-ticket"></i> Giá vé: 80.000đ - 200.000đ</p>
        <p><i class="fa-solid fa-chair"></i> Ghế còn lại: 145</p>
    </div>

    <div class="selected-seat-box">

        <h3>GHẾ ĐÃ CHỌN</h3>

        <div id="selectedSeats">
            Chưa chọn ghế
        </div>

        <div class="total-price" id="totalPrice">
            0đ
        </div>

        <form action="{{ route('booking.checkout') }}" method="POST" id="bookingForm">
            @csrf
            <input type="hidden" name="seats" id="seatsInput" value="[]">
            <input type="hidden" name="movie_title" value="Avatar: Dòng Chảy Của Nước">
            <input type="hidden" name="cinema" value="CGV Vincom">
            <input type="hidden" name="room" value="P05 - 2D">
            <input type="hidden" name="showtime" value="20:00 - 23:15">
            <input type="hidden" name="show_date" value="{{ now()->format('d/m/Y') }}">
            <input type="hidden" name="format" value="2D">

            <div class="email-input-group" style="margin-bottom: 16px;">
                <label for="customerEmail" style="display: block; color: #94a3b8; font-size: 13px; margin-bottom: 6px;">
                    <i class="fa-solid fa-envelope"></i> Email nhận hoá đơn <span style="color: #ef4444;">*</span>
                </label>
                <input
                    type="email"
                    name="customer_email"
                    id="customerEmail"
                    required
                    placeholder="Nhập email của bạn..."
                    value="{{ auth()->check() ? auth()->user()->email : '' }}"
                    style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #334155; background: #0f172a; color: #e2e8f0; font-size: 14px; outline: none; box-sizing: border-box; transition: border-color 0.2s;"
                    onfocus="this.style.borderColor='#8b5cf6'"
                    onblur="this.style.borderColor='#334155'"
                >
                <small style="color: #64748b; font-size: 11px; margin-top: 4px; display: block;">
                    Hoá đơn sẽ được gửi đến email này sau khi thanh toán
                </small>
            </div>

            <button type="submit" class="btn-payment" id="btnPayment" disabled>
                Tiếp Tục Thanh Toán
            </button>
        </form>

    </div>

</div>

<div class="seat-map-container">

    <div class="screen-wrapper">
        <div class="screen-curve"></div>
        <div class="screen-text">SCREEN</div>
    </div>

    <div class="seat-map">

        @foreach(range('G','A') as $row)

        <div class="seat-row">

            <span class="row-label">{{ $row }}</span>

            @for($i = 1; $i <= 16; $i++)

                <button type="button" class="seat available-seat-btn" data-seat="{{ $row.$i }}" data-type="standard" data-price="10000">
                    {{ $i }}
                </button>

                @if($i == 8)
                    <div class="aisle"></div>
                @endif

            @endfor

            <span class="row-label">{{ $row }}</span>

        </div>

        @endforeach

    </div>

    <div class="vip-row">

        @for($i = 1; $i <= 4; $i++)
        <button type="button" class="seat vip-seat-btn" data-seat="VIP{{ $i }}" data-type="vip" data-price="150000">
            VIP{{ $i }}
        </button>
        @endfor

        <div class="aisle"></div>

        @for($i = 5; $i <= 7; $i++)
        <button type="button" class="seat vip-seat-btn" data-seat="VIP{{ $i }}" data-type="vip" data-price="150000">
            VIP{{ $i }}
        </button>
        @endfor

    </div>

    <div class="sweetbox-row">

        @for($i = 1; $i <= 4; $i++)
        <button type="button" class="seat sweet-seat-btn" data-seat="SW{{ $i }}" data-type="sweetbox" data-price="200000">
            <i class="fa-solid fa-heart"></i> {{ $i }}
        </button>
        @endfor

        <div class="aisle"></div>

        @for($i = 5; $i <= 8; $i++)
        <button type="button" class="seat sweet-seat-btn" data-seat="SW{{ $i }}" data-type="sweetbox" data-price="200000">
            <i class="fa-solid fa-heart"></i> {{ $i }}
        </button>
        @endfor

    </div>

    <div class="seat-legend">

        <div class="legend-item">
            <i class="fa-solid fa-couch available-seat"></i>
            <span>Thường - 80K</span>
        </div>

        <div class="legend-item">
            <i class="fa-solid fa-crown vip-seat-icon"></i>
            <span>VIP - 150K</span>
        </div>

        <div class="legend-item">
            <i class="fa-solid fa-heart sweet-seat-icon"></i>
            <span>Sweetbox - 200K</span>
        </div>

        <div class="legend-item">
            <i class="fa-solid fa-couch held-seat"></i>
            <span>Đã chọn</span>
        </div>

        <div class="legend-item">
            <i class="fa-solid fa-couch sold-seat"></i>
            <span>Đã bán</span>
        </div>

    </div>

</div>

</div>

</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const selectedSeats = new Map();
    const selectedSeatsEl = document.getElementById('selectedSeats');
    const totalPriceEl = document.getElementById('totalPrice');
    const seatsInput = document.getElementById('seatsInput');
    const btnPayment = document.getElementById('btnPayment');

    document.querySelectorAll('.seat[data-seat]').forEach(btn => {
        btn.addEventListener('click', () => {
            const seatCode = btn.dataset.seat;
            const seatType = btn.dataset.type;
            const seatPrice = parseInt(btn.dataset.price);

            if (selectedSeats.has(seatCode)) {
                selectedSeats.delete(seatCode);
                btn.classList.remove('selected');
            } else {
                selectedSeats.set(seatCode, {
                    code: seatCode,
                    type: seatType,
                    price: seatPrice,
                });
                btn.classList.add('selected');
            }

            updateUI();
        });
    });

    function updateUI() {
        if (selectedSeats.size === 0) {
            selectedSeatsEl.innerHTML = 'Chưa chọn ghế';
            totalPriceEl.textContent = '0đ';
            btnPayment.disabled = true;
            btnPayment.textContent = 'Tiếp Tục Thanh Toán';
        } else {
            const seatTags = [];
            let total = 0;

            selectedSeats.forEach((seat) => {
                total += seat.price;
                const typeLabel = seat.type === 'vip' ? '👑' : seat.type === 'sweetbox' ? '💕' : '🎬';
                seatTags.push(
                    `<span class="seat-tag seat-tag-${seat.type}">${typeLabel} ${seat.code}</span>`
                );
            });

            selectedSeatsEl.innerHTML = seatTags.join('');
            totalPriceEl.textContent = total.toLocaleString('vi-VN') + 'đ';
            btnPayment.disabled = false;
            btnPayment.textContent = `Thanh Toán ${total.toLocaleString('vi-VN')}đ →`;
        }

        seatsInput.value = JSON.stringify(Array.from(selectedSeats.values()));
    }
});
</script>

@endsection
