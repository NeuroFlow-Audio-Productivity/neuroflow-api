<?php

namespace Database\Seeders;

use App\Models\Mode;
use Illuminate\Database\Seeder;

class ModeSeeder extends Seeder
{
    /**
     * Seed the application's modes.
     */
    public function run(): void
    {
        Mode::query()
            ->where('name', 'Sleep')
            ->where('color', '#6ee7d8')
            ->update(['name' => 'Focus']);

        foreach (Mode::defaults() as $mode) {
            Mode::query()->updateOrCreate(
                [
                    'name' => $mode['name'],
                    'color' => $mode['color'],
                ],
                ['description' => $mode['description'], 'is_system' => $mode['is_system']],
            );
        }
    }
}
