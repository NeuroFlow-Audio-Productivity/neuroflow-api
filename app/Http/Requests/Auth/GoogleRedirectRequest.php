<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class GoogleRedirectRequest extends FormRequest
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
            'redirect_uri' => ['nullable', 'url', 'max:2048'],
            'state' => ['nullable', 'string', 'max:500'],
        ];
    }
}
