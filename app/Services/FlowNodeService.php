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

    public function createFlowNode(array $data): FlowNode
    {
        $flowNode = $this->flowNodeRepository->create(Arr::only($data, [
            'title',
            'time',
            'order',
            'flow_id',
            'mode_id',
            'end_audio_id',
        ]));

        $flowNode->loadMissing(['flow', 'mode', 'endAudio.mode']);

        return $flowNode;
    }

    public function updateFlowNode(FlowNode $flowNode, array $data): FlowNode
    {
        $this->edit($flowNode->id, Arr::only($data, [
            'title',
            'time',
            'order',
            'flow_id',
            'mode_id',
            'end_audio_id',
        ]));

        $flowNode->refresh();
        $flowNode->loadMissing(['flow', 'mode', 'endAudio.mode']);

        return $flowNode;
    }

    public function deleteFlowNode(FlowNode $flowNode): bool
    {
        return $this->delete($flowNode->id);
    }
}
