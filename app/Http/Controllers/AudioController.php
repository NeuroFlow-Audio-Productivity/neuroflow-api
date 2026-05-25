<?php

namespace App\Http\Controllers;

use App\Http\Requests\Audio\StoreAudioRequest;
use App\Http\Requests\Audio\UpdateAudioRequest;
use App\Http\Resources\AudioResource;
use App\Models\Audio;
use App\Models\Mode;
use App\Services\Interfaces\IAudioService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AudioController extends Controller
{
    public function __construct(
        private readonly IAudioService $audioService,
    ) {
        $this->authorizeResource(Audio::class, 'audio');
    }

    /**
     * List the available audios.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return AudioResource::collection(
            $this->audioService->listRecords(paginationAmount: $this->paginationAmount($request), with: ['mode']),
        );
    }

    /**
     * List every available audio without pagination.
     */
    public function getAll(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Audio::class);

        return AudioResource::collection($this->audioService->getAll(with: ['mode']));
    }

    /**
     * List the available audios for a mode.
     */
    public function byMode(Request $request, Mode $mode): AnonymousResourceCollection
    {
        return AudioResource::collection(
            $this->audioService->listRecords(paginationAmount: $this->paginationAmount($request), filters: ['mode_id' => $mode->id]),
        );
    }

    /**
     * Create an audio.
     */
    public function store(StoreAudioRequest $request): AudioResource
    {
        $audio = $this->audioService->createAudio(
            $request->validated(),
            $request->file('file'),
        );

        return new AudioResource($audio, Response::HTTP_CREATED);
    }

    /**
     * Show an audio.
     */
    public function show(Audio $audio): AudioResource
    {
        $audio->loadMissing('mode');

        return new AudioResource($audio);
    }

    /**
     * Stream an audio file.
     */
    public function stream(Request $request, Audio $audio): StreamedResponse|HttpResponse
    {
        return $this->audioService->stream($audio, $request->header('Range'));
    }

    /**
     * Update an audio.
     */
    public function update(UpdateAudioRequest $request, Audio $audio): AudioResource
    {
        $audio = $this->audioService->updateAudio(
            $audio,
            $request->validated(),
            $request->file('file'),
        );

        return new AudioResource($audio);
    }

    /**
     * Delete an audio.
     */
    public function destroy(Audio $audio): HttpResponse
    {
        $this->audioService->deleteAudio($audio);

        return response()->noContent();
    }

}
