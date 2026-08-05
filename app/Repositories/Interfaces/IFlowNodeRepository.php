<?php

namespace App\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IFlowNodeRepository extends IRepository
{
    public function paginateForFlow(int $flowId, int $paginationAmount): LengthAwarePaginator;
}
