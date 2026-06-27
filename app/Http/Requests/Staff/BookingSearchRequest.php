<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/**
 * S2-01: Validation cho API tìm kiếm booking.
 *
 * Conditional validation: regex của search_value phụ thuộc vào search_type.
 */
class BookingSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization đã được xử lý bởi middleware
    }

    public function rules(): array
    {
        return [
            'search_type'        => 'required|in:booking_code,ticket_code,phone,email',
            'search_value'       => 'required|string|max:255',
            'booking_status'     => 'nullable|in:PENDING,PAID,CANCELLED,EXPIRED',
            'payment_status'     => 'nullable|in:UNPAID,PAID,FAILED,REFUNDED',
            'showtime_date_from' => 'nullable|date_format:Y-m-d',
            'showtime_date_to'   => 'nullable|date_format:Y-m-d|after_or_equal:showtime_date_from',
            'cinema_id'          => 'nullable|integer|exists:cinemas,id',
            'page'               => 'nullable|integer|min:1',
            'per_page'           => 'nullable|integer|in:10,15,25,50',
            'sort_by'            => 'nullable|in:created_at,start_time,booking_code',
            'sort_dir'           => 'nullable|in:asc,desc',
        ];
    }

    /**
     * Conditional validation dựa trên search_type.
     */
    public function withValidator($validator): void
    {
        $validator->sometimes('search_value', ['regex:/^BK-\d{8}-\d{3,}$/'], function ($input) {
            return $input->search_type === 'booking_code';
        });

        $validator->sometimes('search_value', ['regex:/^TK-\d{8}-\d{3,}$/'], function ($input) {
            return $input->search_type === 'ticket_code';
        });

        $validator->sometimes('search_value', ['regex:/^(0|\+84)(3|5|7|8|9)\d{8}$/'], function ($input) {
            return $input->search_type === 'phone';
        });

        $validator->sometimes('search_value', ['email'], function ($input) {
            return $input->search_type === 'email';
        });
    }

    public function messages(): array
    {
        return [
            'search_type.required'        => 'Vui lòng chọn loại tìm kiếm.',
            'search_type.in'              => 'Loại tìm kiếm không hợp lệ.',
            'search_value.required'       => 'Vui lòng nhập giá trị tìm kiếm.',
            'search_value.max'            => 'Giá trị tìm kiếm không được quá 255 ký tự.',
            'search_value.regex'          => 'Giá trị tìm kiếm không đúng định dạng.',
            'search_value.email'          => 'Email không đúng định dạng.',
            'booking_status.in'           => 'Trạng thái booking không hợp lệ.',
            'payment_status.in'           => 'Trạng thái thanh toán không hợp lệ.',
            'showtime_date_from.date_format' => 'Ngày bắt đầu không đúng định dạng (YYYY-MM-DD).',
            'showtime_date_to.date_format'   => 'Ngày kết thúc không đúng định dạng (YYYY-MM-DD).',
            'showtime_date_to.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
            'cinema_id.exists'            => 'Rạp chiếu không tồn tại.',
        ];
    }
}
