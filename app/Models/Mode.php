<?php

namespace App\Models;

use Database\Factories\ModeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'description', 'color', 'is_system'])]
class Mode extends Model
{
    /** @use HasFactory<ModeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    /**
     * @return array<int, array{name: string, description: string, color: string, is_system: bool}>
     */
    public static function defaults(): array
    {
        return [
            [
                'name' => 'Focus',
                'description' => 'Beta waves: discreet pulses for distraction-free work blocks.',
                'color' => '#6ee7d8',
                'is_system' => false,
            ],
            [
                'name' => 'Relax',
                'description' => 'Theta waves: textured ambience to slow down mental noise.',
                'color' => '#f6c177',
                'is_system' => false,
            ],
            [
                'name' => 'Sleep',
                'description' => 'Delta waves: automatic fade-out for falling asleep.',
                'color' => '#b9a7ff',
                'is_system' => false,
            ],
            [
                'name' => 'Session Alarm',
                'description' => 'Controls end-of-session alarm sounds for Pomodoro and timer completion events.',
                'color' => '#F97316',
                'is_system' => true,
            ],
        ];
    }
}
