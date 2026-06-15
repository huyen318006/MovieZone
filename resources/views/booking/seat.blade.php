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

                <div class="total-price" id="totalPrice">0VNĐ
                    
                </div>

                <form action="{{ route('booking.seats.submit') }}" method="POST" id="bookingForm">
                    @csrf
                    <input type="hidden" name="showtime_id" value="{{ $showtime->id }}">
                    <div id="hidden-seat-inputs"></div>

                    <div class="email-input-group" style="margin-top: 20px; margin-bottom: 16px;">
                        <label for="customerEmail" style="display: block; color: #94a3b8; font-size: 13px; margin-bottom: 6px;">
                            <i class="fa-solid fa-envelope"></i> Email nhận hoá đơn <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="email" name="customer_email" id="customerEmail" required
                            placeholder="Nhập email của bạn..."
                            value="{{ auth()->check() ? auth()->user()->email : '' }}"
                            style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #334155; background: #0f172a; color: #e2e8f0; font-size: 14px; outline: none; box-sizing: border-box; transition: border-color 0.2s;"
                            onfocus="this.style.borderColor='#8b5cf6'"
                            onblur="this.style.borderColor='#334155'">
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

            @if(session('error'))
                <div class="alert alert-danger" style="color: white; background: #dc3545; padding: 10px; text-align: center; margin-bottom: 20px; border-radius: 5px;">
                    {{ session('error') }}
                </div>
            @endif
            <div id="ajax-error-box" style="display: none; color: white; background: #dc3545; padding: 10px; text-align: center; margin-bottom: 20px; border-radius: 5px;"></div>

            <div class="seat-map">
                @foreach(range('A','J') as $rowLabel)
                    @if(!empty($seatMap[$rowLabel]))
                        <div class="seat-row">
                            <span class="row-label">{{ $rowLabel }}</span>
                            
                            @foreach($seatMap[$rowLabel] as $s)
                                @php
                                    $isDisabled = in_array($s['status'], ['SOLD', 'BLOCKED', 'HELD']) ? 'disabled' : '';
                                    
                                    // Set class and icon based on type
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

                {{-- HÀNG TEST 10K --}}
                @if(!empty($seatMap['TEST']))
                <div class="test-seat-divider">
                    <span>🧪 GHẾ TEST THANH TOÁN</span>
                </div>
                <div class="seat-row test-seat-row">
                    <span class="row-label" style="color: #f59e0b;">TEST</span>
                    @foreach($seatMap['TEST'] as $s)
                        <button type="button"
                                class="seat test-seat-btn"
                                data-id="{{ $s['id'] }}"
                                data-seat="{{ $s['code'] }}"
                                data-type="{{ $s['type'] }}"
                                data-price="{{ $s['price'] }}">
                            🧪 {{ $s['label'] }}
                        </button>
                    @endforeach
                    <span class="row-label" style="color: #f59e0b;">TEST</span>
                </div>
                @endif
            </div>

            <div class="seat-legend" style="margin-top: 30px;">
                <div class="legend-item"><i class="fa-solid fa-couch" style="color: #64748b;"></i><span>Thường</span></div>
                <div class="legend-item"><i class="fa-solid fa-crown vip-seat-icon"></i><span>VIP (Hàng F)</span></div>
                <div class="legend-item"><i class="fa-solid fa-heart sweet-seat-icon"></i><span>Sweetbox (Hàng J)</span></div>
                <div class="legend-item"><i class="fa-solid fa-couch held-seat" style="color: #28a745;"></i><span>Đã chọn</span></div>
                <div class="legend-item"><i class="fa-solid fa-couch" style="color: #ff9800;"></i><span>Đang giữ</span></div>
                <div class="legend-item"><i class="fa-solid fa-couch sold-seat" style="color: #dc3545;"></i><span>Đã bán</span></div>
                <div class="legend-item"><span style="font-size: 16px;">🧪</span><span>Test 10K</span></div>
            </div>
        </div>
    </div>
</section>

