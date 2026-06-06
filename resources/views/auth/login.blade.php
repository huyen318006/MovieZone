<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập - MovieZone</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/login.css'])
</head>
<body>
<div class="overlay"></div>
<img src="{{ asset('assets/hero/avatar2.jpg') }}" alt="" class="bg-image">
<div class="container">

    <div class="left-content">
        <div class="logo">
            MOVIE<span>ZONE</span>
        </div>

        <h2>
            Đắm Chìm<br>
            Thế Giới Điện Ảnh
        </h2>

        <p class="tagline">
            Đặt vé nhanh chóng, chọn chỗ ngồi yêu thích và tận hưởng những bộ phim bom tấn mới nhất.
        </p>
    </div>

    <div class="login-card">

        <h2>Đăng Nhập</h2>

        <form>

            <div class="input-group">
                <input type="email" placeholder="Email">
            </div>

            <div class="input-group">
                <input type="password" placeholder="Mật khẩu">
            </div>

            <div class="options">
                <label><input type="checkbox"> Ghi nhớ đăng nhập</label>
                <a href="#">Quên mật khẩu?</a>
            </div>

            <button type="submit" class="login-btn">
                Đăng Nhập
            </button>

        </form>

        <div class="divider">
            <span>Hoặc</span>
        </div>


        <a href="{{ route('auth.google') }}" class="google-btn">
            {{-- <img src="{{ asset('assets/icons/google.svg') }}" alt="Google Icon"> --}}
            Đăng nhập với Google
        </a>


        <p class="register-link">
            Chưa có tài khoản?
            <a href="{{ route('register') }}">Đăng ký ngay</a>
        </p>

    </div>

</div>

</body>
</html>
