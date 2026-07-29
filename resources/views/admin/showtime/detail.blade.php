@extends('layout.admin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">Chi tiết suất chiếu</h3>
            <p class="text-muted mb-0">Thông tin suất chiếu trong rạp duy nhất của hệ thống.</p>
        </div>
        <a href="{{ \App\Helpers\TabAuthHelper::route('admin.showtime') }}" class="btn btn-outline-secondary">Quay lại</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Phim</dt>
                <dd class="col-sm-9">{{ $showtime->movie->title ?? 'N/A' }}</dd>

                <dt class="col-sm-3">Rạp</dt>
                <dd class="col-sm-9">{{ $showtime->cinema->name ?? 'N/A' }}</dd>

                <dt class="col-sm-3">Phòng chiếu</dt>
                <dd class="col-sm-9">{{ $showtime->room->name ?? 'N/A' }}</dd>

                <dt class="col-sm-3">Thời gian</dt>
                <dd class="col-sm-9">
                    {{ optional($showtime->start_time)->format('d/m/Y H:i') }}
                    -
                    {{ optional($showtime->end_time)->format('d/m/Y H:i') }}
                </dd>

                {{-- <dt class="col-sm-3">Ngôn ngữ</dt>
                <dd class="col-sm-9">{{ $showtime->language_type }}</dd> --}}

                <dt class="col-sm-3">Trạng thái</dt>
                <dd class="col-sm-9">{{ $showtime->status }}</dd>
            </dl>
        </div>
    </div>
</div>
@endsection