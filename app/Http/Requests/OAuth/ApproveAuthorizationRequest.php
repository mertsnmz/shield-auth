<?php

namespace App\Http\Requests\OAuth;

use Illuminate\Foundation\Http\FormRequest;

class ApproveAuthorizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'string'],
            'redirect_uri' => ['required', 'string', 'url'],
            'scope' => ['sometimes', 'string'],
            'state' => ['sometimes', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'Client ID is required',
            'redirect_uri.required' => 'Redirect URI is required',
            'redirect_uri.url' => 'Invalid redirect URI format',
        ];
    }
} 