<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chỉnh Sửa Ghế</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/blueprint.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
<div class="container" style="max-width: 600px;">
    <div class="card shadow">
        <div class="card-header bg-primary text-white fw-bold">
            UPDATE: Thay Đổi Cấu Hình Ghế [{{ $seat->seat_code }}]
        </div>
        <div class="card-body">
            <p class="text-muted small">Thuộc phòng: <strong>{{ $seat->room->cinema->name }} - {{ $seat->room->name }}</strong></p>

            @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <form action="{{ route('admin.seats.update', $seat->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-bold">Hàng ghế</label>
                        <input type="text" name="row_label" class="form-control" value="{{ old('row_label', $seat->row_label) }}" required style="text-transform: uppercase;">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold">Số thứ tự ghế</label>
                        <input type="number" name="seat_number" class="form-control" value="{{ old('seat_number', $seat->seat_number) }}" min="1" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Giá vé áp dụng (VNĐ)</label>
                    <input type="number" name="price" class="form-control" value="{{ old('price', round($seat->price)) }}" min="0" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Loại ghế</label>
                    <select name="seat_type" class="form-select" required>
                        <option value="STANDARD" {{ $seat->seat_type == 'STANDARD' ? 'selected' : '' }}>STANDARD</option>
                        <option value="VIP" {{ $seat->seat_type == 'VIP' ? 'selected' : '' }}>VIP</option>
                        <option value="COUPLE" {{ $seat->seat_type == 'COUPLE' ? 'selected' : '' }}>COUPLE</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Trạng thái vận hành</label>
                    <select name="status" class="form-select" required>
                        <option value="ACTIVE" {{ $seat->status == 'ACTIVE' ? 'selected' : '' }}>ACTIVE</option>
                        <option value="LOCKED" {{ $seat->status == 'LOCKED' ? 'selected' : '' }}>LOCKED</option>
                        <option value="BROKEN" {{ $seat->status == 'BROKEN' ? 'selected' : '' }}>BROKEN</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary fw-bold px-4">Cập Nhật Ngay</button>
                    <a href="{{ route('admin.seats.index', ['cinema_id' => $seat->room->cinema_id, 'room_id' => $seat->room_id]) }}" class="btn btn-secondary px-4">Hủy Bỏ</a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>