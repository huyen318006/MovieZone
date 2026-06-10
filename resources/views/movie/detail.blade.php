@extends('layout.app')

@section('content')
@php
    $poster = $movie->poster_url ? asset($movie->poster_url) : asset('assets/hero/avatar.jpg');
    $banner = $movie->banner_url ? asset($movie->banner_url) : asset('assets/hero/avatar2.jpg');
    $averageRating = $movie->approvedReviews->avg('rating');
@endphp

<section class="movie-detail" style="background-image: linear-gradient(to bottom, rgba(0,0,0,0.8) 0%, rgba(20,20,20,0.95) 100%), url('{{ $movie->banner_url ? asset('assets/' . $movie->banner_url) : asset('assets/hero/avatar2.jpg') }}'); background-size: cover; background-position: center;">
    <div class="movie-detail-card" data-aos="fade-up">
        <div class="movie-content">
            <h1 class="movie-title">{{ $movie->title }}</h1>
            @if($movie->original_title)
                <h2 class="movie-original-title">{{ $movie->original_title }}</h2>
            @endif
            <div class="movie-tags">
                <span>{{ $movie->language }}</span> 
                <span>{{ $movie->country }}</span>
                <span>{{ $movie->director }}</span>
            </div>

            <div class="movie-stats">
                <span>
                    <i class="fa-solid fa-star text-warning"></i> 
                    {{ $movie->rating ? number_format($movie->rating, 1) : '0.0' }} / 5.0
                </span>
                <span><i class="fa-regular fa-clock"></i> {{ $movie->duration_minutes }} phút</span>
                <span class="age-badge">{{ $movie->age_rating }}</span>
            </div>

            <p class="movie-description">
                {{ $movie->description ?: 'Nội dung phim đang được cập nhật.' }}
            </p>

            <div class="movie-facts">
                <div>
                    <strong>Đạo diễn</strong>
                    <span>{{ $movie->director ?: 'Đang cập nhật' }}</span>
                </div>
                <div>
                    <strong>Diễn viên</strong>
                    <span>{{ $movie->cast ?: 'Đang cập nhật' }}</span>
                </div>
                <div>
                    <strong>Phụ đề</strong>
                    <span>{{ $movie->subtitle ?: 'Không có' }}</span>
                </div>
                <div>
                    <strong>Khởi chiếu</strong>
                    <span>{{ $movie->release_date ? \Carbon\Carbon::parse($movie->release_date)->format('d/m/Y') : 'Đang cập nhật' }}</span>
                </div>
            </div>

            <div class="movie-actions">
                <a href="{{ route('showtimes', ['movie' => $movie->id]) }}" class="btn-book">
                    <i class="fa-solid fa-ticket"></i> Xem Suất Chiếu
                </a>
                <button class="btn-trailer" data-bs-toggle="modal" data-bs-target="#trailerModal">
                    <i class="fa-solid fa-play"></i> Xem Trailer
                </button>
            </div>
        </div>

        <div class="movie-poster">
            <img src="{{ $poster }}" alt="Poster {{ $movie->title }}">
            <button class="poster-play-btn" data-bs-toggle="modal" data-bs-target="#trailerModal">
                <i class="fa-solid fa-play"></i>
            </button>
        </div>
    </div>
</section>

<section id="movie-showtimes" class="movie-detail-section movie-showtime-section">
    <div class="section-title">
        <h2>Lịch Chiếu Liên Quan</h2>
        <a href="{{ route('showtimes', ['movie' => $movie->id]) }}">Xem lịch chiếu đầy đủ</a>
    </div>

    @if($movie->showtimes->isEmpty())
        <div class="movie-detail-empty">
            <i class="bi bi-calendar-x"></i>
            <h3>Chưa có suất chiếu phù hợp</h3>
            <p>Lịch chiếu chi tiết sẽ được cập nhật ở UC-CUS-07.</p>
        </div>
    @else
        <div class="detail-showtime-grid">
            @foreach($movie->showtimes as $showtime)
                <article class="detail-showtime-card">
                    <div>
                        <span class="showtime-date">{{ $showtime->start_time ? \Carbon\Carbon::parse($showtime->start_time)->format('d/m/Y') : 'Đang cập nhật' }}</span>
                        <strong>{{ $showtime->start_time ? \Carbon\Carbon::parse($showtime->start_time)->format('H:i') : '--:--' }}</strong>
                    </div>
                    <p>{{ $showtime->cinema?->name ?: 'Rạp đang cập nhật' }}</p>
                    <span>{{ $showtime->room?->name ?: 'Phòng đang cập nhật' }} • {{ $showtime->format }} • {{ $showtime->language_type }}</span>
                    <a href="{{ route('booking.seat', ['showtime_id' => $showtime->id]) }}">Chọn suất</a>
                </article>
            @endforeach
        </div>
    @endif
