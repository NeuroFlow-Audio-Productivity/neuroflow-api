<?php

namespace Database\Factories;

use App\Models\Mode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Mode>
 */
class ModeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => Str::headline($name),
            'description' => fake()->sentence(),
            'color' => fake()->hexColor(),
            'is_system' => false,
        ];
    }
}
