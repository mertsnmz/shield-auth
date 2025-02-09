<?php

namespace App\Http\Requests\OAuth;

use Illuminate\Foundation\Http\FormRequest;

class AuthorizeRequest extends FormRequest
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
            'response_type' => ['required', 'string', 'in:code'],
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
            'response_type.required' => 'Response type is required',
            'response_type.in' => 'Invalid response type',
        ];
    }
} 