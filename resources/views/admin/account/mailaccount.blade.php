<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Thông báo tài khoản</title>
</head>

<body style="font-family: Arial, sans-serif; background:#f5f5f5; padding:20px;">

    <div style="max-width:600px; margin:0 auto; background:#ffffff; padding:20px; border-radius:8px;">

        <h2 style="color:#e67e22;">
            🔔 Thông báo tạm khóa tài khoản
        </h2>

        <p>Xin chào <b>{{ $user->name }}</b>,</p>

        <p>
            Chúng tôi xin thông báo rằng tài khoản của bạn tại hệ thống
            <b>MovieZone</b> đã bị <b>tạm khóa</b>.
        </p>

        <hr>

        <h4>📌 Thông tin tài khoản</h4>

        <p><b>Email:</b> {{ $user->email }}</p>
        <p><b>Trạng thái:</b> <span style="color:red;">Tạm khóa (Lock)</span></p>

        <hr>

        <h4>⚠️ Lý do tạm khóa</h4>

        <p style="color:#555;">
            {{ $reason ?? 'Không có lý do được cung cấp' }}
        </p>

        <hr>

        <p>
            Nếu bạn cho rằng đây là nhầm lẫn, vui lòng liên hệ bộ phận hỗ trợ để được xử lý.
        </p>

        <p>
            Trân trọng,<br>
            <b>MovieZone Admin Team</b>
        </p>

    </div>

</body>

</html>
