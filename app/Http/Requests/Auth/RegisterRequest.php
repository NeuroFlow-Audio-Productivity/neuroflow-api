<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function ($validator): void {
                $email = $this->string('email')->toString();

                if ($email === '') {
                    return;
                }

                $user = User::query()->where('email', $email)->first();

                if (! $user) {
                    return;
                }

                if ($user->google_id !== null) {
                    $validator->errors()->add(
                        'email',
                        'This email already uses Google sign-in. Please continue with Google.',
                    );
                    $validator->errors()->add('auth_provider', 'google');

                    return;
                }

                $validator->errors()->add(
                    'email',
                    'This email is registered with password login. Please sign in with email and password.',
                );
                $validator->errors()->add('auth_provider', 'password');
            },
        ];
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ];
    }
}
