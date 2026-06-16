@extends('layout.admin')

@section('title', 'Cập nhật phim')

@section('content')

    <form method="POST" action="{{ route('update.film', $movie_id->id) }}" enctype="multipart/form-data">

        @csrf

        <div class="row g-3">

            {{-- HIỂN THỊ TẤT CẢ LỖI Ở ĐÂY NẾU CÓ --}}
            @if ($errors->any())
                <div class="col-12">
                    <div class="alert alert-danger">
                        <strong>Vui lòng kiểm tra lại thông tin:</strong>
                        <ul class="mb-0 mt-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- THÔNG TIN PHIM --}}
            <div class="col-lg-8">

                <div class="card shadow-sm">
                    <div class="card-header">
                        <strong>Cập nhật thông tin phim</strong>
                    </div>

                    <div class="card-body">

                        {{-- TITLE --}}
                        <div class="mb-3">
                            <label class="form-label">Tên phim</label>
                            <input type="text" name="title" value="{{ $movie_id->title }}"
                                class="form-control @error('title') is-invalid @enderror">
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- DESCRIPTION --}}
                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea name="description" rows="5"
                                class="form-control @error('description') is-invalid @enderror">{{ $movie_id->description }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">

                            {{--
                                RELEASE DATE
                                Quy tắc:
                                  COMING_SOON  → được sửa
                                  NOW_SHOWING  → bị khóa (phim đang chiếu)
                                  ENDED        → bị khóa
                                  HIDDEN       → được sửa (tái sử dụng phim), phải >= hôm nay + 3 ngày
                            --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Ngày khởi chiếu
                                    @if (in_array($movie_id->status, ['NOW_SHOWING', 'ENDED']))
                                        <span class="badge bg-secondary ms-1">
                                            <i class="bi bi-lock-fill"></i> Đã khóa
                                        </span>
                                    @elseif ($movie_id->status === 'HIDDEN')
                                        <span class="badge bg-warning text-dark ms-1">
                                            <i class="bi bi-arrow-repeat"></i> Tái sử dụng
                                        </span>
                                    @endif
                                </label>

                                <input type="date" name="release_date"
                                    value="{{ $movie_id->release_date }}"
                                    class="form-control @error('release_date') is-invalid @enderror"
                                    @if (in_array($movie_id->status, ['NOW_SHOWING', 'ENDED'])) readonly @endif
                                    @if ($movie_id->status === 'HIDDEN') min="{{ \Carbon\Carbon::today()->addDays(3)->format('Y-m-d') }}" @endif>

                                @if (in_array($movie_id->status, ['NOW_SHOWING', 'ENDED']))
                                    <div class="form-text text-warning">
                                        <i class="bi bi-info-circle"></i>
                                        Không thể sửa khi phim đang chiếu hoặc đã kết thúc.
                                    </div>
                                @elseif ($movie_id->status === 'HIDDEN')
                                    <div class="form-text text-info">
                                        <i class="bi bi-info-circle"></i>
                                        Phim ẩn có thể đặt lại ngày chiếu — phải cách ít nhất
                                        <strong>3 ngày</strong> kể từ hôm nay
                                        (sớm nhất: <strong>{{ \Carbon\Carbon::today()->addDays(3)->format('d/m/Y') }}</strong>).
                                    </div>
                                @endif

                                @error('release_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{--
                                END DATE
                                Quy tắc:
                                  COMING_SOON  → được sửa
                                  NOW_SHOWING  → được sửa
                                  ENDED        → bị khóa
                            --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Ngày kết thúc
                                    @if ($movie_id->status === 'ENDED')
                                        <span class="badge bg-secondary ms-1">
                                            <i class="bi bi-lock-fill"></i> Đã khóa
                                        </span>
                                    @endif
                                </label>

                                <input type="date" name="end_date"
                                    value="{{ $movie_id->end_date }}"
                                    class="form-control @error('end_date') is-invalid @enderror"
                                    @if ($movie_id->status === 'ENDED') readonly @endif>

                                @if ($movie_id->status === 'ENDED')
                                    <div class="form-text text-warning">
                                        <i class="bi bi-info-circle"></i>
                                        Không thể sửa khi phim đã kết thúc.
                                    </div>
                                @endif

                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>

                        <div class="row">

                            {{-- DURATION --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Thời lượng (phút)</label>
                                <input type="number" name="duration_minutes"
                                    value="{{ $movie_id->duration_minutes }}"
                                    class="form-control @error('duration_minutes') is-invalid @enderror">
                                @error('duration_minutes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{--
                                TRẠNG THÁI: chỉ hiển thị, không cho chỉnh.
                                Hệ thống tự động cập nhật dựa theo release_date và end_date.
                            --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Trạng thái hiện tại</label>

                                {{-- Badge hiển thị trạng thái --}}
                                @php
                                    $statusMap = [
                                        'COMING_SOON' => ['label' => 'Chuẩn bị chiếu', 'icon' => '🕐', 'badge' => 'bg-info text-dark'],
                                        'NOW_SHOWING' => ['label' => 'Đang chiếu',     'icon' => '🎬', 'badge' => 'bg-success'],
                                        'ENDED'       => ['label' => 'Ngừng chiếu',    'icon' => '✅', 'badge' => 'bg-secondary'],
                                        'HIDDEN'      => ['label' => 'Ẩn',             'icon' => '🚫', 'badge' => 'bg-danger'],
                                    ];
                                    $s = $statusMap[$movie_id->status] ?? ['label' => $movie_id->status, 'icon' => '', 'badge' => 'bg-secondary'];
                                @endphp
                                <div class="form-control d-flex align-items-center gap-2" style="background:#f8f9fa; cursor:default; height:auto; min-height:38px;">
                                    <span class="badge {{ $s['badge'] }}">{{ $s['icon'] }} {{ $s['label'] }}</span>
                                    <span class="small text-muted">Quản lý tự động</span>
                                </div>

                                {{-- Gửi status hiện tại lên server để FormRequest xử lý khóa ngày --}}
                                <input type="hidden" name="status" value="{{ $movie_id->status }}">

                                <div class="form-text text-muted mt-1">
                                    @if ($movie_id->status === 'COMING_SOON')
                                        <i class="bi bi-unlock"></i> Có thể sửa cả ngày khởi chiếu lẫn ngày kết thúc.
                                    @elseif ($movie_id->status === 'NOW_SHOWING')
                                        <i class="bi bi-lock"></i> Ngày khởi chiếu bị khóa. Chỉ sửa được ngày kết thúc.
                                    @elseif ($movie_id->status === 'ENDED')
                                        <i class="bi bi-lock-fill"></i> Cả hai ngày đều bị khóa.
                                    @elseif ($movie_id->status === 'HIDDEN')
                                        <i class="bi bi-arrow-repeat"></i> Có thể đặt lại ngày chiếu để tái sử dụng phim (phải cách ít nhất 3 ngày).
                                    @endif
                                </div>
                            </div>

                        </div>

                        {{-- LANGUAGE --}}
                        <div class="mb-3">
                            <label class="form-label">Ngôn ngữ</label>
                            <input type="text" name="language" value="{{ $movie_id->language }}"
                                class="form-control @error('language') is-invalid @enderror">
                            @error('language')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- SUBTITLE --}}
                        <div class="mb-3">
                            <label class="form-label">Phụ đề</label>
                            <input type="text" name="subtitle" value="{{ $movie_id->subtitle }}"
                                class="form-control @error('subtitle') is-invalid @enderror">
                            @error('subtitle')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- AGE RATING --}}
                        <div class="mb-3">
                            <label class="form-label">Độ tuổi</label>
                            <input type="text" name="age_rating" value="{{ $movie_id->age_rating }}"
                                class="form-control @error('age_rating') is-invalid @enderror"
                                placeholder="VD: P, K, T13, T16, T18">
                            @error('age_rating')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- COUNTRY (required — thiếu field này sẽ fail validation) --}}
                        <div class="mb-3">
                            <label class="form-label">Quốc gia <span class="text-danger">*</span></label>
                            <input type="text" name="country" value="{{ $movie_id->country }}"
                                class="form-control @error('country') is-invalid @enderror"
                                placeholder="VD: Mỹ, Hàn Quốc, Việt Nam...">
                            @error('country')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            {{-- DIRECTOR --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Đạo diễn</label>
                                <input type="text" name="director" value="{{ $movie_id->director }}"
                                    class="form-control @error('director') is-invalid @enderror"
                                    placeholder="Tên đạo diễn">
                                @error('director')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- TRAILER URL --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Trailer URL</label>
                                <input type="url" name="trailer_url" value="{{ $movie_id->trailer_url }}"
                                    class="form-control @error('trailer_url') is-invalid @enderror"
                                    placeholder="https://youtube.com/...">
                                @error('trailer_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- CAST --}}
                        <div class="mb-0">
                            <label class="form-label">Diễn viên</label>
                            <textarea name="cast" rows="2"
                                class="form-control @error('cast') is-invalid @enderror"
                                placeholder="VD: Diễn viên A, Diễn viên B...">{{ $movie_id->cast }}</textarea>
                            @error('cast')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>

            </div>

            {{-- CỘT PHẢI: POSTER + THỂ LOẠI --}}
            <div class="col-lg-4">

                {{-- POSTER --}}
                <div class="card shadow-sm">
                    <div class="card-header">
                        <strong>Poster phim</strong>
                    </div>
                    <div class="card-body">
                        <label class="form-label">Poster hiện tại</label>
                        <p class="small text-muted mb-2">Để trống nếu muốn giữ nguyên poster cũ.</p>

                        @if ($movie_id->poster_url)
                            <img src="{{ asset('storage/' . $movie_id->poster_url) }}"
                                class="img-fluid rounded border mb-3">
                        @else
                            <div class="border rounded text-center p-5 mb-3">
                                <i class="bi bi-image fs-1 text-secondary"></i>
                                <div class="small text-muted mt-2">Chưa có poster</div>
                            </div>
                        @endif

                        <label class="form-label">Chọn poster mới</label>
                        <input type="file" name="poster"
                            class="form-control @error('poster') is-invalid @enderror">
                        @error('poster')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- THỂ LOẠI --}}
                <div class="card shadow-sm mt-3">
                    <div class="card-header">
                        <strong>Phân loại phim</strong>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Thể loại hiện tại</label>
                            <input type="text" value="{{ $movie_id->genres_name }}"
                                class="form-control" readonly>
                        </div>

                        <p class="small text-muted">Thay đổi thể loại phim (nếu cần).</p>
                        <div class="row">
                            @foreach ($genres as $genre)
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                            name="genres[]"
                                            value="{{ $genre->id }}"
                                            id="genre_{{ $genre->id }}"
                                            {{ in_array($genre->id, old('genres', $currentGenreIds)) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="genre_{{ $genre->id }}">
                                            {{ $genre->name }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

            </div>

        </div>

        {{-- ACTIONS --}}
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('admin.film') }}" class="btn btn-outline-secondary">
                Quay lại
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i> Cập nhật phim
            </button>
        </div>

    </form>

@endsection
