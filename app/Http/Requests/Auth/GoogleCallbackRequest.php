<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

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
            'code' => [$this->isMethod('get') ? 'required_without:error' : 'required', 'string'],
            'error' => ['nullable', 'string', 'max:255'],
            'error_description' => ['nullable', 'string', 'max:2048'],
            'redirect_uri' => ['nullable', 'url', 'max:2048'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'code_verifier' => ['nullable', 'string', 'max:2048'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->isMethod('get')) {
            $url = rtrim((string) config('app.frontend_url'), '/').'/login?'.http_build_query([
                'error' => 'auth_google_invalid_callback',
            ], '', '&', PHP_QUERY_RFC3986);

            throw new HttpResponseException(redirect()->away($url));
        }

        parent::failedValidation($validator);
    }
}
