<?php

namespace App\Services;

use App\Repositories\Interfaces\IRepository;
use App\Services\Interfaces\IService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class Service implements IService
{
    protected IRepository $repository;

    public function __construct(IRepository $repository)
    {
        $this->repository = $repository;
    }

    public function insert($data)
    {
        return $this->repository->insert($data);
    }

    public function edit($id, $data)
    {
        return $this->repository->edit($id, $data);
    }

    public function delete($id)
    {
        return $this->repository->delete($id);
    }

    public function listRecords(int $paginationAmount, array $filters = [], array $with = []): LengthAwarePaginator
    {
        return $this->repository->listRecords($paginationAmount, $filters, $with);
    }

    public function getAll(array $with = []): Collection
    {
        return $this->repository->getAll($with);
    }

    public function find(int $id)
    {
        return $this->repository->find($id);
    }
}
