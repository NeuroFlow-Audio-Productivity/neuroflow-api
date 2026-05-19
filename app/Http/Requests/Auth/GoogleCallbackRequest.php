<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class GoogleCallbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
            'redirect_uri' => ['nullable', 'url', 'max:2048'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'code_verifier' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
