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
                    <p><i class="fa-solid fa-clock"></i> Khung giờ:
                        {{ \Carbon\Carbon::parse($showtime->start_time)->format('H:i - d/m/Y') }}</p>
                </div>

                <div class="selected-seat-box">
                    <div id="timer-box"
                        style="display: none; background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-weight: bold; text-align: center;">
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
                            <label for="customerEmail"
                                style="display: block; color: #94a3b8; font-size: 13px; margin-bottom: 6px;">
                                <i class="fa-solid fa-envelope"></i> Email nhận hoá đơn <span
                                    style="color: #ef4444;">*</span>
                            </label>
                            <input type="email" name="customer_email" id="customerEmail" required
                                placeholder="Nhập email của bạn..."
                                value="{{ auth()->check() ? auth()->user()->email : '' }}"
                                style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #334155; background: #0f172a; color: #e2e8f0; font-size: 14px; outline: none; box-sizing: border-box; transition: border-color 0.2s;"
                                onfocus="this.style.borderColor='#8b5cf6'" onblur="this.style.borderColor='#334155'">
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

                @if (session('error'))
                    <div class="alert alert-danger"
                        style="color: white; background: #dc3545; padding: 10px; text-align: center; margin-bottom: 20px; border-radius: 5px;">
                        {{ session('error') }}
                    </div>
                @endif
                <div id="ajax-error-box"
                    style="display: none; color: white; background: #dc3545; padding: 10px; text-align: center; margin-bottom: 20px; border-radius: 5px;">
                </div>

                <div class="seat-map">

                    @foreach (range('G', 'A') as $row)

                        <div class="seat-row">

                            <span class="row-label">{{ $row }}</span>

                            @for ($i = 1; $i <= 16; $i++)
                                <button type="button" class="seat available-seat-btn" data-seat="{{ $row . $i }}"
                                    data-type="standard" data-price="80000">
                                    {{ $i }}
                                </button>

                                @if ($s['is_aisle'])
                                    <div class="aisle"></div>
                                @endif
                            @endforeach

                            <span class="row-label">{{ $rowLabel }}</span>
                        </div>
                    @endif
                    @endforeach
                </div>

                <div class="seat-legend" style="margin-top: 30px;">
                    <div class="legend-item"><i class="fa-solid fa-couch" style="color: #64748b;"></i><span>Thường</span>
                    </div>
                    <div class="legend-item"><i class="fa-solid fa-crown vip-seat-icon"></i><span>VIP (Hàng F)</span></div>
                    <div class="legend-item"><i class="fa-solid fa-heart sweet-seat-icon"></i><span>Sweetbox (Hàng J)</span>
                    </div>
                    <div class="legend-item"><i class="fa-solid fa-couch held-seat" style="color: #28a745;"></i><span>Đã
                            chọn</span></div>
                    <div class="legend-item"><i class="fa-solid fa-couch" style="color: #ff9800;"></i><span>Đang giữ</span>
                    </div>
                    <div class="legend-item"><i class="fa-solid fa-couch sold-seat" style="color: #dc3545;"></i><span>Đã
                            bán</span></div>
                </div>
            </div>
        </div>
    </section>

    <style>
        .HELD_BY_ME {
            background-color: #28a745 !important;
            color: white !important;
            border: none !important;
        }

        .HELD {
            background-color: #ff9800 !important;
            color: white !important;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .SOLD {
            background-color: #dc3545 !important;
            color: white !important;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .BLOCKED {
            background-color: #343a40 !important;
            color: white !important;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .vip-seat-btn {
            color: white !important;
            border: 2px solid #eab308 !important;
            font-weight: bold !important;
        }

        .vip-seat-btn:hover {
            background-color: #eab308 !important;
        }

        .vip-seat-icon {
            color: #ffc107 !important;
        }

        .sweet-seat-btn {
            color: white !important;
            width: 90px !important;
            font-weight: bold !important;
            border-radius: 8px !important;
            margin: 3px 6px !important;
            transition: background-color 0.2s;
        }

        .sweet-seat-btn:hover {
            background-color: #db2777 !important;
        }

        .sweet-seat-icon {
            color: #ec4899 !important;
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
                    if (secondsLeft <= 0) {
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
                                'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]').content
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
                            selectedSeats.set(seatCode, {
                                id: seatIdAttr,
                                code: seatCode,
                                type: seatType,
                                price: seatPrice
                            });
                            btn.classList.add('HELD_BY_ME');
                        } else {
                            selectedSeats.delete(seatCode); // A2: Bỏ chọn ghế
                            btn.classList.remove('HELD_BY_ME');
                        }
                        updateUI();
                    } else {
                        document.getElementById('ajax-error-box').innerText = lastMessage ||
                            'Có lỗi khi giữ ghế.';
                        document.getElementById('ajax-error-box').style.display = 'block';
                        setTimeout(() => location.reload(), 1500);
                    }
                } catch (error) {
                    document.getElementById('ajax-error-box').innerText =
                        'Lỗi kết nối hệ thống.'; // E5: Lỗi kết nối
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
                        const typeLabel = seat.type === 'vip' ? '👑' : seat.type === 'sweetbox' ? '💕' :
                            '🎬';
                        seatTags.push(
                            `<span class="seat-tag seat-tag-${seat.type}">${typeLabel} ${seat.code}</span>`
                            );

                        const ids = seat.id.split(',');
                        ids.forEach(id => {
                            hiddenSeatInputs.innerHTML +=
                                `<input type="hidden" name="seats[]" value="${id}">`;
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
