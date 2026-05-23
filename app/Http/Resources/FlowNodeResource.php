<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class FlowNodeResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'time' => $this->time,
            'order' => $this->order,
            'flow_id' => $this->flow_id,
            'flow' => FlowResource::make($this->whenLoaded('flow')),
            'mode_id' => $this->mode_id,
            'mode' => ModeResource::make($this->whenLoaded('mode')),
            'end_audio_id' => $this->end_audio_id,
            'end_audio' => AudioResource::make($this->whenLoaded('endAudio')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
