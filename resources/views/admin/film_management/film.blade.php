@extends('layout.admin')

@section('title', 'Film Management')

@section('content')
@if(session('success'))
   <div id="success-alert"
     class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle-fill me-2"></i>
        {{ session('success') }}

        <button type="button"
            class="btn-close"
            data-bs-dismiss="alert"
            aria-label="Close">
        </button>
    </div>
@endif

        {{-- Header --}}
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2" style="margin-bottom: 10px;">
                <div>
                    <h3 class="mb-1">Quản lý phim</h3>
                    {{-- <div class="text-muted">Giao diện tạm thời (demo UI): thêm / cập nhật / phân loại / ẩn-ngừng chiếu</div> --}}
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ \App\Helpers\TabAuthHelper::route('admin.film.add') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i>
                        Thêm phim
                    </a>
                </div>
            </div>
        </div>



        {{-- Table --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-transparent d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="fw-semibold">Danh sách phim</div>
                    <div class="d-flex gap-2">

                        <form method="GET" action="{{ \App\Helpers\TabAuthHelper::route('admin.film') }}">
                            <input type="hidden" name="tab_token" value="{{ \App\Helpers\TabAuthHelper::gettoken() }}">
                            <div class="d-flex gap-2 align-items-center">

                                <h5 class="mb-0"></h5>

                                {{-- Genre --}}
                                <select name="genre" class="form-select form-select-sm" style="width: 180px;"
                                    onchange="this.form.submit()">
                                    <option value="">Tất cả thể loại</option>

                                    @foreach ($allGenres as $genre)
                                        <option value="{{ $genre->id }}"
                                            {{ request('genre') == $genre->id ? 'selected' : '' }}>
                                            {{ $genre->name }}
                                        </option>
                                    @endforeach
                                </select>

                                {{-- Status --}}
                                <select name="status" class="form-select form-select-sm" style="width: 180px;"
                                    onchange="this.form.submit()">
                                    <option value="">Tất cả trạng thái</option>

                                    <option value="NOW_SHOWING" {{ request('status') == 'NOW_SHOWING' ? 'selected' : '' }}>
                                        Đang chiếu
                                    </option>

                                    <option value="COMING_SOON" {{ request('status') == 'COMING_SOON' ? 'selected' : '' }}>
                                        Sắp chiếu
                                    </option>

                                    <option value="ENDED" {{ request('status') == 'ENDED' ? 'selected' : '' }}>
                                        Ngừng chiếu
                                    </option>
                                </select>

                                {{-- Reset --}}
                                <a href="{{ \App\Helpers\TabAuthHelper::route('admin.film') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-arrow-clockwise"></i> Làm mới
                                </a>

                            </div>
                        </form>

                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" style="min-width: 980px;">
                            <thead>
                                <tr>
                                    <th style="width: 70px;">#</th>
                                    <th>Poster</th>
                                    <th>Tên phim</th>
                                    <th>Khởi chiếu</th>
                                    <th>Ngày kết thúc</th>
                                    <th>Thời lượng</th>
                                    <th>Trạng thái</th>
                                    <th>Thể loại / Độ tuổi</th>
                                    <th>Ngôn ngữ / Phụ đề</th>

                                    <th style="width: 360px;">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>


                                @foreach ($movieGenres as $i => $m)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>

                                        <td>
                                            @if ($m->poster_url)
                                                <img src="{{ asset('storage/' . $m->poster_url) }}" alt="poster"
                                                    style="
                    width: 48px;
                    height: 68px;
                    object-fit: cover;
                    border-radius: 6px;
                    background: #eee;
                ">
                                            @else
                                                <div class="d-flex align-items-center justify-content-center border rounded bg-light"
                                                    style="
                    width: 48px;
                    height: 68px;
                ">
                                                    <i class="bi bi-image text-secondary"></i>
                                                </div>
                                            @endif
                                        </td>

                                        <td>
                                            <div class="fw-semibold">
                                                {{ $m->title }}
                                            </div>
                                        </td>

                                        {{-- ngày chiếu --}}
                                        <td class="text-muted">
                                            {{ $m->release_date }}
                                        </td>
                                        {{-- ngày kết thúc --}}
                                        <td class="text-muted">
                                            @if (empty($m->end_date))
                                                <span class="text-muted fst-italic">Chưa có</span>
                                            @else
                                                {{ $m->end_date }}
                                            @endif
                                        </td>
                                        {{--  --}}

                                        <td class="text-muted">
                                            {{ $m->duration_minutes }} phút
                                        </td>

                                        <td>
                                            @php
                                                $badgeClass = 'text-bg-secondary';
                                                $statusText = $m->status;

                                                if ($m->status === 'NOW_SHOWING') {
                                                    $badgeClass = 'text-bg-success';
                                                    $statusText = 'Đang chiếu';
                                                }

                                                if ($m->status === 'COMING_SOON') {
                                                    $badgeClass = 'text-bg-primary';
                                                    $statusText = 'Chuẩn bị chiếu';
                                                }

                                                if ($m->status === 'ENDED') {
                                                    $badgeClass = 'text-bg-danger';
                                                    $statusText = 'Ngừng chiếu';
                                                }

                                                if ($m->status === 'HIDDEN') {
                                                    $badgeClass = 'text-bg-dark';
                                                    $statusText = 'Đã ẩn';
                                                }
                                            @endphp

                                            <span class="badge {{ $badgeClass }}">
                                                {{ $statusText }}
                                            </span>
                                        </td>

                                        {{-- Thể loại + độ tuổi --}}
                                        <td class="text-muted">
                                            {{ $m->genres_name ?? 'Chưa phân loại' }}
                                            •
                                            {{ $m->age_rating }}
                                        </td>

                                        {{-- Ngôn ngữ + phụ đề --}}
                                        <td class="text-muted">
                                            {{ $m->language }}
                                            •
                                            {{ $m->subtitle }}
                                        </td>


                                        <td>
                                            <div class="d-flex gap-2 flex-wrap">


                                                <a href="{{ \App\Helpers\TabAuthHelper::route('admin.view.update.film', ['id' => $m->id]) }}"
                                                    class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-pencil"></i>Sửa
                                                </a>



                                                @if ($m->status === 'ENDED')
                                                    <a href="{{ \App\Helpers\TabAuthHelper::route('restore.film', ['id' => $m->id]) }}"
                                                        class="btn btn-outline-success btn-sm">
                                                        <i class="bi bi-arrow-counterclockwise"></i> Khôi phục
                                                    </a>
                                                @else
                                                    <a href="{{ \App\Helpers\TabAuthHelper::route('admin.film.confirm_stop', ['id' => $m->id]) }}"
                                                        class="btn btn-outline-warning btn-sm">
                                                        <i class="bi bi-stop-circle"></i> Ngừng chiếu
                                                    </a>
                                                @endif

                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex align-items-center justify-content-between mt-3">
                        <div class="text-muted">
                            Tổng số phim: {{ $movieGenres->total() }}
                        </div>

                        {{ $movieGenres->links() }}
                    </div>
                </div>
            </div>
        </div>

    </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const alertEl = document.getElementById('success-alert');

    if (alertEl) {
        // Cách an toàn nhất: dùng class fade + setTimeout
        setTimeout(() => {
            alertEl.classList.remove('show');

            // Xóa hẳn element sau khi fade out
            setTimeout(() => {
                alertEl.remove();
            }, 150); // thời gian fade out của Bootstrap (~150ms)
        }, 3000);
    }
});
</script>
@endsection
