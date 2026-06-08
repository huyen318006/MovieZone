@extends('layout.app')

@section('content')

{{-- HERO SECTION --}}

<section class="hero-slider">

    <div class="hero-slide active">
        <img src="{{ asset('assets/hero/avatar2.jpg') }}">
        <div class="overlay"></div>

        <div class="hero-content">
            <span class="badge">MOVIEZONE PREMIUM</span>
            <h1>AVATAR:<br>THE WAY OF WATER</h1>
            <p>
                Đặt vé xem phim nhanh chóng,
                lựa chọn ghế ngồi trực tuyến.
            </p>

            <div class="hero-buttons">
                <a href="#" class="hero-btn-primary">Đặt Vé Ngay</a>
                <a href="#" class="hero-btn-secondary">Xem Trailer</a>
            </div>
        </div>
    </div>

    <div class="hero-slide">
    <img src="{{ asset('assets/hero/dune2.jpg') }}">
    <div class="overlay"></div>

    <div class="hero-content">
        <span class="badge">NOW SHOWING</span>
        <h1>DUNE<br>PART TWO</h1>

        <p>
            Hành trình của Paul Atreides tiếp tục trên hành tinh Arrakis,
            nơi cuộc chiến giành quyền lực và vận mệnh của thiên hà bắt đầu.
        </p>

        <div class="hero-buttons">
            <a href="#" class="hero-btn-primary">Đặt Vé Ngay</a>
            <a href="#" class="hero-btn-secondary">Xem Trailer</a>
        </div>
    </div>
    </div>

    <div class="hero-slide">
        <img src="{{ asset('assets/hero/oppenheimer.jpeg') }}">
        <div class="overlay"></div>

        <div class="hero-content">
            <span class="badge">BLOCKBUSTER</span>
            <h1>OPPENHEIMER</h1>

            <p>
                Câu chuyện về cha đẻ của bom nguyên tử với những quyết định
                thay đổi lịch sử nhân loại và thế giới hiện đại.
            </p>

            <div class="hero-buttons">
                <a href="#" class="hero-btn-primary">Đặt Vé Ngay</a>
                <a href="#" class="hero-btn-secondary">Xem Trailer</a>
            </div>
        </div>
    </div>

</section>

{{-- PHIM ĐANG CHIẾU --}}

<section class="home-layout">

    <div class="movie-content">

        <div class="section-title">

            <h2>Phim Đang Chiếu</h2>

            <a href="#">
                Xem tất cả
            </a>

        </div>

        <div class="movie-row">

    <div class="movie-card">

        <img
            src="{{ asset('assets/movies/dune.jpg') }}"
            alt="Dune Messiah"
        >

        <div class="info">

            <h4>Dune Messiah</h4>

            <span>
                Khởi chiếu: 25/08/2025
            </span>

        </div>

        <a
            href="{{ route('movies') }}"
            class="book-btn">

            Chi Tiết

        </a>

    </div>

    <div class="movie-card">

        <img
            src="{{ asset('assets/movies/johnwick.jpg') }}"
            alt="John Wick 5"
        >

        <div class="info">

            <h4>John Wick 5</h4>

            <span>
                Khởi chiếu: 01/09/2025
            </span>

        </div>

        <a
            href="{{ route('movies') }}"
            class="book-btn">

            Chi Tiết

        </a>

    </div>

    <div class="movie-card">

        <img
            src="{{ asset('assets/movies/oppenheimer.jpg') }}"
            alt="New Nolan Movie"
        >

        <div class="info">

            <h4>New Nolan Movie</h4>

            <span>
                Khởi chiếu: 12/09/2025
            </span>

        </div>

        <a
            href="{{ route('movies') }}"
            class="book-btn">

            Chi Tiết

        </a>

    </div>

    <div class="movie-card">

        <img
            src="{{ asset('assets/hero/avatar.jpg') }}"
            alt="Avatar 3"
        >

        <div class="info">

            <h4>Avatar 3</h4>

            <span>
                Khởi chiếu: 20/09/2025
            </span>

        </div>

        <a
            href="{{ route('movies') }}"
            class="book-btn">

            Chi Tiết

        </a>

    </div>

</div>

    </div>

    <aside class="promo-sidebar">

        <h3>Khuyến Mãi</h3>
        <div class="promo-card">
            <img
                src="{{ asset('assets/promo/1.jpg') }}"
                alt="">
            <div class="promo-info">
                Giảm 50% vé thứ 2
            </div>
        </div>

        <div class="promo-card">

            <img
                src="{{ asset('assets/promo/2.jpg') }}"
                alt="">

            <div class="promo-info">

                Combo bắp nước chỉ từ 49K

            </div>

        </div>

    </aside>

