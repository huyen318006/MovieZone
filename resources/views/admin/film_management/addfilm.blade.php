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
                        <input type="text" name="title" class="form-control"
                            value="{{ old('title') }}"
                            placeholder="Ví dụ: Doraemon Movie 2026">

                        @error('title')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Tên gốc</label>
                        <input type="text" name="original_title" class="form-control"
                            value="{{ old('original_title') }}"
                            placeholder="Original Title">

                        @error('original_title')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Mô tả phim</label>
                        <textarea name="description" class="form-control" rows="5"
                            placeholder="Nhập mô tả phim...">{{ old('description') }}</textarea>

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
                            value="{{ old('duration_minutes') }}"
                            placeholder="120">

                        @error('duration_minutes')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Ngày khởi chiếu</label>
                        <input type="date" name="release_date" class="form-control"
                            value="{{ old('release_date') }}">

                        @error('release_date')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Ngày kết thúc</label>
                        <input type="date" name="end_date" class="form-control"
                            value="{{ old('end_date') }}">

                        @error('end_date')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Trạng thái</label>
                        <select name="status" class="form-select">

                            <option value="COMING_SOON" {{ old('status') == 'COMING_SOON' ? 'selected' : '' }}>Chuẩn bị chiếu</option>
                            {{-- <option value="NOW_SHOWING" {{ old('status') == 'NOW_SHOWING' ? 'selected' : '' }}>Đang chiếu</option>
                            <option value="ENDED" {{ old('status') == 'ENDED' ? 'selected' : '' }}>Ngừng chiếu</option>
                            <option value="HIDDEN" {{ old('status') == 'HIDDEN' ? 'selected' : '' }}>Đã ẩn</option> --}}
                        </select>

                        @error('status')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

                <!-- Nội dung -->
                <h5 class="border-bottom pb-2 mt-5 mb-3">Nội dung phim</h5>

                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label">Quốc gia</label>
                        <input type="text" name="country" class="form-control"
                            value="{{ old('country') }}"
                            placeholder="Nhật Bản">

                        @error('country')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Ngôn ngữ</label>
                        <input type="text" name="language" class="form-control"
                            value="{{ old('language') }}"
                            placeholder="Tiếng Nhật">

                        @error('language')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Phụ đề / Lồng tiếng</label>
                        <input type="text" name="subtitle" class="form-control"
                            value="{{ old('subtitle') }}"
                            placeholder="Phụ đề Việt">

                        @error('subtitle')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Đạo diễn</label>
                        <input type="text" name="director" class="form-control"
                            value="{{ old('director') }}"
                            placeholder="Tên đạo diễn">

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
                        <textarea name="cast" class="form-control" rows="4"
                            placeholder="Liệt kê diễn viên...">{{ old('cast') }}</textarea>

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
                                <input class="form-check-input"
                                    type="checkbox"
                                    name="genres[]"
                                    value="{{ $genre->id }}"
                                    id="genre_{{ $genre->id }}"
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
                            value="{{ old('trailer_url') }}"
                            placeholder="https://youtube.com/...">

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
