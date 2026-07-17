@extends('layout.staff')

@section('content')

    {{-- COUNTDOWN TIMER 5 PHÚT --}}
    @include('booking._countdown_timer', ['secondsLeft' => $secondsLeft ?? 300])

    <section class="confirm-page">
        <div class="confirm-container">

            <div class="confirm-header">
                <h2>XÁC NHẬN ĐẶT VÉ</h2>
                <p>Kiểm tra lại thông tin trước khi thanh toán</p>
            </div>

            <!-- BẮT ĐẦU FORM BAO QUANH TOÀN BỘ THÔNG TIN -->
            <form action="{{ route('booking.checkout') }}" method="POST">
                @csrf

                <div class="confirm-content">

                    {{-- LEFT --}}
                    <div class="confirm-movie">

                        <img src="{{ asset($showtime->movie->poster_url) }}" alt="{{ $movie_name }}" class="movie-poster">

                        <div class="movie-info">

                            <h3>{{ $movie_name }}</h3>

                            <div class="info-item">
                                <i class="fa-solid fa-door-open"></i>
                                <span>Phòng: {{ $room->name }}</span>
                            </div>

                            <div class="info-item">
                                <i class="fa-solid fa-calendar-days"></i>
                                <span>Ngày chiếu:
                                    {{ \Carbon\Carbon::parse($showtime->start_time)->format('d/m/Y') }}
                                </span>
                            </div>

                            <div class="info-item">
                                <i class="fa-solid fa-clock"></i>
                                <span>{{ $start_time }} - {{ $end_time }}</span>
                            </div>

                        </div>

                    </div>
                    {{-- RIGHT --}}
                    <div class="confirm-ticket">

                        {{-- Ghế đã chọn --}}
                        <div class="ticket-section">
                            <h4>Ghế đã chọn</h4>
                            <div class="seat-list">
                                @if (empty($seats))
                                    <span class="text-muted">Chưa có ghế nào được chọn.</span>
                                @else
                                    @foreach ($seats as $seat)
                                        @php
                                            $seatCode = '';
                                            if (is_object($seat)) {
                                                $seatCode = $seat->seat_code ?? ($seat->seat->seat_code ?? '');
                                            } elseif (is_array($seat)) {
                                                $seatCode = $seat['seat_code'] ?? ($seat['code'] ?? '');
                                            } else {
                                                $seatCode = (string) $seat;
                                            }
                                        @endphp

                                        @if ($seatCode !== '')
                                            <span class="seat-badge">
                                                {{ $seatCode }}
                                            </span>
                                        @endif
                                    @endforeach
                                @endif
                            </div>
                        </div>

                        {{-- Thông tin khách hàng (Các ô input nằm trong Form) --}}
                        <div class="ticket-section">
                            <h4>Thông tin khách hàng</h4>
                            <div class="customer-grid">
                                <div>
                                    <label>Họ và tên</label>
                                    <input type="text" name="customer_name" class="form-control"
                                        placeholder="Nhập họ và tên" required>
                                </div>
                                <div style="margin-top: 10px;">
                                    <label>Số điện thoại</label>
                                    <input type="text" name="customer_phone" class="form-control"
                                        placeholder="Nhập số điện thoại" required>
                                </div>
                            </div>
                        </div>

                        {{-- Chi tiết giá vé --}}
                        <div class="ticket-section">
                            <h4>Chi tiết giá vé</h4>

                            @if (empty($seats))
                                <div class="text-muted">Chưa có ghế để hiển thị.</div>
                            @else
                                <div class="price-breakdown">
                                    @php $calculatedTotalTicket = 0; @endphp
                                    @foreach ($seats as $seat)
                                        @php
                                            $seatCode = '';
                                            $seatPrice = 0;

                                            if (is_object($seat)) {
                                                $seatCode = $seat->seat_code ?? ($seat->seat->seat_code ?? '');
                                                $seatPrice = $seat->price ?? 0;
                                            } elseif (is_array($seat)) {
                                                $seatCode = $seat['seat_code'] ?? ($seat['code'] ?? '');
                                                $seatPrice = $seat['price'] ?? 0;
                                            } else {
                                                $seatCode = (string) $seat;
                                                $seatPrice = 0;
                                            }
                                            $calculatedTotalTicket += (int) $seatPrice;
                                        @endphp

                                        <div class="price-row">
                                            <span>🎬 {{ $seatCode }}</span>
                                            <span>{{ number_format((int) $seatPrice, 0, ',', '.') }}đ</span>
                                        </div>
                                    @endforeach

                                    <div class="price-subtotal-row">
                                        <span>Tổng vé</span>
                                        <span>{{ number_format($totalTicketPrice ?? $calculatedTotalTicket, 0, ',', '.') }}đ</span>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Combo đã chọn --}}
                        @if (!empty($combos))
                            <div class="ticket-section">
                                <h4>Combo đã chọn</h4>
                                <div class="price-breakdown">
                                    @php $calculatedTotalCombo = 0; @endphp
                                    @foreach ($combos as $combo)
                                        @php $calculatedTotalCombo += ($combo['total_price'] ?? 0); @endphp
                                        <div class="price-row">
                                            <span>🍿 {{ $combo['name'] ?? 'Combo' }} x{{ $combo['quantity'] ?? 1 }}</span>
                                            <span>{{ number_format($combo['total_price'] ?? 0, 0, ',', '.') }}đ</span>
                                        </div>
                                    @endforeach
                                    <div class="price-subtotal-row">
                                        <span>Tổng combo</span>
                                        <span>{{ number_format($totalComboPrice ?? $calculatedTotalCombo, 0, ',', '.') }}đ</span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Sản phẩm lẻ đã chọn (Nếu controller lấy từ session nhưng chưa hiển thị) --}}
                        @if (!empty($products))
                            <div class="ticket-section">
                                <h4>Sản phẩm đã chọn</h4>
                                <div class="price-breakdown">
                                    @foreach ($products as $product)
                                        <div class="price-row">
                                            <span>🥤 {{ $product['name'] ?? 'Sản phẩm' }}
                                                x{{ $product['quantity'] ?? 1 }}</span>
                                            <span>{{ number_format($product['total_price'] ?? 0, 0, ',', '.') }}đ</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Tổng thanh toán --}}
                        <div class="ticket-section">
                            <h4>Tổng thanh toán</h4>
                            @php
                                $fallbackTotal =
                                    ($totalTicketPrice ?? ($calculatedTotalTicket ?? 0)) +
                                    ($totalComboPrice ?? ($calculatedTotalCombo ?? 0));
                                $totalPriceSafe = $totalPrice ?? $fallbackTotal;
                            @endphp
                            <div class="total-price">
                                {{ number_format($totalPriceSafe, 0, ',', '.') }} VNĐ
                            </div>
                        </div>

                    </div>

                </div>

                {{-- Phương thức thanh toán --}}
                <div class="payment-method-box">
                    <h4>
                        <i class="fa-solid fa-credit-card"></i>
                        Chọn phương thức thanh toán
                    </h4>

                    <div class="payment-options-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="ONLINE" checked>
                            <div class="option-content">
                                <span class="icon">💳</span>
                                <span class="text">Thanh toán Online</span>
                            </div>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="CASH">
                            <div class="option-content">
                                <span class="icon">💵</span>
                                <span class="text">Thanh toán tiền mặt</span>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Các nút hành động --}}
                <div class="confirm-actions">
                    <a href="{{ route('staff.sell-tickets') }}" class="btn-back">
                        <i class="fa-solid fa-arrow-left"></i>
                        Quay lại chọn ghế
                    </a>

                    <button type="submit" class="btn-confirm">
                        Xác nhận & Thanh toán
                        <i class="fa-solid fa-check"></i>
                    </button>
                </div>

            </form>
            <!-- KẾT THÚC FORM -->

        </div>
    </section>

    <style>
        /* CSS Giữ nguyên theo cấu trúc giao diện của bạn */
        .confirm-page {
            padding: 60px 20px;
            background: #090e17;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            color: #e2e8f0;
        }

        .confirm-container {
            max-width: 1100px;
            margin: 50px auto;
            background: #111827;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid #1f2937;
        }

        .confirm-header {
            background: linear-gradient(to right, #1f2937, #111827);
            border-bottom: 1px solid #374151;
            text-align: center;
            padding: 30px;
        }

        .confirm-header h2 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 1px;
        }

        .confirm-header p {
            margin-top: 8px;
            color: #9ca3af;
            font-size: 15px;
        }

        .confirm-content {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 30px;
            padding: 30px;
        }

        .confirm-movie {
            display: flex;
            gap: 25px;
        }

        .movie-poster {
            width: 200px;
            height: 300px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
            border: 1px solid #374151;
        }

        .movie-info {
            flex: 1;
        }

        .movie-info h3 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 26px;
            color: #f8fafc;
        }

        .info-item {
            background: #1f2937;
            color: #e2e8f0;
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid #374151;
        }

        .info-item i {
            color: #3b82f6;
            font-size: 18px;
        }

        .confirm-ticket {
            background: #1f2937;
            border-radius: 12px;
            padding: 25px;
            border: 1px solid #374151;
        }

        .ticket-section {
            margin-bottom: 25px;
        }

        .ticket-section h4 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #9ca3af;
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .customer-grid label {
            display: block;
            margin-bottom: 5px;
            color: #cbd5e1;
            font-size: 14px;
        }

        .customer-grid .form-control {
            width: 100%;
            padding: 10px;
            background: #111827;
            border: 1px solid #374151;
            border-radius: 6px;
            color: #fff;
        }

        .customer-grid .form-control:focus {
            border-color: #3b82f6;
            outline: none;
        }

        .seat-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .seat-badge {
            background: rgba(59, 130, 246, 0.1);
            color: #60a5fa;
            border: 1px solid #3b82f6;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            display: inline-flex;
            align-items: center;
        }

        .price-breakdown {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #cbd5e1;
            font-size: 14px;
            padding: 6px 0;
        }

        .price-subtotal-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #60a5fa;
            font-weight: 600;
            font-size: 15px;
            padding: 10px 0 0;
            margin-top: 8px;
            border-top: 1px solid #374151;
        }

        .total-price {
            color: #ef4444;
            font-size: 36px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .payment-method-box {
            margin: 0 30px 30px;
            padding: 25px;
            background: #1f2937;
            border: 1px solid #374151;
            border-radius: 12px;
        }

        .payment-method-box h4 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #f8fafc;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .payment-method-box h4 i {
            color: #3b82f6;
        }

        .payment-option {
            display: block;
            cursor: pointer;
        }

        .payment-option input[type="radio"] {
            display: none;
        }

        .option-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 16px;
            background: #111827;
            border: 2px solid #374151;
            border-radius: 10px;
            transition: all 0.3s ease;
            text-align: center
        }

        .payment-option input[type="radio"]:checked+.option-content {
            border-color: #3b82f6;
            background: rgba(59, 130, 246, 0.05);
        }

        .option-content .icon {
            font-size: 20px;
        }

        .option-content .text {
            color: #cbd5e1;
            font-weight: 500;
        }

        .payment-option input[type="radio"]:checked+.option-content .text {
            color: #ffffff;
            font-weight: 600;
        }

        .confirm-actions {
            display: flex;
            justify-content: space-between;
            padding: 0 30px 30px;
        }

        .btn-back {
            background: #374151;
            color: #f8fafc;
            padding: 14px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
        }

        .btn-back:hover {
            background: #4b5563;
        }

        .btn-confirm {
            background: #ef4444;
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
            box-shadow: 0 4px 14px 0 rgba(239, 68, 68, 0.39);
        }

        .btn-confirm:hover {
            background: #dc2626;
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.23);
        }

        @media(max-width: 768px) {
            .confirm-content {
                grid-template-columns: 1fr;
            }

            .confirm-movie {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .movie-poster {
                width: 100%;
                max-width: 250px;
            }

            .payment-options-grid {
                grid-template-columns: 1fr !important;
            }

            .confirm-actions {
                flex-direction: column-reverse;
                gap: 15px;
            }

            .btn-back,
            .btn-confirm {
                width: 100%;
                justify-content: center;
            }
        }
    </style>

@endsection
