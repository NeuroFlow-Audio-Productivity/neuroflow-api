<?php

namespace App\Services;

use App\Models\Mode;
use App\Repositories\Interfaces\IModeRepository;
use App\Services\Interfaces\IModeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

class ModeService extends Service implements IModeService
{
    public function __construct(
        private readonly IModeRepository $modeRepository,
    ) {
        parent::__construct($modeRepository);
    }
}
