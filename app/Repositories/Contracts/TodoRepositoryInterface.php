<?php

namespace App\Repositories\Contracts;

interface TodoRepositoryInterface
{
    public function getAll();
    public function getPaginated($perPage);
    public function getFiltered(array $filters);
    public function getChartData($type);
    public function findById($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
