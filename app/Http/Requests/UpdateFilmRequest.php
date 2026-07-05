<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFilmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TODO: kiểm tra quyền admin nếu cần
    }

    /**
     * Chuẩn bị dữ liệu trước khi validate.
     *
     * QUY TẮc:
     *   COMING_SOON → được sửa cả release_date và end_date
     *   NOW_SHOWING → khóa release_date (lấy từ DB)
     *   ENDED       → khóa cả release_date và end_date (lấy từ DB)
     *   HIDDEN      → được sửa cả hai — dùng để tái sử dụng phim, validate >= hôm nay + 3 ngày
     */
    protected function prepareForValidation(): void
    {
        $status = $this->input('status');
        $movie  = \App\Models\Movie::findOrFail($this->route('id'));

        if (in_array($status, ['NOW_SHOWING', 'ENDED'])) {
            // Khóa release_date: dùng giá trị trong DB, bỏ qua gì admin gửi lên
            $this->merge(['release_date' => $movie->release_date]);
        }

        if ($status === 'ENDED') {
            // Khóa luôn end_date với ENDED
            $this->merge(['end_date' => $movie->end_date]);
        }

        // HIDDEN và COMING_SOON: không khóa gì — admin được sửa tự do
    }

    public function rules(): array
    {
        $status = $this->input('status');

        return [
            // ── THÔNG TIN CƠ BẢN ──────────────────────────────────
            'title'            => 'required|string|max:255',
            'original_title'   => 'nullable|string|max:255',
            'description'      => 'nullable|string',

            // ── PHÁT HÀNH ─────────────────────────────────────────
            'duration_minutes' => 'required|integer|min:1|max:500',

            /*
             * COMING_SOON  → release_date có thể thay đổi (phim chưa chiếu)
             * NOW_SHOWING  → release_date bị khóa (đã override ở prepareForValidation)
             * ENDED        → release_date bị khóa (đã override ở prepareForValidation)
             * HIDDEN       → release_date được sửa, nhưng phải >= hôm nay + 3 ngày
             */
            'release_date' => array_filter([
                'required',
                'date',
                $status === 'HIDDEN'
                    ? function ($attribute, $value, $fail) {
                        $minDate = \Carbon\Carbon::today()->addDays(3);
                        if (\Carbon\Carbon::parse($value)->lt($minDate)) {
                            $fail(
                                'Phim ẩn khi tái sử dụng phải có ngày khởi chiếu từ '
                                . $minDate->format('d/m/Y')
                                . ' trở đi (nhắt ít 3 ngày kể từ hôm nay).'
                            );
                        }
                    }
                    : null,
            ]),

            /*
             * NOW_SHOWING  → end_date có thể thay đổi
             * ENDED        → end_date bị khóa
             * COMING_SOON  → end_date có thể thay đổi
             */
            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:release_date',
            ],

            'status' => 'required|in:COMING_SOON,NOW_SHOWING,ENDED,HIDDEN',

            // ── NỘI DUNG ──────────────────────────────────────────
            'country'    => 'required|string|max:100',
            'language'   => 'required|string|max:100',
            'subtitle'   => 'nullable|string|max:100',
            'director'   => 'nullable|string|max:255',
            'age_rating' => 'required|in:P,K,T13,T16,T18',
            'cast'       => 'nullable|string',

            // ── THỂ LOẠI ──────────────────────────────────────────
            'genres'   => 'nullable|array',
            'genres.*' => 'exists:genres,id',

            // ── KIỂU PHÒNG HỖ TRỢ ─────────────────────────────────
            'room_types'   => 'nullable|array',
            'room_types.*' => 'string',

            // ── MEDIA ─────────────────────────────────────────────
            'poster'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'banner'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'trailer_url' => 'nullable|url',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'            => 'Tên phim không được để trống',
            'duration_minutes.required' => 'Vui lòng nhập thời lượng phim',
            'release_date.required'     => 'Ngày khởi chiếu là bắt buộc',
            'release_date.date'         => 'Ngày khởi chiếu không hợp lệ',
            'end_date.after_or_equal'   => 'Ngày kết thúc phải lớn hơn hoặc bằng ngày khởi chiếu',
            'end_date.date'             => 'Ngày kết thúc không hợp lệ',
            'status.in'                 => 'Trạng thái phim không hợp lệ',
            'age_rating.in'             => 'Độ tuổi không hợp lệ (P, K, T13, T16, T18)',
            'poster.image'              => 'Poster phải là file hình ảnh',
            'poster.mimes'              => 'Poster chỉ hỗ trợ jpg, jpeg, png, webp',
            'banner.image'              => 'Banner phải là file hình ảnh',
            'banner.mimes'              => 'Banner chỉ hỗ trợ jpg, jpeg, png, webp',
            'trailer_url.url'           => 'Trailer URL không hợp lệ',
            'language.required'         => 'Ngôn ngữ là bắt buộc',
            'country.required'          => 'Quốc gia là bắt buộc',
            'subtitle.required'         => 'Phụ đề là bắt buộc',
        ];
    }
}
