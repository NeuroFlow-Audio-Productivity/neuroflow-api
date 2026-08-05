<?php

namespace App\Services;

use App\Models\Flow;
use App\Repositories\Interfaces\IFlowRepository;
use App\Services\Interfaces\IFlowService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class FlowService extends Service implements IFlowService
{
    public function __construct(
        private readonly IFlowRepository $flowRepository,
    ) {
        parent::__construct($flowRepository);
    }

    public function paginateFlowsForUser(int $userId, int $paginationAmount = 15): LengthAwarePaginator
    {
        return $this->flowRepository->paginateForUser($userId, $paginationAmount);
    }
}
