<?php

namespace App\Http\Controllers;

use App\Http\Requests\Mode\StoreModeRequest;
use App\Http\Requests\Mode\UpdateModeRequest;
use App\Http\Resources\ModeResource;
use App\Models\Mode;
use App\Services\Interfaces\IModeService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response as HttpResponse;
use Symfony\Component\HttpFoundation\Response;

class ModeController extends Controller
{
    public function __construct(
        private readonly IModeService $modeService,
    ) {
        $this->authorizeResource(Mode::class, 'mode');
    }

    /**
     * List the available application modes.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return ModeResource::collection(
            $this->modeService->paginateModes($this->paginationAmount($request)),
        );
    }

    /**
     * List every application mode without pagination.
     */
    public function getAll(): AnonymousResourceCollection
    {
        return ModeResource::collection($this->modeService->getAllModes());
    }

    /**
     * Create a mode.
     */
    public function store(StoreModeRequest $request): ModeResource
    {
        $mode = $this->modeService->createMode($request->validated());

        return new ModeResource($mode, Response::HTTP_CREATED);
    }

    /**
     * Show an application mode.
     */
    public function show(Mode $mode): ModeResource
    {
        return new ModeResource($mode);
    }

    /**
     * Update a mode.
     */
    public function update(UpdateModeRequest $request, Mode $mode): ModeResource
    {
        $mode = $this->modeService->updateMode($mode, $request->validated());

        return new ModeResource($mode);
    }

    /**
     * Delete a mode.
     */
    public function destroy(Mode $mode): HttpResponse
    {
        $this->modeService->deleteMode($mode);

        return response()->noContent();
    }
}
