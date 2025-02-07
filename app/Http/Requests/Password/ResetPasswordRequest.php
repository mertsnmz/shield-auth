<?php

namespace App\Http\Requests\Password;

use App\Services\PasswordPolicyService;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $policy = app(PasswordPolicyService::class);

        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => array_merge(
                ['required', 'string', 'confirmed'],
                $policy->getValidationRules()
            ),
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'Reset token is required',
            'email.required' => 'Email address is required',
            'email.email' => 'Please enter a valid email address',
            'password.required' => 'Password is required',
            'password.confirmed' => 'Password confirmation does not match',
        ];
    }
}
