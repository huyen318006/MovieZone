@extends('layout.app')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<section class="seat-page">
    <div class="seat-wrapper">
        <div class="seat-info">
            <img src="{{ asset('assets/hero/avatar.jpg') }}" class="movie-poster" alt="Movie Poster">
            <h2>THÔNG TIN ĐẶT VÉ</h2>

            <div class="booking-summary">
                <p><i class="fa-solid fa-film"></i> Phim: {{ $showtime->movie->title }}</p>
                <p><i class="fa-solid fa-building"></i> Rạp: {{ $showtime->cinema->name }}</p>
                <p><i class="fa-solid fa-door-open"></i> Phòng chiếu: {{ $showtime->room->name }}</p>
                <p><i class="fa-solid fa-clock"></i> Khung giờ: {{ \Carbon\Carbon::parse($showtime->start_time)->format('H:i - d/m/Y') }}</p>
            </div>

            <div class="selected-seat-box">
                <div id="timer-box" style="display: none; background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-weight: bold; text-align: center;">
                    ⏳ Thời gian giữ ghế: <span id="clock">05:00</span>
                </div>

                <h3>GHẾ ĐÃ CHỌN</h3>
                <div id="selectedSeats">Chưa chọn ghế</div>
                <div class="total-price" id="totalPrice">0đ</div>

                <form action="{{ route('booking.seats.submit') }}" method="POST" id="bookingForm">
                    @csrf
                    <input type="hidden" name="showtime_id" value="{{ $showtime->id }}">
                    <div id="hidden-seat-inputs"></div>

                    <button type="submit" class="btn-payment" id="btnPayment" disabled>
                        Tiếp Tục Xác Nhận
                    </button>
                </form>
            </div>
        </div>

        <div class="seat-map-container">
            <div class="screen-wrapper">
                <div class="screen-curve"></div>
                <div class="screen-text">SCREEN</div>
            </div>

            @if(session('error'))
                <div class="alert alert-danger" style="color: white; background: #dc3545; padding: 10px; text-align: center; margin-bottom: 20px; border-radius: 5px;">
                    {{ session('error') }}
                </div>
            @endif
            <div id="ajax-error-box" style="display: none; color: white; background: #dc3545; padding: 10px; text-align: center; margin-bottom: 20px; border-radius: 5px;"></div>

            <div class="seat-map">
               @php

        function getDbSeat($seats,$row,$number)
        {
            return $seats->first(function($seat) use ($row,$number){

                return
                    $seat->seat->row_label == $row
                    &&
                    $seat->seat->seat_number == $number;

            });
        }

        @endphp     
                @foreach(
                    $seats
                    ->pluck('seat.row_label')
                    ->unique()
                    ->sortDesc()
                    as $row
                    )
                <div class="seat-row">
                    <span class="row-label">{{ $row }}</span>
                    @for($i = 1; $i <= 16; $i++)
                        @php
                            $dbSeat = getDbSeat($seats, $row, $i);
                        @endphp
                        @if($dbSeat)
                            <button type="button"
                                    class="seat available-seat-btn {{ $dbSeat->display_status }}"
                                    data-id="{{ $dbSeat->id }}"
                                    data-seat="{{ $dbSeat->seat->seat_code }}"
                                    data-type="standard"
                                    data-price="{{ $dbSeat->price }}"
                                    {{ in_array($dbSeat->display_status, ['SOLD','BLOCKED','HELD']) ? 'disabled' : '' }}>
                                {{ $i }}
                            </button>
                        @endif

                        @if($i == 8) <div class="aisle"></div> @endif
                    @endfor
                    <span class="row-label">{{ $row }}</span>
                </div>
                @endforeach
            </div>

            <div class="seat-legend">
                <div class="legend-item"><i class="fa-solid fa-couch available-seat"></i><span>Thường</span></div>
                <div class="legend-item"><i class="fa-solid fa-couch held-seat" style="color: #28a745;"></i><span>Đã chọn</span></div>
                <div class="legend-item"><i class="fa-solid fa-couch sold-seat" style="color: #dc3545;"></i><span>Đã bán</span></div>
            </div>
        </div>
    </div>
</section>

