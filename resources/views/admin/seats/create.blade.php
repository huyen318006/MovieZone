<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thêm Ghế Mới</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
<div class="container" style="max-width: 600px;">
    <div class="card shadow">
        <div class="card-header bg-success text-white fw-bold">
            CREATE: Thêm Ghế Mới Vào Hệ Thống
        </div>
        <div class="card-body">
            <p class="text-muted small">Đang cấu hình cho: <strong>{{ $room->cinema->name }} - {{ $room->name }}</strong></p>
            
            @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <form action="{{ route('admin.seats.store') }}" method="POST">
                @csrf
                <input type="hidden" name="room_id" value="{{ $room->id }}">

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-bold">Hàng ghế (Ký tự chữ)</label>
                        <input type="text" name="row_label" class="form-control" placeholder="Ví dụ: A" value="{{ old('row_label') }}" required style="text-transform: uppercase;">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold">Số thứ tự ghế (Số)</label>
                        <input type="number" name="seat_number" class="form-control" placeholder="Ví dụ: 5" value="{{ old('seat_number') }}" min="1" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Giá vé cơ sở (VNĐ)</label>
                    <input type="number" name="price" class="form-control" value="80000" min="0" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Phân loại phân cấp ghế (BR05)</label>
                    <select name="seat_type" class="form-select" required>
                        <option value="STANDARD">STANDARD (Ghế thường)</option>
                        <option value="VIP">VIP (Ghế đặc quyền)</option>
                        <option value="COUPLE">COUPLE (Ghế đôi tình nhân)</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Trạng thái khởi tạo ban đầu</label>
                    <select name="status" class="form-select" required>
                        <option value="ACTIVE">ACTIVE (Khả dụng ngay)</option>
                        <option value="LOCKED">LOCKED (Khóa tạm thời)</option>
                        <option value="BROKEN">BROKEN (Hỏng hóc bảo trì)</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success fw-bold px-4">Lưu Dữ Liệu</button>
                    <a href="{{ route('admin.seats.index', ['cinema_id' => $room->cinema_id, 'room_id' => $room->id]) }}" class="btn btn-secondary px-4">Quay Lại Sơ Đồ</a>
                </div>
                
            </form>
        </div>
    </div>
</div>
</body>
</html>