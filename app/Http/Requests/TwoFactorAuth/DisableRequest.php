<?php

namespace App\Http\Requests\TwoFactorAuth;

use Illuminate\Foundation\Http\FormRequest;

class DisableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'code' => ['required', 'string', 'size:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Current password is required',
            'code.required' => '2FA code is required',
            'code.size' => '2FA code must be 6 digits',
        ];
    }
}
