<?php

namespace App\Http\Requests\Password;

use App\Services\PasswordPolicyService;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    private readonly PasswordPolicyService $passwordPolicy;

    public function __construct()
    {
        parent::__construct();
        $this->passwordPolicy = app(PasswordPolicyService::class);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required'],
            'password' => array_merge(
                ['required', 'confirmed'],
                [$this->passwordPolicy->getValidationRules()]
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

    public function bodyParameters(): array
    {
        return [
            'current_password' => [
                'description' => 'Current password of the user',
                'example' => 'current-secret-password'
            ],
            'password' => [
                'description' => 'New password that meets the password policy requirements',
                'example' => 'new-secret-password'
            ],
            'password_confirmation' => [
                'description' => 'Confirmation of the new password',
                'example' => 'new-secret-password'
            ]
        ];
    }
}
