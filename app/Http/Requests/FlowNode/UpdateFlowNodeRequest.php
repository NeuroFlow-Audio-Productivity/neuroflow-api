<?php

namespace App\Http\Requests\FlowNode;

use App\Models\Flow;
use App\Models\FlowNode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFlowNodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $flowNode = $this->targetFlowNode();

        return $flowNode !== null
            && ($this->user()?->can('update', $flowNode) ?? false)
            && $this->canUseFlow();
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            /**
             * Time in minutes for this node.
             */
            'time' => ['required', 'integer', 'min:1'],
            /**
             * Position of this node inside its flow.
             */
            'order' => ['required', 'integer', 'min:1'],
            /**
             * Parent flow. The authenticated user must own this flow.
             */
            'flow_id' => ['required', Rule::exists('flows', 'id')],
            /**
             * Mode applied during this node.
             */
            'mode_id' => ['required', Rule::exists('modes', 'id')],
            /**
             * Audio played when this node ends.
             */
            'end_audio_id' => ['required', Rule::exists('audios', 'id')],
        ];
    }

    private function targetFlowNode(): ?FlowNode
    {
        $flowNode = $this->route('flow_node');

        return $flowNode instanceof FlowNode ? $flowNode : null;
    }

    private function canUseFlow(): bool
    {
        $flowId = $this->input('flow_id');

        if ($flowId === null || $flowId === '') {
            return true;
        }

        $flow = Flow::query()->find($flowId);

        return $flow === null
            || ($this->user()?->can('update', $flow) ?? false);
    }
}
