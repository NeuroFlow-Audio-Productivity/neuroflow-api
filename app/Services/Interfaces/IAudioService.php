<?php

namespace App\Services\Interfaces;

use App\Models\Audio;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Response as HttpResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
interface IAudioService extends IService
{
    public function stream(Audio $audio, ?string $rangeHeader): StreamedResponse|HttpResponse;
    public function createAudio(array $data, UploadedFile $file): Audio;
    public function updateAudio(Audio $audio, array $data, ?UploadedFile $file = null): Audio;
    public function deleteAudio(Audio $audio): bool;
}
