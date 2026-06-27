<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/**
 * T2-03: Validate manual code input.
 */
class ManualCheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|min:5|max:50',
            'type' => 'required|in:booking_code,ticket_code',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Vui lòng nhập mã booking hoặc mã vé.',
            'type.required' => 'Vui lòng chọn loại mã.',
            'type.in' => 'Loại mã không hợp lệ.',
        ];
    }
}
