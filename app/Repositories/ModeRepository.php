<?php

namespace App\Repositories;

use App\Models\Mode;
use App\Repositories\Interfaces\IModeRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ModeRepository extends Repository implements IModeRepository
{
    public function model(): string
    {
        return Mode::class;
    }
}
