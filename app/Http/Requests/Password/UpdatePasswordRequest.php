<?php

namespace App\Http\Requests\Password;

use App\Services\PasswordPolicyService;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
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