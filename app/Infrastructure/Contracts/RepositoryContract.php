<?php

namespace App\Infrastructure\Contracts;

interface RepositoryContract
{
    public function getAll();

    public function findOne($id);

    public function create($data);

    public function update($id, $data);

    public function delete($id);
}
