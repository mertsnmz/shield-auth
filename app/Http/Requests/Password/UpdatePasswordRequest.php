<?php

namespace App\Http\Requests\Password;

use App\Services\PasswordPolicyService;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    public function __construct(
        private readonly PasswordPolicyService $passwordPolicy
    ) {
        parent::__construct();
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
} 