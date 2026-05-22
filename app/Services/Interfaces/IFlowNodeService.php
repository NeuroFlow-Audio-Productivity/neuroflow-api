<?php

namespace App\Services\Interfaces;

use App\Models\FlowNode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IFlowNodeService extends IService
{
    public function paginateFlowNodesForFlow(int $flowId, int $paginationAmount = 15): LengthAwarePaginator;

    public function createFlowNode(array $data): FlowNode;

    public function updateFlowNode(FlowNode $flowNode, array $data): FlowNode;

    public function deleteFlowNode(FlowNode $flowNode): bool;
}
