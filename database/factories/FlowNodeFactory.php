<?php

namespace Database\Factories;

use App\Models\Audio;
use App\Models\Flow;
use App\Models\FlowNode;
use App\Models\Mode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FlowNode>
 */
class FlowNodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'time' => fake()->numberBetween(1, 120),
            'order' => fake()->numberBetween(1, 20),
            'flow_id' => Flow::factory(),
            'mode_id' => Mode::factory(),
            'end_audio_id' => Audio::factory(),
        ];
    }
}
