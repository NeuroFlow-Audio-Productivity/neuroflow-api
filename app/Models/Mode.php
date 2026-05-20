<?php

namespace App\Models;

use Database\Factories\ModeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'description', 'color'])]
class Mode extends Model
{
    /** @use HasFactory<ModeFactory> */
    use HasFactory;

    /**
     * @return array<int, array{name: string, description: string, color: string}>
     */
    public static function defaults(): array
    {
        return [
            [
                'name' => 'Sleep',
                'description' => 'Beta waves: discreet pulses for distraction-free work blocks.',
                'color' => '#6ee7d8',
            ],
            [
                'name' => 'Relax',
                'description' => 'Theta waves: textured ambience to slow down mental noise.',
                'color' => '#f6c177',
            ],
            [
                'name' => 'Sleep',
                'description' => 'Delta waves: automatic fade-out for falling asleep.',
                'color' => '#b9a7ff',
            ],
            [
                'name' => 'Session Alarm',
                'description' => 'Controls end-of-session alarm sounds for Pomodoro and timer completion events.',
                'color' => '#F97316',
            ],
        ];
    }
}
