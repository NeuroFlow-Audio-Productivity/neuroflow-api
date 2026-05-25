<?php

namespace App\Repositories;

use App\Models\Audio;
use App\Repositories\Interfaces\IAudioRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AudioRepository extends Repository implements IAudioRepository
{
    public function model(): string
    {
        return Audio::class;
    }
}
