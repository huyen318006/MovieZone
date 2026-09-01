<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Review;
use App\Models\Booking;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    /**
     * Store a new movie review.
     */
    public function store(Request $request, $movieId)
    {
        $movie = Movie::findOrFail($movieId);
        $userId = Auth::id();

        // BR01: Chỉ Customer có booking PAID hoặc CHECKED_IN cho phim đó mới được đánh giá.
        $isCustomer = UserRole::where('user_id', $userId)->where('role_id', 3)->exists();
        
        // KIỂM TRA VÉ HỢP LỆ 
        $hasBooking = false; 

        // Lấy ra toàn bộ các đơn đặt vé của User này đối với bộ phim hiện tại
        $userBookings = Booking::where('user_id', $userId)
            ->whereHas('showtime', function ($query) use ($movie) {
                $query->where('movie_id', $movie->id);
            })
            ->get();

        // Duyệt qua từng đơn hàng để kiểm tra điều kiện
        foreach ($userBookings as $booking) {
            // đã được thanh toán chưa
            $isPaid = ($booking->status === 'PAID' || $booking->payment_status === 'PAID');

            // đã bắt đầu chiếu hay chưa
            $showtimeStartTime = $booking->showtime->start_time; 
            $hasMovieStarted = (strtotime($showtimeStartTime) <= time()); 

            //  đã check-in vào rạp chưa
            $hasCheckedIn = $booking->tickets()->whereNotNull('checked_in_at')->exists();

            // D. Điều kiện hợp lệ: (Đã thanh toán Xong + Phim đã chiếu rồi) / (Đã check-in thực tế)
            if (($isPaid && $hasMovieStarted) || $hasCheckedIn) {
                $hasBooking = true; 
                break; 
            }
            return back()->withErrors(['review' => 'Bạn cần có vé đã thanh toán và đã checkin để đánh giá phim này.'])->withInput();
        }

        if (!$isCustomer || !$hasBooking) {
            return back()->withErrors(['review' => 'Bạn cần có vé hợp lệ để đánh giá phim này.'])->withInput();
        }

        // BR03: Mỗi Customer chỉ có một đánh giá cho một phim
        $existingReview = Review::where('user_id', $userId)
            ->where('movie_id', $movie->id)
            ->first();

        if ($existingReview) {
            return back()->withErrors(['review' => 'Bạn đã đánh giá phim này rồi. Vui lòng chỉnh sửa đánh giá cũ.'])->withInput();
        }

        // BR02: Số sao đánh giá nằm trong khoảng 1–5.
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ], [
            'rating.required' => 'Vui lòng chọn số sao từ 1 đến 5.',
            'rating.integer' => 'Số sao không hợp lệ.',
            'rating.min' => 'Vui lòng chọn số sao từ 1 đến 5.',
            'rating.max' => 'Vui lòng chọn số sao từ 1 đến 5.',
            'comment.max' => 'Bình luận không được vượt quá 500 ký tự.',
        ]);

        $comment = $request->input('comment');

        // BR08: Hệ thống phải kiểm tra nội dung bình luận để hạn chế spam hoặc nội dung không phù hợp.
        if ($this->isInappropriate($comment)) {
            return back()->withErrors(['comment' => 'Bình luận chứa từ ngữ không phù hợp hoặc bị nghi ngờ là spam. Vui lòng chỉnh sửa lại.'])->withInput();
        }

        // Create and save review
        Review::create([
            'user_id' => $userId,
            'movie_id' => $movie->id,
            'rating' => $request->input('rating'),
            'comment' => $comment,
            'status' => 'APPROVED', // Default to APPROVED so it's shown immediately
        ]);

        // Recalculate average rating of the movie
        $movie->recalculateRating();

        return back()->with('success', 'Đánh giá của bạn đã được gửi thành công!');
    }

    /**
     * BR04: Customer được phép chỉnh sửa đánh giá của chính mình.
     */
    public function update(Request $request, $reviewId)
    {
        $review = Review::findOrFail($reviewId);
        $userId = Auth::id();

        // Verify ownership
        if ($review->user_id !== $userId) {
            abort(403, 'Bạn không có quyền chỉnh sửa đánh giá này.');
        }

        // Validation
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ], [
            'rating.required' => 'Vui lòng chọn số sao từ 1 đến 5.',
            'rating.integer' => 'Số sao không hợp lệ.',
            'rating.min' => 'Vui lòng chọn số sao từ 1 đến 5.',
            'rating.max' => 'Vui lòng chọn số sao từ 1 đến 5.',
            'comment.max' => 'Bình luận không được vượt quá 500 ký tự.',
        ]);

        $comment = $request->input('comment');

        // Profanity & spam check
        if ($this->isInappropriate($comment)) {
            return back()->withErrors(['comment' => 'Bình luận chứa từ ngữ không phù hợp hoặc bị nghi ngờ là spam. Vui lòng chỉnh sửa lại.'])->withInput();
        }

        // Save changes
        $review->update([
            'rating' => $request->input('rating'),
            'comment' => $comment,
        ]);

        // Update movie rating
        $movie = Movie::find($review->movie_id);
        if ($movie) {
            $movie->recalculateRating();
        }

        return back()->with('success', 'Đã cập nhật đánh giá của bạn.');
    }

    /**
     * BR05: Customer chỉ được xóa mềm đánh giá của chính mình.
     * BR06: Xóa cứng đánh giá chỉ dành cho Admin.
     */
    public function destroy($reviewId)
    {
        $review = Review::withTrashed()->findOrFail($reviewId);
        $userId = Auth::id();

        // Check if user is Admin (role_id = 1)
        $isAdmin = UserRole::where('user_id', $userId)->where('role_id', 1)->exists();

        if ($isAdmin) {
            // BR06: Xóa cứng đánh giá chỉ dành cho Admin.
            $movieId = $review->movie_id;
            $review->forceDelete();
            
            $movie = Movie::find($movieId);
            if ($movie) {
                $movie->recalculateRating();
            }
            
            return back()->with('success', 'Quản trị viên đã xóa cứng đánh giá thành công.');
        }

        // BR05: Customer chỉ được xóa mềm đánh giá của chính mình.
        if ($review->user_id !== $userId) {
            abort(403, 'Bạn không có quyền xóa đánh giá này.');
        }

        $review->delete(); // Soft delete

        // Recalculate rating
        $movie = Movie::find($review->movie_id);
        if ($movie) {
            $movie->recalculateRating();
        }

        return back()->with('success', 'Đã xóa đánh giá thành công.');
    }

    /**
     * Inappropriate content & spam checker (BR08)
     */
    private function isInappropriate($text)
    {
        if (empty($text)) {
            return false;
        }

        // Inappropriate / offensive / spam keywords list
        $badWords = [
            'spam', 'scam', 'lừa đảo', 'phản động', 'độc hại', 
            'đm', 'dm', 'đéo', 'vcl', 'vãi', 'chửi', 'cút', 'chó', 'nhảm', 'dcm', 'cl', 'đmm', 'đm m'
        ];

        $textLower = mb_strtolower($text, 'UTF-8');
        foreach ($badWords as $word) {
            if (mb_strpos($textLower, $word) !== false) {
                return true;
            }
        }

        return false;
    }
}
