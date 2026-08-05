<?php

namespace App\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IFlowRepository extends IRepository
{
    public function paginateForUser(int $userId, int $paginationAmount): LengthAwarePaginator;
}
