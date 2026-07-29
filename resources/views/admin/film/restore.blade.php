@extends('layout.admin')

@section('title', 'Xác nhận khôi phục phim')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-success text-white py-3">
                <h5 class="card-title mb-0">
                    <i class="bi bi-arrow-counterclockwise"></i> Xác nhận khôi phục hiển thị phim
                </h5>
            </div>

            <div class="card-body p-4">
                <div class="mb-4">
                    <h4 class="text-success">{{ $movie->name ?? $movie->title }}</h4>
                    <p class="text-muted mb-0">Ngày khởi chiếu: {{ \Carbon\Carbon::parse($movie->release_date)->format('d/m/Y') }}</p>
                </div>

                <div class="alert alert-info mb-4">
                    <h5 class="alert-heading fw-bold"><i class="bi bi-info-circle-fill"></i> Cơ chế tính toán trạng thái:</h5>
                    <p class="mb-0 fs-5 mt-2">
                        Hệ thống tự động cập nhật ngày mở bán vé là 3 ngày sau kể từ thời điểm hiện tại (theo thời gian thực).
                    </p>
                </div>

                <div class="card mb-4 bg-light">
                    <div class="card-body">
                        <h6 class="fw-bold text-secondary mb-3">Quy trình xử lý tự động khi khôi phục:</h6>
                        <ul class="mb-0 text-muted ps-3">
                            @if (\Carbon\Carbon::parse($movie->release_date)->isFuture())
                                <li class="mb-2">
                                  Để tránh xung đột với các suất chiếu hiện tại ngày khởi chiếu chỉnh  ở **tương lai**, trạng thái phim sẽ chuyển thành
                                    <span class="badge bg-warning text-dark">COMING_SOON (Sắp chiếu)</span>.
                                </li>
                            @else

                            @endif
                            <li class="mb-2">Phim sẽ xuất hiện trở lại trên danh sách tìm kiếm và trang chủ của khách hàng.</li>
                            <li class="mb-0 text-warning fw-bold">
                                <i class="bi bi-exclamation-circle"></i> Lưu ý: Các suất chiếu và vé trước đó đã bị hủy (nếu có) sẽ KHÔNG tự động khôi phục để tránh xung đột lịch chiếu hiện tại Admin kiểm tra kĩ thông tin của film.
                            </li>
                        </ul>
                    </div>
                </div>

                <form action="{{ \App\Helpers\TabAuthHelper::route('confirm.recovery', ['id' => $movie->id]) }}" method="POST">
                    @csrf
                    @method('POST')
                    <input type="hidden" name="toggle_action" value="resume">

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ \App\Helpers\TabAuthHelper::route('admin.film') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Quay lại
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle-fill"></i> Xác nhận khôi phục
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
