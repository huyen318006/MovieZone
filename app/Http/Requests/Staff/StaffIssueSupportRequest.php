<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class StaffIssueSupportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'input_type' => 'required|in:booking_code,ticket_code,phone,email,qr_content',
            'input_value' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'input_type.in' => 'Loại dữ liệu nhập không hợp lệ.',
            'input_value.required' => 'Vui lòng nhập thông tin sự cố.',
        ];
    }
}


