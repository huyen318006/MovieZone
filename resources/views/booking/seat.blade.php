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
                <p><i class="fa-solid fa-door-open"></i> Phòng chiếu: {{ $showtime->room->name }}</p>
                <p><i class="fa-solid fa-clock"></i> Khung giờ: {{ \Carbon\Carbon::parse($showtime->start_time)->format('H:i - d/m/Y') }}</p>
            </div>

            <div class="selected-seat-box">
                <div id="timer-box" style="display: none; background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-weight: bold; text-align: center;">
                    ⏳ Thời gian giữ ghế: <span id="clock">05:00</span>
                </div>

                <h3>GHẾ ĐÃ CHỌN</h3>

                <div id="selectedSeats">Chưa chọn ghế</div>

                <div class="total-price" id="totalPrice">0VNĐ

                </div>

                <form action="{{ route('booking.seats.submit') }}" method="POST" id="bookingForm">
                    @csrf
                    <input type="hidden" name="showtime_id" value="{{ $showtime->id }}">
                    <div id="hidden-seat-inputs"></div>



                    <button type="submit" class="btn-payment" id="btnPayment" disabled>
                        Vui lòng chọn ghế
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
                @foreach($seatMap as $rowLabel => $rowSeats)
                    @if(!empty($rowSeats))
                        <div class="seat-row">
                            <span class="row-label">{{ $rowLabel }}</span>

                            @foreach($rowSeats as $s)
                                @php
                                    $isDisabled = in_array($s['status'], ['SOLD', 'BLOCKED', 'LOCKED', 'HELD']) ? 'disabled' : '';

                                    if ($s['type'] === 'sweetbox') {
                                        $btnClass = 'sweet-seat-btn';
                                        $icon = '<i class="fa-solid fa-heart"></i> ';
                                    } elseif ($s['type'] === 'vip') {
                                        $btnClass = 'vip-seat-btn';
                                        $icon = '<i class="fa-solid fa-crown" style="font-size: 11px; margin-right: 2px;"></i>';
                                    } else {
                                        $btnClass = 'available-seat-btn';
                                        $icon = '';
                                    }
                                @endphp

                                <button type="button"
                                        class="seat {{ $btnClass }} {{ $s['status'] }}"
                                        data-id="{{ $s['id'] }}"
                                        data-seat="{{ $s['code'] }}"
                                        data-type="{{ $s['type'] }}"
                                        data-price="{{ $s['price'] }}"
                                        {{ $isDisabled }}>
                                    {!! $icon !!}{{ $s['label'] }}
                                </button>

                                @if($s['is_aisle']) <div class="aisle"></div> @endif
                            @endforeach

                            <span class="row-label">{{ $rowLabel }}</span>
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="seat-legend" style="margin-top: 30px;">
                <div class="legend-item"><i class="fa-solid fa-couch" style="color: #64748b;"></i><span>Thường</span></div>
                <div class="legend-item"><i class="fa-solid fa-crown vip-seat-icon"></i><span>VIP</span></div>
                <div class="legend-item"><i class="fa-solid fa-heart sweet-seat-icon"></i><span>Sweetbox</span></div>
                <div class="legend-item"><i class="fa-solid fa-couch held-seat" style="color: #28a745;"></i><span>Đã chọn</span></div>
                <div class="legend-item"><i class="fa-solid fa-couch" style="color: #ff9800;"></i><span>Đang giữ</span></div>
                <div class="legend-item"><i class="fa-solid fa-couch sold-seat" style="color: #dc3545;"></i><span>Đã bán</span></div>
            </div>
        </div>
    </div>
</section>

