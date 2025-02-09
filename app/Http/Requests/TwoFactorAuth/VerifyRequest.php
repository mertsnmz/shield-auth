<?php

namespace App\Http\Requests\TwoFactorAuth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'size:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => '2FA verification code is required',
            'code.size' => '2FA verification code must be 6 digits',
        ];
    }
}
