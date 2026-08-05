<?php

namespace App\Models;

use Database\Factories\FlowFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'user_id'])]
class Flow extends Model
{
    /** @use HasFactory<FlowFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<FlowNode, $this>
     */
    public function nodes(): HasMany
    {
        return $this->hasMany(FlowNode::class);
    }
}
