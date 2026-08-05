<?php

namespace App\Http\Requests\Flow;

use App\Models\Flow;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFlowRequest extends FormRequest
{
    public function authorize(): bool
    {
        $flow = $this->targetFlow();

        return $flow !== null
            && ($this->user()?->can('update', $flow) ?? false);
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            /**
             * Display name for the flow. The owner cannot be changed through this endpoint.
             */
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    private function targetFlow(): ?Flow
    {
        $flow = $this->route('flow');

        return $flow instanceof Flow ? $flow : null;
    }
}
