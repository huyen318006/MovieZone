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

            <h2>Đăng Nhập</h2>
<form action="{{ route('login') }}" method="POST">
    @csrf

    <div class="input-group mb-3">
        <input
            type="email"
            name="email"
            value="{{ old('email') }}"
            placeholder="Email"
            class="form-control @error('email') is-invalid @enderror"
        >

        @error('email')
            <small class="text-danger d-block" style="color:red !important;">
                {{ $message }}
            </small>
        @enderror
    </div>

    <div class="input-group mb-3">
        <input
            type="password"
            name="password"
            placeholder="Mật khẩu"
            class="form-control @error('password') is-invalid @enderror"
        >

        @error('password')
            <small class="text-danger d-block" style="color:red !important;">
                {{ $message }}
            </small>
        @enderror
    </div>

    <div class="options">
        <label>
            <input type="checkbox" name="remember">
            Ghi nhớ đăng nhập
        </label>

        <a href="{{ route('password.request') }}">Quên mật khẩu?</a>
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
