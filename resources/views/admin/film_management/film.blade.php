@extends('layout.admin')

@section('title', 'Film Management')

@section('content')
    <div class="row g-3">

        {{-- Header --}}
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h3 class="mb-1">Quản lý phim</h3>
                    {{-- <div class="text-muted">Giao diện tạm thời (demo UI): thêm / cập nhật / phân loại / ẩn-ngừng chiếu</div> --}}
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.film.add') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i>
                        Thêm phim
                    </a>
                </div>
            </div>
        </div>

        {{-- Tabs (demo) --}}
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <div class="badge text-bg-primary">Danh sách phim</div>

                    </div>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-transparent d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="fw-semibold">Danh sách phim</div>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" style="width: 180px;">
                            <option selected>Trạng thái (demo)</option>
                            <option>Đang chiếu</option>
                            <option>Sắp chiếu</option>
                            <option>Ngừng chiếu</option>
                        </select>
                        <button class="btn btn-outline-secondary btn-sm" type="button">
                            <i class="bi bi-arrow-clockwise"></i> Làm mới
                        </button>
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

                                        <td class="text-muted">
                                            {{ $m->release_date }}
                                        </td>

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

                                                <button type="button" class="btn btn-outline-primary btn-sm"
                                                    data-bs-toggle="modal" data-bs-target="#movieUpdateModal">
                                                    <i class="bi bi-pencil"></i>
                                                    Sửa
                                                </button>

                                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                                    data-bs-toggle="modal" data-bs-target="#movieClassifyModal">
                                                    <i class="bi bi-tags"></i>
                                                    Phân loại
                                                </button>

                                                <button type="button" class="btn btn-outline-warning btn-sm"
                                                    data-bs-toggle="modal" data-bs-target="#movieToggleModal">
                                                    <i class="bi bi-eye-slash"></i>
                                                    Ẩn/Ngừng
                                                </button>

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

    {{-- Modal: Thêm phim --}}
    <div class="modal fade" id="movieCreateModal" tabindex="-1" aria-labelledby="movieCreateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="movieCreateModalLabel"><i class="bi bi-plus-circle"></i> Thêm phim</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="#" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12 col-lg-8">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Tên phim</label>
                                            <input class="form-control" name="title" placeholder="Ví dụ: Interstellar" />
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Mô tả</label>
                                            <textarea class="form-control" name="description" rows="3" placeholder="Mô tả ngắn..."></textarea>
                                        </div>

                                        <div class="row g-3">
                                            <div class="col-12 col-md-6 mb-3">
                                                <label class="form-label">Ngày khởi chiếu</label>
                                                <input class="form-control" type="date" name="release_date" />
                                            </div>
                                            <div class="col-12 col-md-6 mb-3">
                                                <label class="form-label">Thời lượng</label>
                                                <input class="form-control" name="duration"
                                                    placeholder="Ví dụ: 120 phút" />
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Trailer (link)</label>
                                            <input class="form-control" name="trailer" placeholder="https://..." />
                                        </div>

                                        <div class="row g-3">
                                            <div class="col-12 col-md-6 mb-3">
                                                <label class="form-label">Poster</label>
                                                <input class="form-control" type="file" name="poster"
                                                    accept="image/*" />
                                            </div>
                                            <div class="col-12 col-md-6 mb-3">
                                                <label class="form-label">Trạng thái</label>
                                                <select class="form-select" name="status">
                                                    <option>Đang chiếu</option>
                                                    <option>Sắp chiếu</option>
                                                    <option>Ngừng chiếu</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="text-muted small">
                                            (Demo UI) Backend sẽ xử lý upload poster/trailer.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-lg-4">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <div class="fw-semibold mb-2">Xem trước</div>
                                        <div class="mb-3">
                                            <div class="text-muted small mb-2">Poster hiện tại (demo)</div>
                                            <img src="{{ asset('assets/movies/avatar.jpg') }}" alt="preview"
                                                style="width: 100%; height: 240px; object-fit: cover; border-radius: 8px; background:#eee;">
                                        </div>
                                        <div class="mb-2 text-muted small">Các field phân loại sẽ ở tab/section “Phân loại
                                            phim”.</div>
                                    </div>
                                </div>

                                <div class="card shadow-sm mt-3">
                                    <div class="card-body">
                                        <div class="fw-semibold mb-2">Gợi ý dữ liệu diễn viên/đạo diễn</div>
                                        <div class="mb-2">
                                            <label class="form-label">Diễn viên (demo)</label>
                                            <input class="form-control" name="actors"
                                                placeholder="Cách nhau bởi dấu phẩy" />
                                        </div>
                                        <div class="mb-0">
                                            <label class="form-label">Đạo diễn (demo)</label>
                                            <input class="form-control" name="director" placeholder="Tên đạo diễn" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Lưu (demo)</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    {{-- Modal: Cập nhật phim --}}
    <div class="modal fade" id="movieUpdateModal" tabindex="-1" aria-labelledby="movieUpdateModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="movieUpdateModalLabel"><i class="bi bi-pencil-square"></i> Cập nhật phim
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="#" method="POST">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12 col-lg-8">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Tên phim</label>
                                            <input class="form-control" name="title" value=""
                                                placeholder="Tên phim" />
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Mô tả</label>
                                            <textarea class="form-control" name="description" rows="3" placeholder="Sửa mô tả..."></textarea>
                                        </div>

                                        <div class="row g-3">
                                            <div class="col-12 col-md-6 mb-3">
                                                <label class="form-label">Ngày khởi chiếu</label>
                                                <input class="form-control" type="date" name="release_date" />
                                            </div>
                                            <div class="col-12 col-md-6 mb-3">
                                                <label class="form-label">Thời lượng</label>
                                                <input class="form-control" name="duration"
                                                    placeholder="Ví dụ: 120 phút" />
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Trailer (link)</label>
                                            <input class="form-control" name="trailer" placeholder="https://..." />
                                        </div>

                                        <div class="row g-3">
                                            <div class="col-12 col-md-6 mb-3">
                                                <label class="form-label">Diễn viên</label>
                                                <input class="form-control" name="actors"
                                                    placeholder="Cách nhau bởi dấu phẩy" />
                                            </div>
                                            <div class="col-12 col-md-6 mb-3">
                                                <label class="form-label">Đạo diễn</label>
                                                <input class="form-control" name="director" placeholder="Tên đạo diễn" />
                                            </div>
                                        </div>

                                        <div class="mb-0">
                                            <label class="form-label">Trạng thái</label>
                                            <select class="form-select" name="status">
                                                <option>Đang chiếu</option>
                                                <option>Sắp chiếu</option>
                                                <option>Ngừng chiếu</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-lg-4">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <div class="fw-semibold mb-2">Poster</div>
                                        <div class="mb-3">
                                            <input class="form-control" type="file" name="poster"
                                                accept="image/*" />
                                        </div>
                                        <div class="text-muted small">(Demo UI) Không tải ảnh thật từ row.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Cập nhật
                            (demo)</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal: Phân loại phim --}}
    <div class="modal fade" id="movieClassifyModal" tabindex="-1" aria-labelledby="movieClassifyModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="movieClassifyModalLabel"><i class="bi bi-tags"></i> Phân loại phim</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="#" method="POST">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-12 mb-3">
                                                <label class="form-label">Thể loại</label>
                                                <input class="form-control" name="genre"
                                                    placeholder="Ví dụ: Khoa học viễn tưởng" />
                                            </div>

                                            <div class="col-12 col-md-6 mb-3">
                                                <label class="form-label">Độ tuổi</label>
                                                <select class="form-select" name="age">
                                                    <option>0+</option>
                                                    <option>6+</option>
                                                    <option>13+</option>
                                                    <option>16+</option>
                                                    <option>18+</option>
                                                </select>
                                            </div>

                                            <div class="col-12 col-md-6 mb-3">
                                                <label class="form-label">Ngôn ngữ</label>
                                                <input class="form-control" name="language"
                                                    placeholder="Ví dụ: Tiếng Anh" />
                                            </div>

                                            <div class="col-12 mb-0">
                                                <label class="form-label">Phụ đề</label>
                                                <select class="form-select" name="subtitle">
                                                    <option>Có</option>
                                                    <option>Không</option>
                                                </select>
                                            </div>

                                        </div>

                                        <div class="text-muted small mt-3">
                                            (Demo UI) Nếu phim đã có booking, hệ thống chỉ ẩn/ngừng chiếu thay vì xóa.
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Lưu phân loại
                            (demo)</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal: Ẩn / Ngừng chiếu --}}
    <div class="modal fade" id="movieToggleModal" tabindex="-1" aria-labelledby="movieToggleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="movieToggleModalLabel"><i class="bi bi-eye-slash"></i> Ẩn / Ngừng chiếu
                        (demo)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="#" method="POST">
                    <div class="modal-body">
                        <div class="alert alert-warning mb-3">
                            <div class="fw-semibold">Nguyên tắc:</div>
                            <div class="small">Không xóa cứng phim đã có booking. Chỉ thay đổi trạng thái ẩn/ngừng chiếu.
                            </div>
                        </div>

                        <label class="form-label">Chọn hành động</label>
                        <select class="form-select" name="toggle_action">
                            <option value="hide">Ẩn (không hiển thị)</option>
                            <option value="stop">Ngừng chiếu</option>
                            <option value="resume">Khôi phục hiển thị/đang chiếu</option>
                        </select>

                        <div class="text-muted small mt-3">
                            (Demo UI) Backend sẽ kiểm tra booking trước khi quyết định.
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-warning"><i class="bi bi-check2-circle"></i> Thực hiện
                            (demo)</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
