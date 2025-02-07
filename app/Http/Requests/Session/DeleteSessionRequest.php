<?php

namespace App\Http\Requests\Session;

use Illuminate\Foundation\Http\FormRequest;

class DeleteSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Session ID is required',
            'id.exists' => 'Invalid session ID'
        ];
    }
} 