<style>
    .HELD_BY_ME { background-color: #28a745 !important; color: white !important; border: none !important; }
    .HELD { background-color: #ff9800 !important; color: white !important; cursor: not-allowed; opacity: 0.7;}
    .SOLD { background-color: #dc3545 !important; color: white !important; cursor: not-allowed; opacity: 0.7;}
    .BLOCKED { background-color: #343a40 !important; color: white !important; cursor: not-allowed; opacity: 0.7;}

    .vip-seat-btn { color: white !important; border: 2px solid #eab308 !important; font-weight: bold !important; }
    .vip-seat-btn:hover { background-color: #eab308 !important; }
    .vip-seat-icon { color: #ffc107 !important; }
    
    .sweet-seat-btn {color: white !important; width: 90px !important; font-weight: bold !important; border-radius: 8px !important; margin: 3px 6px !important; transition: background-color 0.2s; }
    .sweet-seat-btn:hover { background-color: #db2777 !important; }
    .sweet-seat-icon { color: #ec4899 !important; }

    /* TEST SEAT */
    .test-seat-divider {
        margin: 20px 0 8px;
        text-align: center;
        position: relative;
    }
    .test-seat-divider::before {
        content: '';
        position: absolute;
        left: 0; right: 0; top: 50%;
        height: 1px;
        background: linear-gradient(90deg, transparent, #f59e0b44, transparent);
    }
    .test-seat-divider span {
        background: #0f172a;
        padding: 2px 16px;
        position: relative;
        color: #f59e0b;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 1px;
        text-transform: uppercase;
    }
    .test-seat-row {
        justify-content: center !important;
    }
    .test-seat-btn {
        background: linear-gradient(135deg, #92400e, #d97706) !important;
        color: #fff !important;
        border: 2px dashed #f59e0b !important;
        font-weight: bold !important;
        padding: 8px 20px !important;
        border-radius: 8px !important;
        font-size: 13px !important;
        min-width: 130px !important;
        transition: all 0.3s ease !important;
        animation: testPulse 2s ease-in-out infinite;
    }
    .test-seat-btn:hover {
        background: linear-gradient(135deg, #b45309, #f59e0b) !important;
        transform: scale(1.05);
        box-shadow: 0 0 20px rgba(245, 158, 11, 0.4);
    }
    .test-seat-btn.HELD_BY_ME {
        background: #16a34a !important;
        border-color: #22c55e !important;
        animation: none;
    }
    @keyframes testPulse {
        0%, 100% { box-shadow: 0 0 5px rgba(245, 158, 11, 0.3); }
        50% { box-shadow: 0 0 20px rgba(245, 158, 11, 0.6); }
    }
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

        // === GHẾ TEST: không cần AJAX hold ===
        if (seatType === 'test') {
            const isSelecting = !selectedSeats.has(seatCode);
            if (isSelecting) {
                // Bỏ tất cả ghế thường đã chọn khi chọn test
                selectedSeats.forEach((val, key) => {
                    if (val.type !== 'test') {
                        const oldBtn = document.querySelector(`.seat[data-seat="${key}"]`);
                        if (oldBtn) oldBtn.classList.remove('HELD_BY_ME');
                    }
                });
                selectedSeats.clear();
                selectedSeats.set(seatCode, { id: seatIdAttr, code: seatCode, type: seatType, price: seatPrice });
                btn.classList.add('HELD_BY_ME');
            } else {
                selectedSeats.delete(seatCode);
                btn.classList.remove('HELD_BY_ME');
            }
            updateUI();
            return;
        }

        // === Bỏ ghế test nếu đang chọn ghế thường ===
        if (selectedSeats.has('TEST-10K')) {
            selectedSeats.delete('TEST-10K');
            const testBtn = document.querySelector('.seat[data-seat="TEST-10K"]');
            if (testBtn) testBtn.classList.remove('HELD_BY_ME');
        }
        
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
            btnPayment.textContent = 'Tiếp Tục Thanh Toán';
        } else {
            const seatTags = [];
            let total = 0;
            selectedSeats.forEach((seat) => {
                total += seat.price;
                const typeLabel = seat.type === 'vip' ? '👑' : seat.type === 'sweetbox' ? '💕' : seat.type === 'test' ? '🧪' : '🎬';
                seatTags.push(`<span class="seat-tag seat-tag-${seat.type}">${typeLabel} ${seat.code}</span>`);
                
                const ids = seat.id.split(',');
                ids.forEach(id => {
                    hiddenSeatInputs.innerHTML += `<input type="hidden" name="seats[]" value="${id}">`;
                });
            });
            selectedSeatsEl.innerHTML = seatTags.join(' ');
            totalPriceEl.textContent = total.toLocaleString('vi-VN') + 'VNĐ';
            btnPayment.disabled = false;
            btnPayment.textContent = `Thanh Toán ${total.toLocaleString('vi-VN')}VNĐ →`;
        }
    }
});
</script>
@endsection