<?php

namespace App\Services\Interfaces;

use App\Models\Flow;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IFlowService extends IService
{
    public function paginateFlowsForUser(int $userId, int $paginationAmount = 15): LengthAwarePaginator;
}
