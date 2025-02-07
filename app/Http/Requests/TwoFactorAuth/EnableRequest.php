<?php

namespace App\Http\Requests\TwoFactorAuth;

use Illuminate\Foundation\Http\FormRequest;

class EnableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
} 