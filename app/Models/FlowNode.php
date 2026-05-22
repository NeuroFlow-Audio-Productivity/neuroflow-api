<?php

namespace App\Models;

use Database\Factories\FlowNodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['time', 'order', 'flow_id', 'mode_id', 'end_audio_id'])]
class FlowNode extends Model
{
    /** @use HasFactory<FlowNodeFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Flow, $this>
     */
    public function flow(): BelongsTo
    {
        return $this->belongsTo(Flow::class);
    }

    /**
     * @return BelongsTo<Mode, $this>
     */
    public function mode(): BelongsTo
    {
        return $this->belongsTo(Mode::class);
    }

    /**
     * @return BelongsTo<Audio, $this>
     */
    public function endAudio(): BelongsTo
    {
        return $this->belongsTo(Audio::class, 'end_audio_id');
    }
}
