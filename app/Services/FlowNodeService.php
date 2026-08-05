<?php

namespace App\Services;

use App\Models\FlowNode;
use App\Repositories\Interfaces\IFlowNodeRepository;
use App\Services\Interfaces\IFlowNodeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class FlowNodeService extends Service implements IFlowNodeService
{
    public function __construct(
        private readonly IFlowNodeRepository $flowNodeRepository,
    ) {
        parent::__construct($flowNodeRepository);
    }

    public function paginateFlowNodesForFlow(int $flowId, int $paginationAmount = 15): LengthAwarePaginator
    {
        return $this->flowNodeRepository->paginateForFlow($flowId, $paginationAmount);
    }
}
