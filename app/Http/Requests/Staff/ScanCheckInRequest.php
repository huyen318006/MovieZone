<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/**
 * T2-02: Validate QR scan input.
 */
class ScanCheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth handled by middleware
    }

    public function rules(): array
    {
        return [
            'qr_content' => 'required|string|min:10|max:200',
        ];
    }

    public function messages(): array
    {
        return [
            'qr_content.required' => 'Nội dung QR không được để trống.',
            'qr_content.min' => 'Nội dung QR quá ngắn.',
            'qr_content.max' => 'Nội dung QR quá dài.',
        ];
    }
}
