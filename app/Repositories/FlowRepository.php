<?php

namespace App\Repositories;

use App\Models\Flow;
use App\Repositories\Interfaces\IFlowRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FlowRepository extends Repository implements IFlowRepository
{
    public function model(): string
    {
        return Flow::class;
    }

    public function paginateForUser(int $userId, int $paginationAmount): LengthAwarePaginator
    {
        return Flow::query()
            ->where('user_id', $userId)
            ->orderBy('id')
            ->paginate($paginationAmount);
    }
}
