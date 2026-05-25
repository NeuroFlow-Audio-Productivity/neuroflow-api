<?php

namespace App\Services;

use App\Models\Audio;
use App\Repositories\Interfaces\IAudioRepository;
use App\Services\Interfaces\IAudioService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;
use Illuminate\Http\Response as HttpResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AudioService extends Service implements IAudioService
{
    private const DIRECTORY = 'audios';

    public function __construct(
        private readonly IAudioRepository $audioRepository,
    ) {
        parent::__construct($audioRepository);
    }

    public function stream(Audio $audio, ?string $rangeHeader): StreamedResponse|HttpResponse
    {
        abort_unless(Storage::exists($audio->path), Response::HTTP_NOT_FOUND);

        $size = Storage::size($audio->path);
        $start = 0;
        $end = $size - 1;
        $status = Response::HTTP_OK;
        $headers = [
            'Accept-Ranges'       => 'bytes',
            'Content-Type'        => Storage::mimeType($audio->path) ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . basename($audio->path) . '"',
        ];

        if ($rangeHeader !== null) {
            $range = $this->parseRange($rangeHeader, $size);

            if ($range === null) {
                return response('', Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE, [
                    ...$headers,
                    'Content-Range' => "bytes */{$size}",
                ]);
            }

            [$start, $end] = $range;
            $status = Response::HTTP_PARTIAL_CONTENT;
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";
        }

        $length = $end - $start + 1;
        $headers['Content-Length'] = (string) $length;

        return response()->stream(function () use ($audio, $start, $length): void {
            $stream = Storage::readStream($audio->path);

            if ($stream === false) {
                return;
            }

            fseek($stream, $start);

            $remaining = $length;

            while ($remaining > 0 && !feof($stream)) {
                $chunk = fread($stream, min(8192, $remaining));

                if ($chunk === false) {
                    break;
                }

                echo $chunk;
                $remaining -= strlen($chunk);
                flush();
            }

            fclose($stream);
        }, $status, $headers);
    }

    public function createAudio(array $data, UploadedFile $file): Audio
    {
        $path = $this->storeFile($file);

        try {
            $audio = DB::transaction(fn (): Audio => $this->audioRepository->create([
                ...Arr::only($data, ['name', 'mode_id']),
                'path' => $path,
            ]));
        } catch (Throwable $throwable) {
            Storage::delete($path);

            throw $throwable;
        }

        $audio->loadMissing('mode');

        return $audio;
    }

    public function updateAudio(Audio $audio, array $data, ?UploadedFile $file = null): Audio
    {
        $payload = Arr::only($data, ['name', 'mode_id']);
        $newPath = null;
        $oldPath = $audio->path;

        if ($file !== null) {
            $newPath = $this->storeFile($file);
            $payload['path'] = $newPath;
        }

        try {
            DB::transaction(fn () => $this->edit($audio->id, $payload));
        } catch (Throwable $throwable) {
            if ($newPath !== null) {
                Storage::delete($newPath);
            }

            throw $throwable;
        }

        if ($newPath !== null && $newPath !== $oldPath) {
            Storage::delete($oldPath);
        }

        $audio->refresh();
        $audio->loadMissing('mode');

        return $audio;
    }

    public function deleteAudio(Audio $audio): bool
    {
        $path = $audio->path;

        $deleted = DB::transaction(fn (): bool => $this->delete($audio->id));

        if ($deleted) {
            Storage::delete($path);
        }

        return $deleted;
    }

    private function storeFile(UploadedFile $file): string
    {
        $path = $file->store(self::DIRECTORY);

        if (! is_string($path)) {
            throw new RuntimeException('The audio file could not be stored.');
        }

        return $path;
    }

    /**
     * @return array{0: int, 1: int}|null
     */
    private function parseRange(string $range, int $size): ?array
    {
        if (! preg_match('/^bytes=(\d*)-(\d*)$/', $range, $matches)) {
            return null;
        }

        if ($matches[1] === '' && $matches[2] === '') {
            return null;
        }

        if ($matches[1] === '') {
            $suffixLength = (int) $matches[2];

            if ($suffixLength <= 0) {
                return null;
            }

            return [max(0, $size - $suffixLength), $size - 1];
        }

        $start = (int) $matches[1];
        $end = $matches[2] === '' ? $size - 1 : (int) $matches[2];

        if ($start > $end || $start >= $size) {
            return null;
        }

        return [$start, min($end, $size - 1)];
    }
}
