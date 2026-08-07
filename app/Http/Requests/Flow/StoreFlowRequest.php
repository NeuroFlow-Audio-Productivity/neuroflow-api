<?php

namespace App\Http\Requests\Flow;

use App\Models\Flow;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreFlowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Flow::class) ?? false;
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            /**
             * Display name for the flow. The owner is always the authenticated user.
             */
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
