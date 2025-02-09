<?php

namespace App\Http\Requests\OAuth;

use Illuminate\Foundation\Http\FormRequest;

class IssueTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'grant_type' => ['required', 'string', 'in:authorization_code,client_credentials,refresh_token'],
            'client_id' => ['required', 'string'],
            'client_secret' => ['required', 'string'],
        ];

        if ($this->input('grant_type') === 'authorization_code') {
            $rules['code'] = ['required', 'string'];
            $rules['redirect_uri'] = ['required', 'string', 'url'];
        } elseif ($this->input('grant_type') === 'refresh_token') {
            $rules['refresh_token'] = ['required', 'string'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'grant_type.required' => 'Grant type is required',
            'grant_type.in' => 'Invalid grant type',
            'client_id.required' => 'Client ID is required',
            'client_secret.required' => 'Client secret is required',
            'code.required' => 'Authorization code is required',
            'redirect_uri.required' => 'Redirect URI is required',
            'redirect_uri.url' => 'Invalid redirect URI format',
            'refresh_token.required' => 'Refresh token is required',
        ];
    }
} 