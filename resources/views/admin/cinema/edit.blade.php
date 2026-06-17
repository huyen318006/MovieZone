@extends('layout.admin')

@section('title', 'Sửa rạp chiếu')

@section('content')

{{-- Header --}}
<div class="col-12">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h3 class="mb-1">Sửa rạp chiếu</h3>
            <p class="text-muted mb-0">Cập nhật thông tin chi nhánh rạp chiếu phim</p>
        </div>
        <div>
            <a href="{{ route('admin.cinemas.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Quay lại danh sách
            </a>
        </div>
    </div>
</div>

{{-- Meta info --}}
<div class="col-12 mt-3">
    <div class="row g-3">
        <div class="col-auto">
            <div class="card card-body py-2 px-3">
                <small class="text-muted">Ngày tạo</small>
                <span class="fw-semibold">{{ $cinema->created_at->format('d/m/Y H:i') }}</span>
            </div>
        </div>
        <div class="col-auto">
            <div class="card card-body py-2 px-3">
                <small class="text-muted">Cập nhật lần cuối</small>
                <span class="fw-semibold">{{ $cinema->updated_at->format('d/m/Y H:i') }}</span>
            </div>
        </div>
        <div class="col-auto">
            <div class="card card-body py-2 px-3">
                <small class="text-muted">Số phòng chiếu</small>
                <span class="fw-semibold">
                    <i class="bi bi-door-open me-1"></i>{{ $cinema->rooms_count }} phòng
                </span>
            </div>
        </div>
    </div>
</div>

{{-- Form --}}
<div class="col-12 mt-3">
    <div class="card">
        <div class="card-header bg-transparent">
            <div class="fw-semibold">
                <i class="bi bi-building me-2"></i>Thông tin rạp chiếu
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.cinemas.update', $cinema->id) }}">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    {{-- Tên rạp --}}
                    <div class="col-12 col-md-6">
                        <label for="name" class="form-label">
                            Tên rạp <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               id="name"
                               name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $cinema->name) }}"
                               placeholder="VD: MovieZone Hà Nội"
                               required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Thành phố --}}
                    <div class="col-12 col-md-3">
                        <label for="city" class="form-label">
                            Thành phố <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               id="city"
                               name="city"
                               class="form-control @error('city') is-invalid @enderror"
                               value="{{ old('city', $cinema->city) }}"
                               placeholder="VD: Hà Nội"
                               required>
                        @error('city')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Quận / Huyện --}}
                    <div class="col-12 col-md-3">
                        <label for="district" class="form-label">
                            Quận / Huyện
                        </label>
                        <input type="text"
                               id="district"
                               name="district"
                               class="form-control @error('district') is-invalid @enderror"
                               value="{{ old('district', $cinema->district) }}"
                               placeholder="VD: Hoàn Kiếm">
                        @error('district')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Địa chỉ --}}
                    <div class="col-12">
                        <label for="address" class="form-label">
                            Địa chỉ đầy đủ <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               id="address"
                               name="address"
                               class="form-control @error('address') is-invalid @enderror"
                               value="{{ old('address', $cinema->address) }}"
                               placeholder="VD: 123 Tràng Tiền, Hoàn Kiếm, Hà Nội"
                               required>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Hotline --}}
                    <div class="col-12 col-md-6">
                        <label for="hotline" class="form-label">
                            Hotline
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                            <input type="text"
                                   id="hotline"
                                   name="hotline"
                                   class="form-control @error('hotline') is-invalid @enderror"
                                   value="{{ old('hotline', $cinema->hotline) }}"
                                   placeholder="VD: 19001001">
                            @error('hotline')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Google Map URL --}}
                    <div class="col-12 col-md-6">
                        <label for="map_url" class="form-label">
                            Google Map URL
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                            <input type="url"
                                   id="map_url"
                                   name="map_url"
                                   class="form-control @error('map_url') is-invalid @enderror"
                                   value="{{ old('map_url', $cinema->map_url) }}"
                                   placeholder="https://maps.google.com/...">
                            @error('map_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Trạng thái --}}
                    <div class="col-12 col-md-4">
                        <label for="status" class="form-label">
                            Trạng thái <span class="text-danger">*</span>
                        </label>
                        <select id="status"
                                name="status"
                                class="form-select @error('status') is-invalid @enderror"
                                required>
                            <option value="ACTIVE" {{ old('status', $cinema->status) == 'ACTIVE' ? 'selected' : '' }}>
                                Hoạt động
                            </option>
                            <option value="INACTIVE" {{ old('status', $cinema->status) == 'INACTIVE' ? 'selected' : '' }}>
                                Tạm ngưng
                            </option>
                            <option value="MAINTENANCE" {{ old('status', $cinema->status) == 'MAINTENANCE' ? 'selected' : '' }}>
                                Bảo trì
                            </option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Submit --}}
                <div class="d-flex gap-2 mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Cập nhật rạp
                    </button>
                    <a href="{{ route('admin.cinemas.index') }}" class="btn btn-outline-secondary">
                        Huỷ bỏ
                    </a>
                </div>

            </form>
        </div>
    </div>
</div>

@endsection