</section>

{{-- PHIM SẮP CHIẾU --}}

<section class="home-layout">

    <div class="movie-content">

        <div class="section-title">

            <h2>Phim Sắp Chiếu</h2>

            <a href="#">
                Xem tất cả
            </a>

        </div>

        <div class="movie-row">

    <div class="movie-card">

        <img
            src="{{ asset('assets/movies/dune.jpg') }}"
            alt="Dune Messiah"
        >

        <div class="info">

            <h4>Dune Messiah</h4>

            <span>
                Khởi chiếu: 25/08/2025
            </span>

        </div>

        <a
            href="{{ route('movies') }}"
            class="book-btn">

            Chi Tiết

        </a>

    </div>

    <div class="movie-card">

        <img
            src="{{ asset('assets/movies/johnwick.jpg') }}"
            alt="John Wick 5"
        >

        <div class="info">

            <h4>John Wick 5</h4>

            <span>
                Khởi chiếu: 01/09/2025
            </span>

        </div>

        <a
            href="{{ route('movies') }}"
            class="book-btn">

            Chi Tiết

        </a>

    </div>

    <div class="movie-card">

        <img
            src="{{ asset('assets/movies/oppenheimer.jpg') }}"
            alt="New Nolan Movie"
        >

        <div class="info">

            <h4>New Nolan Movie</h4>

            <span>
                Khởi chiếu: 12/09/2025
            </span>

        </div>

        <a
            href="{{ route('movies') }}"
            class="book-btn">

            Chi Tiết

        </a>

    </div>

    <div class="movie-card">

        <img
            src="{{ asset('assets/hero/avatar.jpg') }}"
            alt="Avatar 3"
        >

        <div class="info">

            <h4>Avatar 3</h4>

            <span>
                Khởi chiếu: 20/09/2025
            </span>

        </div>

        <a
            href="{{ route('movies') }}"
            class="book-btn">

            Chi Tiết

        </a>

    </div>

</div>

    </div>

    <aside class="promo-sidebar small">

        <div class="promo-card">

            <img
                src="{{ asset('assets/promo/3.jpg') }}"
                alt="">

            <div class="promo-info">

                Thành viên MovieZone giảm 20%

            </div>

        </div>
        <div class="promo-card">

            <img
                src="{{ asset('assets/promo/4.png') }}"
                alt="">

            <div class="promo-info">

                Combo bắp nước chỉ từ 49K

            </div>

        </div>

    </aside>

</section>
{{-- NEWS SECTION --}}
<section class="news-section">

    <div class="section-title">
        <h2>Tin Tức Điện Ảnh</h2>
        <a href="#">Xem tất cả</a>
    </div>

    <div class="news-grid">

        <article class="news-feature">

            <div class="news-slide active">
                <img src="{{ asset('assets/news/batman.jpg') }}" alt="">
                <div class="news-overlay">
                    <span class="news-tag">Tin nổi bật</span>
                    <h3>Avatar 3 chính thức tung trailer đầu tiên</h3>
                    <p>Bộ phim bom tấn tiếp theo của James Cameron sẽ ra mắt cuối năm nay.</p>
                </div>
            </div>

            <div class="news-slide">
                <img src="{{ asset('assets/news/mechanic.jpg') }}" alt="">
                <div class="news-overlay">
                    <span class="news-tag">Điện ảnh</span>
                    <h3>Dune Part Three xác nhận khởi quay</h3>
                    <p>Phần tiếp theo của thương hiệu Dune đang được sản xuất.</p>
                </div>
            </div>

            <div class="news-slide">
                <img src="{{ asset('assets/news/deadpool.jpg') }}" alt="">
                <div class="news-overlay">
                    <span class="news-tag">Bom tấn</span>
                    <h3>Deadpool & Wolverine lập kỷ lục doanh thu</h3>
                    <p>Bộ đôi Marvel tiếp tục thống trị phòng vé toàn cầu.</p>
                </div>
            </div>

            <div class="news-dots">
                <span class="active"></span>
                <span></span>
                <span></span>
            </div>

        </article>

        <div class="news-side">

            <article class="news-small">
                <img src="{{ asset('assets/news/conan.jpg') }}" alt="">
                <div class="news-content">
                    <span>03/06/2026</span>
                    <h4>Dune Part Three xác nhận khởi quay</h4>
                </div>
            </article>

            <article class="news-small">
                <img src="{{ asset('assets/news/deadpool.jpg') }}" alt="">
                <div class="news-content">
                    <span>02/06/2026</span>
                    <h4>John Wick trở lại với phần phim mới</h4>
                </div>
            </article>

        </div>

    </div>

</section>
@endsection
