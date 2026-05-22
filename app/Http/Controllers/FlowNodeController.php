<?php

namespace App\Http\Controllers;

use App\Http\Requests\FlowNode\IndexFlowNodeRequest;
use App\Http\Requests\FlowNode\StoreFlowNodeRequest;
use App\Http\Requests\FlowNode\UpdateFlowNodeRequest;
use App\Http\Resources\FlowNodeResource;
use App\Models\FlowNode;
use App\Services\Interfaces\IFlowNodeService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response as HttpResponse;
use Symfony\Component\HttpFoundation\Response;

class FlowNodeController extends Controller
{
    public function __construct(
        private readonly IFlowNodeService $flowNodeService,
    ) {
        $this->authorizeResource(FlowNode::class, 'flow_node');
    }

    /**
     * List the nodes from one of the authenticated user's flows.
     */
    public function index(IndexFlowNodeRequest $request): AnonymousResourceCollection
    {
        return FlowNodeResource::collection(
            $this->flowNodeService->paginateFlowNodesForFlow(
                (int) $request->validated('flow_id'),
                $this->paginationAmount($request),
            ),
        );
    }

    /**
     * Create a node inside one of the authenticated user's flows.
     */
    public function store(StoreFlowNodeRequest $request): FlowNodeResource
    {
        $flowNode = $this->flowNodeService->createFlowNode($request->validated());

        return new FlowNodeResource($flowNode, Response::HTTP_CREATED);
    }

    /**
     * Show a node from one of the authenticated user's flows.
     */
    public function show(FlowNode $flowNode): FlowNodeResource
    {
        $flowNode->loadMissing(['flow', 'mode', 'endAudio.mode']);

        return new FlowNodeResource($flowNode);
    }

    /**
     * Update a node from one of the authenticated user's flows.
     */
    public function update(UpdateFlowNodeRequest $request, FlowNode $flowNode): FlowNodeResource
    {
        $flowNode = $this->flowNodeService->updateFlowNode($flowNode, $request->validated());

        return new FlowNodeResource($flowNode);
    }

    /**
     * Delete a node from one of the authenticated user's flows.
     */
    public function destroy(FlowNode $flowNode): HttpResponse
    {
        $this->flowNodeService->deleteFlowNode($flowNode);

        return response()->noContent();
    }
}
