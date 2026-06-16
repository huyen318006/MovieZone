@extends('layout.admin')

@section('title', 'Xác nhận ngừng chiếu phim')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-warning text-white py-3">
                <h5 class="card-title mb-0"><i class="bi bi-exclamation-triangle-fill"></i> Xác nhận ngừng chiếu phim</h5>
            </div>
            <div class="card-body p-4">
                <div class="mb-4">
                    <h4 class="text-primary">{{ $movie->title }}</h4>
                    <p class="text-muted mb-0">Tên gốc: {{ $movie->original_title ?? 'N/A' }}</p>
                </div>

                <div class="alert alert-danger mb-4">
                    <h5 class="alert-heading fw-bold"><i class="bi bi-shield-fill-exclamation"></i> Cảnh báo ảnh hưởng lớn:</h5>
                    <p class="mb-0 fs-5 mt-2">
                        Phim này có <strong>{{ $showtimeCount }}</strong> suất chiếu và <strong>{{ $bookingCount }}</strong> vé sẽ bị huỷ nếu tiếp tục.
                    </p>
                </div>

                <div class="card mb-4 bg-light">
                    <div class="card-body">
                        <h6 class="fw-bold text-secondary mb-3">Quy trình xử lý tự động khi ngừng chiếu:</h6>
                        <ul class="mb-0 text-muted ps-3">
                            <li class="mb-2">Trạng thái phim sẽ được chuyển sang <strong>Ngừng chiếu (ENDED)</strong> và lưu ngày kết thúc chiếu là ngày hôm nay.</li>
                            <li class="mb-2">Tất cả <strong>{{ $showtimeCount }}</strong> suất chiếu tương lai của phim này sẽ bị hủy (CANCELLED).</li>
                            <li class="mb-0">Tất cả <strong>{{ $bookingCount }}</strong> đơn đặt vé tương lai sẽ bị hủy, các hóa đơn đã thanh toán sẽ tự động chuyển sang trạng thái chờ hoàn tiền (REFUNDED).</li>
                        </ul>
                    </div>
                </div>

                <form action="{{ route('admin.film.toggle_status', $movie->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="toggle_action" value="stop">
                    
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('admin.film') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Quay lại
                        </a>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-check-circle-fill"></i> Xác nhận ngừng chiếu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
