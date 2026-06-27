<footer class="footer">
    <!-- Curved Wave Top -->
    <div class="footer-wave" data-aos="fade-up" data-aos-duration="1000">
        <svg viewBox="0 0 1440 150" preserveAspectRatio="none">
            <defs>
                <linearGradient id="wave-gradient" x1="0" x2="0" y1="0" y2="1">
                    <stop offset="0%" stop-color="#1e3a8a" stop-opacity="0.2" />
                    <stop offset="100%" stop-color="#081124" stop-opacity="1" />
                </linearGradient>
            </defs>
            <path fill="url(#wave-gradient)" 
                d="M0,70 Q720,0 1440,80 L1440,150 L0,150 Z" />
        </svg>
    </div>

    <div class="footer-container" data-aos="fade-up" data-aos-duration="1200" data-aos-easing="ease-out-cubic">
        <!-- Logo & Description -->
        <div class="footer-col"data-aos="fade-up"data-aos-delay="100">
            <h2 class="footer-logo">MOVIE<span>ZONE</span></h2>
            <p>Đặt vé xem phim trực tuyến nhanh chóng, cập nhật lịch chiếu, khuyến mãi và những bộ phim mới nhất.</p>
        </div>

        <!-- Site Map -->
        <div class="footer-col nav-col" data-aos="fade-up" data-aos-delay="250">
            <h3>ĐIỀU HƯỚNG</h3>
            <a href="{{ route('home') }}"><i class="fas fa-home"></i> Trang Chủ</a>
            <a href="{{ route('movies') }}"><i class="fas fa-film"></i> Phim</a>
            <a href="{{ route('showtimes') }}"><i class="fas fa-calendar-alt"></i> Lịch Chiếu</a>
            <a href="{{ route('news') }}"><i class="fas fa-newspaper"></i> Tin Tức</a>
        </div>

        <!-- Contact & Social -->
        <div class="footer-col nav-col" data-aos="fade-up" data-aos-delay="250">
            <h3>LIÊN HỆ</h3>
            <p><i class="fas fa-phone-alt"></i> 0123 456 789</p>
            <p><i class="fas fa-envelope"></i> moviezone@gmail.com</p>
            <p><i class="fas fa-map-marker-alt"></i> Hà Nội, Việt Nam</p>
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-telegram"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-tiktok"></i></a>
            </div>
        </div>
    </div>
</footer>