</section>

<section class="movie-detail-section movie-review-section">
    <div class="section-title">
        <h2>Đánh Giá Từ Khán Giả</h2>
    </div>
    <div class="review-alerts-container" data-aos="fade-up">
    {{-- 1. Thông báo THÀNH CÔNG --}}
    @if(session('success'))
        <div class="custom-alert alert-success-dark alert-dismissible fade show" role="alert">
            <span>{{ session('success') }}</span>
            <button type="button" class="btn-close-custom" data-bs-dismiss="alert" aria-label="Close">&times;</button>
        </div>
    @endif

    {{-- 2. Thông báo VI PHẠM TỤC TĨU / LỖI --}}
    @if($errors->has('review') || $errors->has('comment'))
        <div class="custom-alert alert-danger-dark alert-dismissible fade show" role="alert">
            <span>
                @if($errors->has('review'))
                    {{ $errors->first('review') }}
                @else
                    {{ $errors->first('comment') }}
                @endif
            </span>
            <button type="button" class="btn-close-custom" data-bs-dismiss="alert" aria-label="Close">&times;</button>
        </div>
    @endif
</div>
    <!-- Khung hiển thị / viết đánh giá của bản thân (UC-CUS-14) -->
    <div class="my-review-container mb-5">
        @auth
            @php
                $myReview = $movie->reviews()->where('user_id', auth()->id())->first();
                $isCustomer = \App\Models\UserRole::where('user_id', auth()->id())->where('role_id', 3)->exists();
                $hasBooking = \App\Models\Booking::where('user_id', auth()->id())
                    ->whereHas('showtime', function ($query) use ($movie) {
                        $query->where('movie_id', $movie->id);
                    })
                    ->where(function ($query) {
                        $query->where('status', 'PAID')
                              ->orWhere('payment_status', 'PAID')
                              ->orWhereHas('tickets', function ($q) {
                                  $q->whereNotNull('checked_in_at');
                              });
                    })
                    ->exists();
                $isAdminUser = \App\Models\UserRole::where('user_id', auth()->id())->where('role_id', 1)->exists();
            @endphp

            @if($myReview)
                <!-- Người dùng đã có đánh giá (E5: cho phép sửa đổi) -->
                <div class="my-review-box" id="review-display-{{ $myReview->id }}" data-aos="fade-up">
                    <div class="review-box-header">
                        <div class="review-user-info">
                            <i class="fa-solid fa-circle-user avatar-icon text-primary"></i>
                            <div>
                                <h4>Đánh giá của bạn</h4>
                                <small class="text-muted">Gửi lúc: {{ $myReview->created_at?->format('H:i d/m/Y') }}</small>
                            </div>
                        </div>
                        <div class="review-actions-btns">
                            <button class="btn-edit-review" onclick="showEditForm({{ $myReview->id }})">
                                <i class="fa-solid fa-pen-to-square"></i> Chỉnh sửa
                            </button>
                            <form action="{{ route('reviews.destroy', $myReview->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa đánh giá này?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-delete-review">
                                    <i class="fa-solid fa-trash"></i> Xóa
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="review-box-content mt-3">
                        <div class="stars-display mb-2">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fa-solid fa-star {{ $i <= $myReview->rating ? 'text-warning' : 'text-secondary' }}"></i>
                            @endfor
                            <span class="rating-number ms-2">{{ $myReview->rating }}.0 / 5.0</span>
                        </div>
                        <p class="review-comment">{{ $myReview->comment ?: 'Bạn chỉ chấm điểm sao, không để lại bình luận.' }}</p>
                    </div>
                </div>

                <!-- Form chỉnh sửa ẩn đi lúc đầu (A2) -->
                <div class="review-form-box" id="review-edit-form-{{ $myReview->id }}" style="display: none;" data-aos="fade-up">
                    <h4>Chỉnh sửa đánh giá</h4>
                    
                    @if($errors->any())
                        <div class="alert alert-danger bg-danger-subtle border-danger-subtle text-danger-emphasis mb-3">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('reviews.update', $myReview->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-4">
                            <label class="form-label font-weight-bold d-block text-light">Số sao đánh giá:</label>
                            <div class="star-rating edit-star-rating">
                                @for($i = 5; $i >= 1; $i--)
                                    <input type="radio" id="edit-star{{ $i }}" name="rating" value="{{ $i }}" {{ $myReview->rating == $i ? 'checked' : '' }} />
                                    <label for="edit-star{{ $i }}" title="{{ $i }} sao"><i class="fa-solid fa-star"></i></label>
                                @endfor
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="edit-comment" class="form-label font-weight-bold text-light">Bình luận của bạn:</label>
                            <textarea class="form-control bg-dark text-white border-secondary" id="edit-comment" name="comment" rows="4" maxlength="500" placeholder="Viết bình luận của bạn...">{{ old('comment', $myReview->comment) }}</textarea>
                            @error('comment')
                                <div class="text-danger mt-2" style="font-size: 14px;">
                                    <i class="fa-solid fa-circle-exclamation me-1"></i> {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="form-actions d-flex gap-2">
                            <button type="submit" class="btn-submit-review">Cập nhật</button>
                            <button type="button" class="btn-cancel-review" onclick="hideEditForm({{ $myReview->id }})">Hủy</button>
                        </div>
                    </form>
                </div>
            @elseif($isAdminUser)
                <div class="alert alert-info bg-dark border-secondary text-info d-flex align-items-center gap-3 py-3 px-4 rounded-4" data-aos="fade-up">
                    <i class="fa-solid fa-shield-halved fs-4 text-info"></i>
                    <div>
                        <strong class="d-block text-white mb-1">Chế độ Quản trị viên</strong>
                        Bạn có thể quản lý, duyệt và xóa cứng các đánh giá không phù hợp từ khán giả.
                    </div>
                </div>
            @elseif(!$isCustomer)
                <div class="alert alert-info bg-dark border-secondary text-info d-flex align-items-center gap-3 py-3 px-4 rounded-4" data-aos="fade-up">
                    <i class="fa-solid fa-circle-info fs-4 text-info"></i>
                    <div>
                        <strong class="d-block text-white mb-1">Quyền đánh giá bị giới hạn</strong>
                        Chỉ các tài khoản khách hàng mới được phép thực hiện đánh giá phim.
                    </div>
                </div>
            @elseif(!$hasBooking)
                <!-- E2: Không có booking hợp lệ -->
                <div class="alert alert-warning bg-dark border-secondary text-warning d-flex align-items-center gap-3 py-3 px-4 rounded-4" data-aos="fade-up">
                    <i class="fa-solid fa-circle-exclamation fs-4 text-warning"></i>
                    <div>
                        <strong class="d-block text-white mb-1">Bạn cần có vé hợp lệ để đánh giá phim này</strong>
                        Chỉ các khách hàng có vé đã thanh toán (PAID) hoặc đã check-in đối với phim này mới có thể viết đánh giá.
                    </div>
                </div>
            
            @else
                <!-- Hiển thị form tạo đánh giá mới -->
                <div class="review-form-box" data-aos="fade-up">
                    <h4>Gửi đánh giá của bạn</h4>
                    
                    @if($errors->any())
                        <div class="alert alert-danger bg-danger-subtle border-danger-subtle text-danger-emphasis mb-3">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('movies.review.store', $movie->id) }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label font-weight-bold d-block text-light">Chọn số sao:</label>
                            <div class="star-rating">
                                <input type="radio" id="star5" name="rating" value="5" /><label for="star5" title="5 sao"><i class="fa-solid fa-star"></i></label>
                                <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="4 sao"><i class="fa-solid fa-star"></i></label>
                                <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="3 sao"><i class="fa-solid fa-star"></i></label>
                                <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="2 sao"><i class="fa-solid fa-star"></i></label>
                                <input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="1 sao"><i class="fa-solid fa-star"></i></label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="comment" class="form-label font-weight-bold text-light">Bình luận (không bắt buộc, tối đa 500 ký tự):</label>
                            <textarea class="form-control bg-dark text-white border-secondary" id="comment" name="comment" rows="4" placeholder="Nhập suy nghĩ của bạn về phim (tùy chọn)..." maxlength="500">{{ old('comment') }}</textarea>
                            @error('comment')
                                <div class="text-danger mt-2" style="font-size: 14px;">
                                    <i class="fa-solid fa-circle-exclamation me-1"></i> {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <button type="submit" class="btn-submit-review">Gửi đánh giá</button>
                    </form>
                </div>
            @endif
        @else
            <!-- E1: Chưa đăng nhập -->
            <div class="alert alert-warning bg-dark border-secondary text-warning d-flex align-items-center gap-3 py-3 px-4 rounded-4" data-aos="fade-up">
                <i class="fa-solid fa-circle-exclamation fs-4 text-warning"></i>
                <div>
                    <strong class="d-block text-white mb-1">Yêu cầu đăng nhập</strong>
                    Vui lòng <a href="{{ route('login') }}" class="text-warning text-decoration-underline font-weight-bold">đăng nhập</a> và mua vé hợp lệ để đánh giá phim này.
                </div>
            </div>
        @endauth
    </div>

    <!-- Danh sách đánh giá công khai (A4) -->
    <div class="public-reviews-container">
        <h3 class="mb-4 text-light"><i class="fa-regular fa-comments text-primary me-2"></i> Nhận xét từ người xem khác</h3>

        @php
            $publicReviews = $movie->approvedReviews->filter(function($r) {
                return !auth()->check() || $r->user_id !== auth()->id();
            });
        @endphp

        @if($publicReviews->isEmpty())
            <div class="movie-detail-empty rounded-4 p-5" data-aos="fade-up">
                <i class="bi bi-chat-square-heart display-4 text-secondary mb-3 d-block"></i>
                <h4 class="text-white">Chưa có đánh giá khác</h4>
                <p class="text-muted">Hãy là một trong những người đầu tiên chia sẻ cảm nhận về bộ phim này!</p>
            </div> 
        @else           
        <div class="detail-review-grid">
            @foreach($publicReviews as $review)
                    <article class="detail-review-card" data-aos="fade-up">
                    <div class="review-head">
                        <div class="review-author">
                                <div class="avatar-small">
                                    {{ mb_substr($review->user?->name ?: 'K', 0, 1) }}
                                </div>
                                <strong class="text-white">{{ $review->user?->name ?: 'Khách hàng MovieZone' }}</strong>
                                
                                @auth
                                    @if(\App\Models\UserRole::where('user_id', auth()->id())->where('role_id', 1)->exists())
                                        <!-- BR06: Xóa cứng đánh giá chỉ dành cho Admin -->
                                        <form action="{{ route('reviews.destroy', $review->id) }}" method="POST" class="d-inline ms-2" onsubmit="return confirm('Quản trị viên: Bạn có chắc chắn muốn XÓA CỨNG đánh giá này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-link text-danger p-0 border-0 align-baseline" style="font-size: 13px;" title="Xóa cứng (Admin)">
                                                <i class="fa-solid fa-trash-can"></i> Xóa cứng
                                            </button>
                                        </form>
                                    @endif
                                @endauth
                            </div>
                            <span class="badge-rating"><i class="fa-solid fa-star text-warning"></i> {{ $review->rating }}/5</span>
                        </div>
                        <p class="review-body">{{ $review->comment ?: 'Người dùng chỉ chấm điểm sao.' }}</p>
                        <div class="review-meta">
                            <small class="text-muted">{{ $review->created_at?->format('d/m/Y H:i') }}</small>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
    </div>
</section>

<script>
    function showEditForm(reviewId) {
        document.getElementById('review-display-' + reviewId).style.display = 'none';
        document.getElementById('review-edit-form-' + reviewId).style.display = 'block';
    }
    
    function hideEditForm(reviewId) {
        document.getElementById('review-display-' + reviewId).style.display = 'block';
        document.getElementById('review-edit-form-' + reviewId).style.display = 'none';
    }
</script>

<div class="modal fade" id="trailerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content trailer-modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Trailer - {{ $movie->title }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if($trailerEmbedUrl)
                    <iframe
                        src="{{ $trailerEmbedUrl }}"
                        title="Trailer {{ $movie->title }}"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen></iframe>
                @else
                    <div class="trailer-error">
                        <i class="bi bi-exclamation-triangle"></i>
                        <p>Không thể tải trailer</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection