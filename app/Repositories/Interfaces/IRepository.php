<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface IRepository
{
    public function create($data);
    public function listRecords(int $paginationAmount, array $filters = [], array $with = []): LengthAwarePaginator;
    public function insert($data);
    public function edit($id, $data);
    public function delete($id);
    public function getAll(array $with = []): Collection;
    public function find(int $id);
}
