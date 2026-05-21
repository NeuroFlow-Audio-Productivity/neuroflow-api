<?php

namespace App\Services\Interfaces;

use App\Models\Flow;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IFlowService extends IService
{
    public function paginateFlowsForUser(int $userId, int $paginationAmount = 15): LengthAwarePaginator;

    public function createFlow(array $data, int $userId): Flow;

    public function updateFlow(Flow $flow, array $data): Flow;

    public function deleteFlow(Flow $flow): bool;
}
