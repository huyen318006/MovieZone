@extends('layout.admin')

@section('title', 'Film Management')

@section('content')

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="container-fluid">
        <div class="card shadow-sm">
            <div class="card-header">
                <h4 class="mb-0"><i class="bi bi-film"></i> Thêm phim mới</h4>
            </div>

            <div class="card-body">
                <form action="{{ route('admin.store.film') }}" enctype="multipart/form-data" method="POST">
                    @csrf

                    <!-- Thông tin cơ bản -->
                    <h5 class="border-bottom pb-2 mb-3">Thông tin cơ bản</h5>

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Tên phim</label>
                            <input type="text" name="title" class="form-control" value="{{ old('title') }}"
                                placeholder="Ví dụ: Doraemon Movie 2026">

                            @error('title')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Tên gốc</label>
                            <input type="text" name="original_title" class="form-control"
                                value="{{ old('original_title') }}" placeholder="Original Title">

                            @error('original_title')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Mô tả phim</label>
                            <textarea name="description" class="form-control" rows="5" placeholder="Nhập mô tả phim...">{{ old('description') }}</textarea>

                            @error('description')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>

                    <!-- Phát hành -->
                    <h5 class="border-bottom pb-2 mt-5 mb-3">Thông tin phát hành</h5>

                    <div class="row g-3">

                        <div class="col-md-3">
                            <label class="form-label">Thời lượng (phút)</label>
                            <input type="number" name="duration_minutes" class="form-control"
                                value="{{ old('duration_minutes') }}" placeholder="VD: 120">

                            @error('duration_minutes')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Ngày khởi chiếu</label>
                            <input type="date" name="release_date" id="release_date" class="form-control"
                                value="{{ old('release_date') }}">

                            @error('release_date')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Ngày kết thúc</label>
                            <input type="date" name="end_date" id="end_date" class="form-control"
                                value="{{ old('end_date') }}">

                            @error('end_date')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Trạng thái</label>
                            <select name="status" class="form-select">

                                <option value="COMING_SOON" {{ old('status') == 'COMING_SOON' ? 'selected' : '' }}>Chuẩn bị
                                    chiếu</option>
                                {{-- <option value="NOW_SHOWING" {{ old('status') == 'NOW_SHOWING' ? 'selected' : '' }}>Đang chiếu</option>
                            <option value="ENDED" {{ old('status') == 'ENDED' ? 'selected' : '' }}>Ngừng chiếu</option>
                            <option value="HIDDEN" {{ old('status') == 'HIDDEN' ? 'selected' : '' }}>Đã ẩn</option> --}}
                            </select>

                            @error('status')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>

                    {{-- ── START: ĐOẠN THÊM (KIỂM TRA SLOT TRỐNG KHẢ DỤNG) ───────────────────────────────────────── --}}
                    <div class="row mt-4">
                        <div class="col-12 mb-2">
                            <div class="card bg-light p-3" style="border: 1px dashed #6c757d;">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div>
                                        <strong class="d-block text-dark">
                                            <i class="bi bi-shield-check text-primary"></i> Kiểm tra tài nguyên phòng chiếu
                                        </strong>
                                        <span class="small text-muted">
                                            Hãy đảm bảo kiểm tra tính toán các khoảng trống lịch chiếu tự động tránh trùng
                                            lịch trước khi bấm Lưu phim.
                                        </span>
                                    </div>

                                    <button type="button" id="btn-check-slots" class="btn btn-sm btn-outline-primary">
                                        <span class="spinner-border spinner-border-sm d-none" id="check-spinner"
                                            role="status"></span>
                                        <i class="bi bi-calendar2-check me-1" id="check-icon"></i> Kiểm tra slot trống khả
                                        dụng
                                    </button>
                                </div>

                                <div id="check-slots-result" class="mt-2 d-none"></div>
                            </div>
                        </div>
                    </div>
                    {{-- ── END: ĐOẠN THÊM ─────────────────────────────────────────────────────────────────────────── --}}

                    <!-- Nội dung -->
                    <h5 class="border-bottom pb-2 mt-5 mb-3">Nội dung phim</h5>

                    <div class="row g-3">

                        <div class="col-md-4">
                            <label class="form-label">Quốc gia</label>
                            <input type="text" name="country" class="form-control" value="{{ old('country') }}"
                                placeholder="Nhập quốc gia của phim VD: Nhật Bản">

                            @error('country')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Ngôn ngữ</label>
                            <input type="text" name="language" class="form-control" value="{{ old('language') }}"
                                placeholder="VD: Tiếng Nhật">

                            @error('language')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Phụ đề / Lồng tiếng</label>
                            <input type="text" name="subtitle" class="form-control" value="{{ old('subtitle') }}"
                                placeholder="VD: Phụ đề Việt">

                            @error('subtitle')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Đạo diễn</label>
                            <input type="text" name="director" class="form-control" value="{{ old('director') }}"
                                placeholder="Điền Tên đạo diễn">

                            @error('director')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Độ tuổi</label>
                            <select name="age_rating" class="form-select">
                                <option value="P" {{ old('age_rating') == 'P' ? 'selected' : '' }}>P</option>
                                <option value="K" {{ old('age_rating') == 'K' ? 'selected' : '' }}>K</option>
                                <option value="T13" {{ old('age_rating') == 'T13' ? 'selected' : '' }}>T13</option>
                                <option value="T16" {{ old('age_rating') == 'T16' ? 'selected' : '' }}>T16</option>
                                <option value="T18" {{ old('age_rating') == 'T18' ? 'selected' : '' }}>T18</option>
                            </select>

                            @error('age_rating')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Diễn viên</label>
                            <textarea name="cast" class="form-control" rows="4" placeholder="Hãy liệt kê diễn viên...">{{ old('cast') }}</textarea>

                            @error('cast')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>

                    <!-- Thể loại -->
                    <h5 class="border-bottom pb-2 mt-5 mb-3">Thể loại</h5>

                    <div class="row">
                        @foreach ($genres as $genre)
                            <div class="col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="genres[]"
                                        value="{{ $genre->id }}" id="genre_{{ $genre->id }}"
                                        {{ in_array($genre->id, old('genres', [])) ? 'checked' : '' }}>

                                    <label class="form-check-label" for="genre_{{ $genre->id }}">
                                        {{ $genre->name }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @error('genres')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror

                    <h5 class="border-bottom pb-2 mt-5 mb-3">
                        Định dạng / Kiểu phòng hỗ trợ
                    </h5>

                    <div class="row">
                        @php
                            $roomChoices = App\Models\Room::all();
                        @endphp
                        @foreach ($roomChoices as $roomChoice)
                            <div class="col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="room_types[]"
                                        value="{{ $roomChoice->id }}" id="room_type_{{ $roomChoice->id }}"
                                        {{ in_array($roomChoice->id, old('room_types', [])) ? 'checked' : '' }}>

                                    <label class="form-check-label" for="room_type_{{ $roomChoice->id }}">
                                        {{ $roomChoice->name }} ({{ $roomChoice->room_type }})
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @error('room_types')
                        <div class="text-danger small mt-1">
                            {{ $message }}
                        </div>
                    @enderror

                    <!-- MEDIA -->
                    <h5 class="border-bottom pb-2 mt-5 mb-3">Hình ảnh & Trailer</h5>

                    <div class="row g-3">

                        <div class="col-md-4">
                            <label class="form-label">Poster</label>
                            <input type="file" name="poster" class="form-control">

                            @error('poster')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Banner</label>
                            <input type="file" name="banner" class="form-control">

                            @error('banner')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Trailer URL</label>
                            <input type="url" name="trailer_url" class="form-control"
                                value="{{ old('trailer_url') }}" placeholder="https://youtube.com/...">

                            @error('trailer_url')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>


                    <!-- BUTTON -->
                    <div class="mt-4 text-end">
                        <a href="{{ route('admin.film') }}" class="btn btn-secondary">Hủy</a>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Lưu phim
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

@endsection

{{-- ── START: SCRIPT KIỂM TRA SLOT TRỐNG KHẢ DỤNG (AJAX) ───────────────────────────────────────── --}}
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('btn-check-slots');
            if (!btn) return;

            btn.addEventListener('click', async () => {
                const releaseDate = document.getElementById('release_date')?.value;
                const endDate = document.getElementById('end_date')?.value;
                const duration = document.querySelector('input[name="duration_minutes"]')?.value;

                if (!releaseDate || !endDate || !duration) {
                    alert(
                        'Vui lòng điền hoàn chỉnh: Ngày khởi chiếu, Ngày kết thúc và Thời lượng phim để hệ thống tính toán!');
                    return;
                }

                const spinner = document.getElementById('check-spinner');
                const icon = document.getElementById('check-icon');
                const resultDiv = document.getElementById('check-slots-result');

                if (spinner) spinner.classList.remove('d-none');
                if (icon) icon.classList.add('d-none');
                btn.disabled = true;

                try {
                    const res = await fetch("{{ route('admin.movies.check-slots') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        body: JSON.stringify({
                            release_date: releaseDate,
                            end_date: endDate,
                            duration_minutes: duration
                        })
                    });

                    const data = await res.json().catch(() => null);

                    resultDiv.classList.remove('d-none', 'alert', 'alert-success', 'alert-danger',
                        'alert-warning');

                    if (!data || typeof data.total_slots === 'undefined') {
                        resultDiv.classList.add('alert', 'alert-danger', 'mb-0');
                        resultDiv.textContent =
                            'Server trả về dữ liệu không đúng định dạng (thiếu total_slots).';
                        return;
                    }

                    if (data.total_slots > 0) {
                        // Giống hệt trang update (để đảm bảo màu xanh hiển thị đúng)
                        resultDiv.classList.add('alert', 'alert-success', 'bg-success-subtle',
                            'text-success-emphasis', 'border', 'border-success', 'mb-0');
                        resultDiv.innerHTML =
                            `<i class="bi bi-check-circle-fill me-1"></i> <strong>Khả dụng:</strong> Phát hiện thấy khoảng <strong>${data.total_slots} suất chiếu trống</strong> thích hợp trên hệ thống phòng chiếu. Bạn có thể lưu phim!`;
                    } else {
                        resultDiv.classList.add('alert', 'alert-warning', 'bg-warning-subtle',
                            'text-warning-emphasis', 'border', 'border-warning', 'mb-0');
                        resultDiv.innerHTML =
                            `<i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Cảnh báo:</strong> Kín lịch! Không tìm thấy khoảng thời gian trống nào đủ đáp ứng thời lượng phim này tại tất cả các phòng chiếu trong khoảng ngày đã chọn.`;
                    }
                } catch (e) {
                    console.error('check-slots fetch error:', e);
                    alert('Có lỗi hệ thống xảy ra khi kiểm tra slot.');
                } finally {
                    if (spinner) spinner.classList.add('d-none');
                    if (icon) icon.classList.remove('d-none');
                    btn.disabled = false;
                }
            });
        });
    </script>
@endpush
{{-- ── END: SCRIPT ─────────────────────────────────────────────────────────────────────────────── --}}
