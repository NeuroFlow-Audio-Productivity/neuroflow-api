<?php

namespace App\Repositories;

use App\Repositories\Interfaces\IRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class Repository implements IRepository
{
    protected $model;

    public function __construct()
    {
        $this->model = app($this->model());
    }

    public function create($data)
    {
        return $this->model->create($data);
    }

    public function listRecords(int $paginationAmount, array $filters = [], array $with = []): LengthAwarePaginator
    {
        $query = $this->model->query();

        if (!empty($with)) {
            $query->with($with);
        }

        foreach ($filters as $name => $value) {
            if ($value !== null && $name !== 'pagination_amount') {
                $query->where($name, $value);
            }
        }

        return $query->paginate($paginationAmount);
    }

    public function insert($data)
    {
        return $this->create($data);
    }

    public function edit($id, $data)
    {
        $model = $this->model->find($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete($id)
    {
        $model = $this->model->find($id);

        return $model->delete();
    }

    public function getAll(array $with = []): Collection
    {
        $query = $this->model->query();

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->orderBy('id')->get();
    }

    public function find(int $id)
    {
        return $this->model->find($id);
    }

    abstract public function model();
}
