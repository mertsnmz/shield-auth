<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user() ? $this->user()->id : null;

        return [
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users')->ignore($userId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.email' => 'Please enter a valid email address',
            'email.unique' => 'This email address is already in use',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'email' => [
                'description' => 'The new email address for the user',
                'example' => 'newuser@example.com',
            ],
        ];
    }
}
