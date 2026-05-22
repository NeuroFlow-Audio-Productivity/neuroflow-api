<?php

namespace App\Repositories;

use App\Models\FlowNode;
use App\Repositories\Interfaces\IFlowNodeRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FlowNodeRepository extends Repository implements IFlowNodeRepository
{
    public function model(): string
    {
        return FlowNode::class;
    }

    public function paginateForFlow(int $flowId, int $paginationAmount): LengthAwarePaginator
    {
        return FlowNode::query()
            ->with(['flow', 'mode', 'endAudio.mode'])
            ->where('flow_id', $flowId)
            ->orderBy('order')
            ->paginate($paginationAmount);
    }
}
