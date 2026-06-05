@extends('layout.app')

@section('content')

<section class="movie-detail">
    <div class="movie-backdrop"></div>
    <div class="movie-detail-card" data-aos="fade-up">
        <div class="movie-content">
            <h1 class="movie-title">Avatar: Dòng Chảy Của Nước</h1>
            <div class="movie-tags">
                <span>Hành Động</span> <span>Phiêu Lưu</span> <span>Viễn Tưởng</span>
            </div>
            <div class="movie-stats">
                <span><i class="fa-solid fa-star"></i> 8.9 / 10</span>
                <span><i class="fa-regular fa-clock"></i> 192 phút</span>
                <span>T13</span>
            </div>
            <p class="movie-description">
                Jake Sully cùng gia đình mình tiếp tục sinh sống trên Pandora. 
                Khi một hiểm họa mới xuất hiện, họ phải chiến đấu để bảo vệ quê hương.
            </p>
            <div class="movie-gallery swiper movieSwiper">
            <div class="swiper-wrapper">
                <div class="swiper-slide"><img src="{{ asset('assets/gallery/1.jpg') }}" alt=""></div>
                <div class="swiper-slide"><img src="{{ asset('assets/gallery/2.jpeg') }}" alt=""></div>
                <div class="swiper-slide"><img src="{{ asset('assets/gallery/3.jpg') }}" alt=""></div>
                <div class="swiper-slide"><img src="{{ asset('assets/gallery/4.jpg') }}" alt=""></div>
                </div>
            </div>
            <div class="movie-actions">
                <button class="btn-book"><i class="fa-solid fa-ticket"></i> Đặt Vé Ngay</button>
                <button class="btn-trailer" data-bs-toggle="modal" data-bs-target="#trailerModal">
                    <i class="fa-solid fa-play"></i> Xem Trailer
                </button>
            </div>
        </div>

        <div class="movie-poster">
            <img src="{{ asset('assets/hero/avatar.jpg') }}" alt="Avatar">
            <button class="poster-play-btn" data-bs-toggle="modal" data-bs-target="#trailerModal">
                <i class="fa-solid fa-play"></i>
            </button>
        </div>
    </div>
</section>

<section class="booking-bar">

    <div class="booking-grid">

        <!-- DATE -->

        <div class="booking-column">

            <div class="booking-title">

                Ngày Chiếu

            </div>

            <div class="date-list">

                <button
                    onclick="selectDate(this)"
                    class="date-active">

                    <span>Hôm Nay</span>

                    <strong>11</strong>

                    <small>Th5</small>

                </button>

                <button onclick="selectDate(this)">

                    <span>Tháng 6</span>

                    <strong>12</strong>

                    <small>Th6</small>

                </button>

                <button onclick="selectDate(this)">

                    <span>Tháng 6</span>

                    <strong>13</strong>

                    <small>Th7</small>

                </button>

                <button onclick="selectDate(this)">

                    <span>Tháng 6</span>

                    <strong>14</strong>

                    <small>CN</small>

                </button>

            </div>

        </div>

        <!-- TIME -->

        <div
            class="booking-column disabled"
            id="timeBlock">

            <div class="booking-title">

                Suất Chiếu

            </div>

            <div class="option-list">

                <button onclick="selectTime(this)">
                    09:00
                </button>

                <button onclick="selectTime(this)">
                    13:30
                </button>

                <button onclick="selectTime(this)">
                    18:00
                </button>

                <button onclick="selectTime(this)">
                    20:00
                </button>

            </div>

        </div>

        <!-- TYPE -->

        <div
            class="booking-column disabled"
            id="typeBlock">

            <div class="booking-title">

                Định Dạng

            </div>

            <div class="option-list">

                <button onclick="selectType(this)">
                    2D
                </button>

                <button onclick="selectType(this)">
                    3D
                </button>

                <button onclick="selectType(this)">
                    IMAX
                </button>

            </div>

        </div>

        <!-- CINEMA -->

        <div
            class="booking-column disabled"
            id="cinemaBlock">

            <div class="booking-title">

                Rạp Chiếu

            </div>

            <div class="option-list">

                <button onclick="selectCinema(this)">
                    CGV Vincom
                </button>

                <button onclick="selectCinema(this)">
                    Lotte Cinema
                </button>

                <button onclick="selectCinema(this)">
                    Galaxy Cinema
                </button>

            </div>

        </div>

    </div>

</section>

<div
    class="continue-area"
    id="continueArea">

    <a
        href="{{ route('booking.seat') }}"
        class="continue-btn">

        Tiếp Tục Chọn Ghế

    </a>

</div>

<script>

function selectDate(btn){

    // Active ngày
    document.querySelectorAll('.date-list button').forEach(el=>el.classList.remove('date-active'));
    btn.classList.add('date-active');

    // Mở bước 2

    document.getElementById('timeBlock').classList.remove('disabled');

    // RESET GIỜ

    document.querySelectorAll('#timeBlock button').forEach(el=>el.classList.remove('active-option'));

    // RESET ĐỊNH DẠNG

    document.querySelectorAll('#typeBlock button').forEach(el=>el.classList.remove('active-option'));

    // RESET RẠP

    document.querySelectorAll('#cinemaBlock button').forEach(el=>el.classList.remove('active-option'));

    // KHÓA LẠI BƯỚC 3
    document.getElementById('typeBlock').classList.add('disabled');
    // KHÓA LẠI BƯỚC 4
    document.getElementById('cinemaBlock').classList.add('disabled');

    // ẨN NÚT TIẾP TỤC
    document.getElementById('continueArea').style.display='none';
}

function selectTime(btn){

    document
    .querySelectorAll('#timeBlock button')
    .forEach(el=>el.classList.remove('active-option'));

    btn.classList.add('active-option');

    document
    .getElementById('typeBlock')
    .classList.remove('disabled');
}

function selectType(btn){

    document
    .querySelectorAll('#typeBlock button')
    .forEach(el=>el.classList.remove('active-option'));

    btn.classList.add('active-option');

    document
    .getElementById('cinemaBlock')
    .classList.remove('disabled');
}

function selectCinema(btn){

    document
    .querySelectorAll('#cinemaBlock button')
    .forEach(el=>el.classList.remove('active-option'));

    btn.classList.add('active-option');

    document
    .getElementById('continueArea')
    .style.display='flex';
}

</script>

@endsection