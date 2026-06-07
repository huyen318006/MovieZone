<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký - MovieZone</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/login.css'])
</head>
<body>

<div class="overlay"></div>

<img
    src="{{ asset('assets/hero/avatar2.jpg') }}"
    alt=""
    class="bg-image"
>

<div class="container">

    <div class="left-content">

        <div class="logo">
            MOVIE<span>ZONE</span>
        </div>

        <h2>
            Tạo Tài Khoản<br>
            MovieZone
        </h2>

        <p class="tagline">
            Tham gia MovieZone để đặt vé nhanh hơn,
            lưu lịch sử giao dịch và cập nhật những
            bộ phim bom tấn mới nhất.
        </p>

    </div>

    <div class="register-card">

        <h2>Đăng Ký</h2>

 <form action="{{ route('register.post') }}" method="POST">
    @csrf



    <div class="input-group mb-3">
        <input
            type="text"
            name="name"
            value="{{ old('name') }}"
            placeholder="Họ và tên"
            class="form-control @error('name') border border-danger @enderror"
        >

        @error('name')
            <small class="text-danger d-block" style="color:red !important;">
                {{ $message }}
            </small>
        @enderror
    </div>

    <div class="input-group mb-3">
        <input
            type="email"
            name="email"
            value="{{ old('email') }}"
            placeholder="Email"
            class="form-control @error('email') border border-danger @enderror"
        >

       @error('email')
    <small class="text-danger d-block" style="color:red !important;">
        {{ $message }}
    </small>
@enderror
    </div>

    <div class="input-group mb-3">
        <input
            type="text"
            name="phone"
            value="{{ old('phone') }}"
            placeholder="Số điện thoại"
            class="form-control @error('phone') border border-danger @enderror"
        >

        @error('phone')
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
            class="form-control @error('password') border border-danger @enderror"
        >

        @error('password')
            <small class="text-danger d-block" style="color:red !important;">
                {{ $message }}
            </small>
        @enderror
    </div>

    <div class="input-group mb-3">
        <input
            type="password"
            name="password_confirmation"
            placeholder="Xác nhận mật khẩu"
            class="form-control"
        >
          @error('password.confirmed')
            <small class="text-danger d-block" style="color:red !important;">
                {{ $message }}
            </small>
        @enderror
    </div>

    <button
        type="submit"
        class="register-btn">
        Tạo Tài Khoản
    </button>

</form>

        <p class="login-link">
            Đã có tài khoản?
            <a href="{{ route('login') }}">
                Đăng nhập
            </a>
        </p>

    </div>

</div>

</body>
</html>