<style>
    /* CSS Trạng thái màu sắc để map JS */
    .HELD_BY_ME { background-color: #28a745 !important; color: white !important; border: none; }
    .HELD { background-color: #ff9800 !important; color: white !important; cursor: not-allowed; opacity: 0.7;}
    .SOLD { background-color: #dc3545 !important; color: white !important; cursor: not-allowed; opacity: 0.7;}
    .BLOCKED { background-color: #343a40 !important; color: white !important; cursor: not-allowed; opacity: 0.7;}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const selectedSeats = new Map();
    const selectedSeatsEl = document.getElementById('selectedSeats');
    const totalPriceEl = document.getElementById('totalPrice');
    const hiddenSeatInputs = document.getElementById('hidden-seat-inputs');
    const btnPayment = document.getElementById('btnPayment');
    
    let timerInterval;
    let secondsLeft = 300; // 5 phút

    function startTimer() {
        document.getElementById('timer-box').style.display = 'block';
        timerInterval = setInterval(() => {
            secondsLeft--;
            if(secondsLeft <= 0) {
                clearInterval(timerInterval);
                alert('E4: Đã hết thời gian 5 phút giữ ghế! Hệ thống sẽ tải lại trang và giải phóng ghế.');
                location.reload();
            }
            let m = Math.floor(secondsLeft / 60).toString().padStart(2, '0');
            let s = (secondsLeft % 60).toString().padStart(2, '0');
            document.getElementById('clock').textContent = m + ':' + s;
        }, 1000);
    }

    document.querySelectorAll('.seat[data-seat]:not([disabled])').forEach(btn => {
        // Gắn sẵn ghế HELD_BY_ME từ Cache nếu khách F5 lại trang
        if (btn.classList.contains('HELD_BY_ME')) {
            const code = btn.dataset.seat;
            selectedSeats.set(code, { 
                id: btn.dataset.id, 
                code: code, 
                type: btn.dataset.type, 
                price: parseInt(btn.dataset.price) 
            });
            updateUI();
            if(secondsLeft === 300) startTimer(); // Chạy đồng hồ
        }

        btn.addEventListener('click', async () => {
            const seatId = btn.dataset.id;
            const seatCode = btn.dataset.seat;
            const seatType = btn.dataset.type;
            const seatPrice = parseInt(btn.dataset.price);
            
            const isSelecting = !selectedSeats.has(seatCode);
            const action = isSelecting ? 'hold' : 'release';

            btn.disabled = true;
            document.getElementById('ajax-error-box').style.display = 'none';

            try {
                const response = await fetch("{{ route('booking.holdSeat') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        showtime_id: "{{ $showtime->id }}",
                        seat_id: seatId,
                        action: action
                    })
                });

                const res = await response.json();

                if (res.success) {
                    if (isSelecting) {
                        if (selectedSeats.size === 0 && secondsLeft === 300) startTimer();
                        selectedSeats.set(seatCode, { id: seatId, code: seatCode, type: seatType, price: seatPrice });
                        btn.classList.add('HELD_BY_ME');
                        btn.classList.remove('AVAILABLE');
                    } else {
                        selectedSeats.delete(seatCode);
                        btn.classList.remove('HELD_BY_ME');
                        btn.classList.add('AVAILABLE');
                    }
                    updateUI();
                } else {
                    document.getElementById('ajax-error-box').innerText = res.message;
                    document.getElementById('ajax-error-box').style.display = 'block';
                    btn.classList.remove('AVAILABLE');
                    btn.classList.add(res.error_type); // Thêm class SOLD, HELD,...
                }
            } catch (error) {
                document.getElementById('ajax-error-box').innerText = 'E5: Lỗi kết nối hệ thống. Vui lòng tải lại trang.';
                document.getElementById('ajax-error-box').style.display = 'block';
            } finally {
                // Đảm bảo nút luôn được mở lại nếu không thuộc nhóm bị chặn
                const isUnavailable = btn.classList.contains('SOLD') || 
                                      btn.classList.contains('HELD') || 
                                      btn.classList.contains('BLOCKED');
                if (!isUnavailable) {
                    btn.disabled = false;
                }
            }
        });
    });

    function updateUI() {
        hiddenSeatInputs.innerHTML = ''; 

        if (selectedSeats.size === 0) {
            selectedSeatsEl.innerHTML = 'Chưa chọn ghế';
            totalPriceEl.textContent = '0đ';
            btnPayment.disabled = true;
        } else {
            const seatTags = [];
            let total = 0;

            selectedSeats.forEach((seat) => {
                total += seat.price;
                seatTags.push(`<span class="seat-tag">${seat.code}</span>`);
                hiddenSeatInputs.innerHTML += `<input type="hidden" name="seats[]" value="${seat.id}">`;
            });

            selectedSeatsEl.innerHTML = seatTags.join(', ');
            totalPriceEl.textContent = total.toLocaleString('vi-VN') + 'đ';
            btnPayment.disabled = false;
        }
    }
});
</script>
@endsection