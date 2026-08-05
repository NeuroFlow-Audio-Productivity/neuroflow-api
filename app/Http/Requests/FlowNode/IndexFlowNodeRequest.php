<?php

namespace App\Http\Requests\FlowNode;

use App\Models\Flow;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexFlowNodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $flowId = $this->query('flow_id');

        if ($flowId === null || $flowId === '') {
            return $this->user() !== null;
        }

        $flow = Flow::query()->find($flowId);

        return $flow === null
            || ($this->user()?->can('view', $flow) ?? false);
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            /**
             * Flow whose nodes should be listed. The authenticated user must own this flow.
             */
            'flow_id' => ['required', Rule::exists('flows', 'id')],
        ];
    }
}
