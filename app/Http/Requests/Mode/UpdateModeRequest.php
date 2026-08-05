<?php

namespace App\Http\Requests\Mode;

use App\Models\Mode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateModeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $mode = $this->targetMode();

        return $mode !== null
            && ($this->user()?->can('update', $mode) ?? false);
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            /**
             * Display name for the mode.
             */
            'name' => ['required', 'string', 'max:255'],
            /**
             * Description for what the mode controls or represents.
             */
            'description' => ['required', 'string'],
            /**
             * Hex color used to represent the mode.
             */
            'color' => ['required', 'string', 'size:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            /**
             * Indicates whether the mode is used by system sounds instead of normal audio playback.
             */
            'is_system' => ['required', 'boolean'],
        ];
    }

    private function targetMode(): ?Mode
    {
        $mode = $this->route('mode');

        return $mode instanceof Mode ? $mode : null;
    }
}
