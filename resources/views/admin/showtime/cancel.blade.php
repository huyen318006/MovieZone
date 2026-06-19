@extends('layout.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm border-0 mx-auto" style="max-width: 760px;">
        <div class="card-body p-4 p-md-5">
            <h3 class="mb-2">Xác nhận hủy suất chiếu</h3>
            <p class="text-muted mb-4">
                Bạn đang hủy suất chiếu của phim <strong>{{ $showtime->movie->title ?? 'N/A' }}</strong>
                tại phòng <strong>{{ $showtime->room->name ?? 'N/A' }}</strong>.
            </p>

            @if($bookingCount > 0)
                <div class="alert alert-warning">
                    Suất chiếu này đã có <strong>{{ $bookingCount }}</strong> booking liên quan.
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('admin.showtime.cancel', $showtime->id) }}">
                @csrf

                <div class="mb-3">
                    <label for="reason" class="form-label">Lý do hủy</label>
                    <textarea
                        id="reason"
                        name="reason"
                        class="form-control @error('reason') is-invalid @enderror"
                        rows="4"
                        required
                        maxlength="255"
                        placeholder="Nhập lý do hủy suất chiếu..."
                    >{{ old('reason') }}</textarea>
                    @error('reason')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2 justify-content-end">
                    <a href="{{ route('admin.showtime') }}" class="btn btn-light">Quay lại</a>
                    <button type="submit" class="btn btn-danger">Xác nhận hủy</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
