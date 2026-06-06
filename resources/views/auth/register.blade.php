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

        <form>

            <div class="input-group">
                <input
                    type="text"
                    placeholder="Họ và tên"
                >
            </div>

            <div class="input-group">
                <input
                    type="email"
                    placeholder="Email"
                >
            </div>

            <div class="input-group">
                <input
                    type="text"
                    placeholder="Số điện thoại"
                >
            </div>

            <div class="input-group">
                <input
                    type="password"
                    placeholder="Mật khẩu"
                >
            </div>

            <div class="input-group">
                <input
                    type="password"
                    placeholder="Xác nhận mật khẩu"
                >
            </div>

            <button
                type="submit"
                class="register-btn"
            >
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