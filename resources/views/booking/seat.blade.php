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

        <div class="total-price">
            0đ
        </div>

        <button class="btn-payment">
            Tiếp Tục Thanh Toán
        </button>

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

                <button class="seat available-seat-btn" data-seat="{{ $row.$i }}">
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

        <div class="vip-seat">VIP</div>
        <div class="vip-seat">VIP</div>
        <div class="vip-seat">VIP</div>
        <div class="vip-seat">VIP</div>

        <div class="aisle"></div>

        <div class="vip-seat">VIP</div>
        <div class="vip-seat">VIP</div>
        <div class="vip-seat">VIP</div>

    </div>

    <div class="sweetbox-row">

        <div class="sweet-seat">
            <i class="fa-solid fa-user"></i> 1
        </div>

        <div class="sweet-seat">
            <i class="fa-solid fa-user"></i> 2
        </div>

        <div class="sweet-seat">
            <i class="fa-solid fa-user"></i> 3
        </div>

        <div class="sweet-seat">
            <i class="fa-solid fa-user"></i> 4
        </div>

        <div class="aisle"></div>

        <div class="sweet-seat">
            <i class="fa-solid fa-user"></i> 5
        </div>

        <div class="sweet-seat">
            <i class="fa-solid fa-user"></i> 6
        </div>

        <div class="sweet-seat">
            <i class="fa-solid fa-user"></i> 7
        </div>

        <div class="sweet-seat">
            <i class="fa-solid fa-user"></i> 8
        </div>

    </div>

    <div class="seat-legend">

        <div class="legend-item">
            <i class="fa-solid fa-couch available-seat"></i>
            <span>AVAILABLE</span>
        </div>

        <div class="legend-item">
            <i class="fa-solid fa-couch held-seat"></i>
            <span>HELD</span>
        </div>

        <div class="legend-item">
            <i class="fa-solid fa-couch sold-seat"></i>
            <span>SOLD</span>
        </div>

        <div class="legend-item">
            <i class="fa-solid fa-lock blocked-seat"></i>
            <span>BLOCKED</span>
        </div>

        <div class="legend-item">
            <i class="fa-solid fa-crown vip-seat-icon"></i>
            <span>VIP</span>
        </div>

        <div class="legend-item">
            <i class="fa-solid fa-heart sweet-seat-icon"></i>
            <span>SWEETBOX</span>
        </div>

    </div>

</div>

</div>

</section>

@endsection
