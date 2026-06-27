<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/**
 * T2-04: Validate check-in confirmation.
 */
class ConfirmCheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ticket_id' => 'required|integer|exists:tickets,id',
        ];
    }

    public function messages(): array
    {
        return [
            'ticket_id.required' => 'Thiếu thông tin vé.',
            'ticket_id.exists' => 'Vé không tồn tại.',
        ];
    }
}
