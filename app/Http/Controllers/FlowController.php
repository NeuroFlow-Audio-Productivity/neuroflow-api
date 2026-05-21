<?php

namespace App\Http\Controllers;

use App\Http\Requests\Flow\StoreFlowRequest;
use App\Http\Requests\Flow\UpdateFlowRequest;
use App\Http\Resources\FlowResource;
use App\Models\Flow;
use App\Services\Interfaces\IFlowService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response as HttpResponse;
use Symfony\Component\HttpFoundation\Response;

class FlowController extends Controller
{
    public function __construct(
        private readonly IFlowService $flowService,
    ) {
        $this->authorizeResource(Flow::class, 'flow');
    }

    /**
     * List the authenticated user's flows.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return FlowResource::collection(
            $this->flowService->paginateFlowsForUser(
                $request->user()->id,
                $this->paginationAmount($request),
            ),
        );
    }

    /**
     * Create a flow for the authenticated user.
     */
    public function store(StoreFlowRequest $request): FlowResource
    {
        $flow = $this->flowService->createFlow(
            $request->validated(),
            $request->user()->id,
        );

        return new FlowResource($flow, Response::HTTP_CREATED);
    }

    /**
     * Show one of the authenticated user's flows.
     */
    public function show(Flow $flow): FlowResource
    {
        return new FlowResource($flow);
    }

    /**
     * Update one of the authenticated user's flows.
     */
    public function update(UpdateFlowRequest $request, Flow $flow): FlowResource
    {
        $flow = $this->flowService->updateFlow($flow, $request->validated());

        return new FlowResource($flow);
    }

    /**
     * Delete one of the authenticated user's flows.
     */
    public function destroy(Flow $flow): HttpResponse
    {
        $this->flowService->deleteFlow($flow);

        return response()->noContent();
    }
}
