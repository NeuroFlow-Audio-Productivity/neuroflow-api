<?php

namespace App\Services;

use App\Models\Mode;
use App\Repositories\Interfaces\IModeRepository;
use App\Services\Interfaces\IModeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

class ModeService extends Service implements IModeService
{
    public function __construct(
        private readonly IModeRepository $modeRepository,
    ) {
        parent::__construct($modeRepository);
    }

    public function getAllModes(): Collection
    {
        return $this->modeRepository->getAll();
    }

    public function paginateModes(int $paginationAmount = 15): LengthAwarePaginator
    {
        return $this->modeRepository->paginate($paginationAmount);
    }

    public function createMode(array $data): Mode
    {
        return $this->modeRepository->create(Arr::only($data, [
            'name',
            'description',
            'color',
            'is_system',
        ]));
    }

    public function updateMode(Mode $mode, array $data): Mode
    {
        $this->edit($mode->id, Arr::only($data, [
            'name',
            'description',
            'color',
            'is_system',
        ]));

        $mode->refresh();

        return $mode;
    }

    public function deleteMode(Mode $mode): bool
    {
        return $this->delete($mode->id);
    }
}
