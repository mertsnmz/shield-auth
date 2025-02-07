<?php

namespace App\Http\Requests\Auth;

use App\Services\PasswordPolicyService;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $passwordPolicyService = app(PasswordPolicyService::class);

        return [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => array_merge(
                ['required', 'string', 'confirmed'],
                [$passwordPolicyService->getValidationRules()]
            )
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email is required',
            'email.email' => 'Please enter a valid email address',
            'email.unique' => 'This email is already registered',
            'password.required' => 'Password is required',
            'password.confirmed' => 'Password confirmation does not match'
        ];
    }
} 