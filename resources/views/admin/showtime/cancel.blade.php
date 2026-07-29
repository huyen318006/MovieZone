@extends('layout.admin')

@section('title', 'Xác nhận hủy suất chiếu')

@section('content')
<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h3 class="mb-1">Xác nhận hủy suất chiếu</h3>
        <p class="text-muted mb-0">
            Bạn đang hủy suất chiếu của phim <strong>{{ $showtime->movie->title ?? 'N/A' }}</strong>
            tại phòng <strong>{{ $showtime->room->name ?? 'N/A' }}</strong>.
        </p>
    </div>
</div>

<div class="card border-0 shadow-sm mx-auto" style="max-width: 760px;">
    <div class="card-body p-4 p-md-5">
        @if($bookingCount > 0)
            <div class="alert alert-warning">
                Suất chiếu này đã có <strong>{{ $bookingCount }}</strong> booking liên quan.
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ \App\Helpers\TabAuthHelper::route('admin.showtime.cancel', ['id' => $showtime->id]) }}">
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
                <a href="{{ \App\Helpers\TabAuthHelper::route('admin.showtime') }}" class="btn btn-outline-secondary">Quay lại</a>
                <button type="submit" class="btn btn-danger">Xác nhận hủy</button>
            </div>
        </form>
    </div>
</div>
@endsection