<style>
    .HELD_BY_ME { background-color: #28a745 !important; color: white !important; border: none !important; }
    .HELD { background-color: #ff9800 !important; color: white !important; cursor: not-allowed; opacity: 0.7;}
    .SOLD { background-color: #dc3545 !important; color: white !important; cursor: not-allowed; opacity: 0.7;}
    .BLOCKED, .LOCKED { background-color: #343a40 !important; color: white !important; cursor: not-allowed; opacity: 0.7;}

    .vip-seat-btn { color: white !important; border: 2px solid #eab308 !important; font-weight: bold !important; }
    .vip-seat-btn:hover { background-color: #eab308 !important; }
    .vip-seat-icon { color: #ffc107 !important; }

    .sweet-seat-btn {color: white !important; width: 90px !important; font-weight: bold !important; border-radius: 8px !important; margin: 3px 6px !important; transition: background-color 0.2s; }
    .sweet-seat-btn:hover { background-color: #db2777 !important; }
    .sweet-seat-icon { color: #ec4899 !important; }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const selectedSeats = new Map();
    const selectedSeatsEl = document.getElementById('selectedSeats');
    const totalPriceEl = document.getElementById('totalPrice');
    const hiddenSeatInputs = document.getElementById('hidden-seat-inputs');
    const btnPayment = document.getElementById('btnPayment');

    let timerInterval;
    let secondsLeft = {{ $secondsLeft ?? 300 }};

   // 1. TỰ ĐỘNG KHÔI PHỤC GHẾ ĐANG CHỌN (Từ class HELD_BY_ME trên màn hình)
    document.querySelectorAll('.HELD_BY_ME').forEach(btn => {
        const seatCode = btn.dataset.seat;
        selectedSeats.set(seatCode, {
            id: btn.dataset.id,
            code: seatCode,
            type: btn.dataset.type,
            price: parseInt(btn.dataset.price)
        });
    });

    // 2. NẾU CÓ GHẾ THÌ CHẠY TIMER
    if (selectedSeats.size > 0 && secondsLeft > 0) {
    updateUI();
    startTimer();
    }

    function startTimer() {
        document.getElementById('timer-box').style.display = 'block';
        timerInterval = setInterval(() => {
            secondsLeft--;
            if(secondsLeft <= 0) {
                clearInterval(timerInterval);
                location.reload();
            }
            let m = Math.floor(secondsLeft / 60).toString().padStart(2, '0');
            let s = (secondsLeft % 60).toString().padStart(2, '0');
            document.getElementById('clock').textContent = m + ':' + s;
        }, 1000);
    }

    document.querySelector('.seat-map').addEventListener('click', async (e) => {
        const btn = e.target.closest('.seat');
        if (!btn || btn.disabled) return;

        const seatIdAttr = btn.dataset.id;
        const seatCode = btn.dataset.seat;
        const seatType = btn.dataset.type;
        const seatPrice = parseInt(btn.dataset.price);

        if (!seatIdAttr) return;

        const isSelecting = !selectedSeats.has(seatCode);
        const action = isSelecting ? 'hold' : 'release';

        btn.disabled = true;
        document.getElementById('ajax-error-box').style.display = 'none';
        const seatIds = seatIdAttr.split(',');

        try {
            let allSuccess = true;
            let lastMessage = '';

            for (const sId of seatIds) {
                const response = await fetch("{{ route('booking.holdSeat') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        showtime_id: "{{ $showtime->id }}",
                        seat_id: sId,
                        action: action
                    })
                });

                const res = await response.json();
                if (!res.success) {
                    allSuccess = false;
                    lastMessage = res.message;
                    break;
                }
            }

            if (allSuccess) {
                if (isSelecting) {
                    if (selectedSeats.size === 0) startTimer();
                    selectedSeats.set(seatCode, { id: seatIdAttr, code: seatCode, type: seatType, price: seatPrice });
                    btn.classList.add('HELD_BY_ME');
                } else {
                    selectedSeats.delete(seatCode); // A2: Bỏ chọn ghế
                    btn.classList.remove('HELD_BY_ME');
                }
                updateUI();
            } else {
                document.getElementById('ajax-error-box').innerText = lastMessage || 'Có lỗi khi giữ ghế.';
                document.getElementById('ajax-error-box').style.display = 'block';
                setTimeout(() => location.reload(), 1500);
            }
        } catch (error) {
            document.getElementById('ajax-error-box').innerText = 'Lỗi kết nối hệ thống.'; // E5: Lỗi kết nối
            document.getElementById('ajax-error-box').style.display = 'block';
        } finally {
            btn.disabled = false;
        }
    });

    function updateUI() {
        hiddenSeatInputs.innerHTML = '';
        if (selectedSeats.size === 0) {
            selectedSeatsEl.innerHTML = 'Chưa chọn ghế';
            totalPriceEl.textContent = '0 VNĐ';
            btnPayment.disabled = true;
            btnPayment.textContent = 'Vui lòng chọn ghế';
        } else {
            const seatTags = [];
            let total = 0;
            selectedSeats.forEach((seat) => {
                total += seat.price;
                const typeLabel = seat.type === 'vip' ? '👑' : seat.type === 'sweetbox' ? '💕' : '🎬';
                seatTags.push(`<span class="seat-tag seat-tag-${seat.type}">${typeLabel} ${seat.code}</span>`);

                const ids = seat.id.split(',');
                ids.forEach(id => {
                    hiddenSeatInputs.innerHTML += `<input type="hidden" name="seats[]" value="${id}">`;
                });
            });
            selectedSeatsEl.innerHTML = seatTags.join(' ');
            totalPriceEl.textContent = total.toLocaleString('vi-VN') + 'VNĐ';
            btnPayment.disabled = false;
            btnPayment.textContent = `Tiếp tục`;
        }
    }
    // =================================================================
    // ĐOẠN MÃ MỚI: KIỂM TRA LỖI LẺ 1 GHẾ TRỐNG KHI BẤM TIẾP TỤC
    // =================================================================
    document.getElementById('bookingForm').addEventListener('submit', function (e) {
        const allSeats = document.querySelectorAll('.seat');
        let seatMap = {};

        // 1. Quét toàn bộ cấu hình ghế hiển thị trên màn hình hiện tại
        allSeats.forEach(seat => {
            const seatCode = seat.getAttribute('data-seat');
            if (!seatCode) return;

            // Tách chữ (Hàng) và số (Số ghế) một cách an toàn nhất
            const rowMatch = seatCode.match(/[a-zA-Z]+/);
            const numMatch = seatCode.match(/\d+/);
            if (!rowMatch || !numMatch) return;

            const row = rowMatch[0].toUpperCase();
            const num = parseInt(numMatch[0]);

            // Phân loại trạng thái ghế chuẩn theo Class hiện tại trên giao diện công cộng
            let status = 'available';
            if (seat.classList.contains('HELD_BY_ME')) {
                status = 'selected'; // Ghế khách này đang chọn
            } else if (
                seat.classList.contains('SOLD') ||
                seat.classList.contains('HELD') ||
                seat.classList.contains('BLOCKED') ||
                seat.classList.contains('LOCKED') ||
                seat.disabled
            ) {
                status = 'unavailable'; // Ghế không thể mua (đã bán/khóa)
            }

            if (!seatMap[row]) {
                seatMap[row] = {};
            }
            seatMap[row][num] = status;
        });

        let hasError = false;

        // 2. Thuật toán kiểm tra ghế trống bị cô lập thông minh
        for (let row in seatMap) {
            for (let numStr in seatMap[row]) {
                let num = parseInt(numStr);

                // CHỈ xét những ghế đang thực sự TRỐNG (available)
                if (seatMap[row][num] === 'available') {
                    // Lấy trạng thái 2 bên cạnh (Nếu không có ghế bên cạnh -> coi như là Tường/Lối đi)
                    let leftStatus = seatMap[row][num - 1] || 'wall';
                    let rightStatus = seatMap[row][num + 1] || 'wall';

                    // Ghế trống này bị chặn 2 bên nếu hàng xóm không phải là ghế trống ('available')
                    let isLeftBlocked = (leftStatus !== 'available');
                    let isRightBlocked = (rightStatus !== 'available');

                    // Nếu bị kẹp cứng cả 2 bên (Trở thành ghế lẻ duy nhất)
                    if (isLeftBlocked && isRightBlocked) {
                        // CHỈ tính là lỗi nếu ít nhất 1 trong 2 bên chặn nó là ghế DO KHÁCH NÀY ĐANG CHỌN ('selected')
                        if (leftStatus === 'selected' || rightStatus === 'selected') {
                            hasError = true;
                            break;
                        }
                    }
                }
            }
            if (hasError) break;
        }

        // 3. Xử lý xuất thông báo trực quan cho khách hàng
        if (hasError) {
            e.preventDefault(); // Chặn đứng hành động gửi form lên hệ thống thanh toán

            const errorBox = document.getElementById('ajax-error-box');
            if (errorBox) {
                errorBox.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Vị trí chọn ghế không hợp lệ! Vui lòng không để trống duy nhất 1 ghế trống bên cạnh ghế bạn chọn hoặc ở đầu/cuối hàng.';
                errorBox.style.display = 'block'; // Hiện hộp thông báo màu đỏ lên

                // Tự động cuộn màn hình mượt mà đến vùng báo lỗi để khách nhìn thấy ngay lập tức
                errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                // Phòng hờ nếu lỗi giao diện không tìm thấy thẻ div thì xài tạm alert mặc định
                alert("Vị trí chọn ghế không hợp lệ! Vui lòng không để trống duy nhất 1 ghế trống bên cạnh ghế bạn chọn.");
            }
        }
    });
    });

</script>
@endsection
