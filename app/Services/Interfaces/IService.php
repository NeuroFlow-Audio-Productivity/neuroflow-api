<?php

namespace App\Services\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface IService
{
    public function insert($data);
    public function edit($id, $data);
    public function delete($id);
    public function listRecords(int $paginationAmount, array $filters = [], array $with = []): LengthAwarePaginator;
    public function getAll(array $with = []): Collection;
    public function find(int $id);
}
