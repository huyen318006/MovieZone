<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sơ Đồ Ghế</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .screen { background: #6c757d; color: white; text-align: center; padding: 6px; margin-bottom: 30px; border-radius: 0 0 15px 15px; font-weight: bold;}
        .seat-box { display: inline-block; text-align: center; margin: 4px; }
        .seat { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold; border-radius: 6px; color: #fff; text-decoration: none;}
        .seat-STANDARD { background-color: #0d6efd; }
        .seat-VIP { background-color: #ffc107; color: #000; }
        .seat-COUPLE { background-color: #dc3545; width: 100px; }
        .seat-LOCKED { background-color: #6c757d; position: relative; }
        .seat-LOCKED::after { content: "✖"; color: red; font-size: 14px; position: absolute; }
        .seat-BROKEN { background-color: #212529; }
        .action-links { font-size: 10px; display: block; margin-top: 2px; }
    </style>
</head>
<body class="bg-light py-4">
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h3 class="text-uppercase text-primary m-0">UC-ADM-04: Quản Lý Ghế</h3>
        @if($selectedRoom)
            <a href="{{ route('admin.seats.create', ['room_id' => $selectedRoom]) }}" class="btn btn-success fw-bold">+ Thêm Ghế Mới</a>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success">{!! session('success') !!}</div>
    @endif

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.seats.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Chọn Rạp Chiếu</label>
                    <select name="cinema_id" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Chọn Rạp --</option>
                        @foreach($cinemas as $cinema)
                            <option value="{{ $cinema->id }}" {{ $selectedCinema == $cinema->id ? 'selected' : '' }}>{{ $cinema->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Chọn Phòng Chiếu</label>
                    <select name="room_id" class="form-select" onchange="this.form.submit()" {{ empty($rooms) ? 'disabled' : '' }}>
                        <option value="">-- Chọn Phòng --</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}" {{ $selectedRoom == $room->id ? 'selected' : '' }}>{{ $room->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if($selectedRoom)
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <span class="fw-bold">Sơ đồ phân bố cấu hình phòng chiếu</span>
            <div>
                <span class="badge bg-primary">STANDARD</span>
                <span class="badge bg-warning text-dark">VIP</span>
                <span class="badge bg-danger">COUPLE</span>
                <span class="badge bg-secondary">LOCKED</span>
            </div>
        </div>
        <div class="card-body bg-white text-center">
            <div class="screen mx-auto w-50">MÀN HÌNH CHIẾU PHIM</div>
            <div class="d-flex flex-column align-items-center overflow-auto py-3">
                @if(count($seatsGrouped) > 0)
                    @foreach($seatsGrouped as $row => $seats)
                        <div class="d-flex align-items-start mb-3">
                            <strong class="me-3 mt-2 text-secondary text-uppercase" style="width: 20px;">{{ $row }}</strong>
                            @foreach($seats as $seat)
                                <div class="seat-box">
                                    <div class="seat seat-{{ $seat->status === 'LOCKED' ? 'LOCKED' : (($seat->status === 'BROKEN') ? 'BROKEN' : $seat->seat_type) }}" 
                                         title="Giá: {{ number_format($seat->price) }}đ">
                                        {{ $seat->seat_code }}
                                    </div>
                                    <div class="action-links d-flex justify-content-center gap-1 mt-1">
                                        <a href="{{ route('admin.seats.edit', $seat->id) }}" class="text-primary text-decoration-none">Sửa</a>
                                        <span class="text-muted">|</span>
                                        <form action="{{ route('admin.seats.toggle_lock', $seat->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" style="background:none;border:none;padding:0;font-size:10px;" class="text-warning text-decoration-none">{{ $seat->status === 'LOCKED' ? 'Mở' : 'Khóa' }}</button>
                                        </form>
                                        <span class="text-muted">|</span>
                                        <form action="{{ route('admin.seats.destroy', $seat->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Xóa mềm ghế?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" style="background:none;border:none;padding:0;font-size:10px;" class="text-danger text-decoration-none">Xóa</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                @else
                    <p class="text-muted my-4">Chưa có dữ liệu ghế.</p>
                @endif
            </div>
        </div>
    </div>
    @else
        <div class="alert alert-info text-center">Vui lòng chọn Rạp và Phòng để bắt đầu quản lý.</div>
    @endif
</div>
</body>
</html>