<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập - MovieZone</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
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

            <h2>Quên Mật Khẩu</h2>

            <p class="tagline" style="margin-bottom:20px;">
                Nhập email đã đăng ký để nhận liên kết đặt lại mật khẩu.
            </p>

            <form action="{{ route('password.email') }}" method="POST">
                @csrf

                @if (session('success'))
                    <div style="color:#4ade80;margin-bottom:15px;">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="input-group mb-3">
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="Nhập email của bạn"
                        class="form-control @error('email') is-invalid @enderror">

                    @error('email')
                        <small class="d-block" style="color:red !important;margin-top:8px;">
                            {{ $message }}
                        </small>
                    @enderror
                </div>

                <button type="submit" class="login-btn">
                    Gửi Liên Kết Khôi Phục
                </button>
            </form>

            <p class="register-link">
                Đã nhớ mật khẩu?
                <a href="{{ route('login') }}">
                    Đăng nhập ngay
                </a>
            </p>

        </div>

    </div>

</body>

</html>
