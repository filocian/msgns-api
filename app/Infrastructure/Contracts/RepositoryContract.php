<?php

namespace App\Infrastructure\Contracts;

interface RepositoryContract
{
    public function getAll();

    public function findOne(int $id, ?array $opts = null);

    public function create(array $data, ?array $opts = null);

    public function update(int $id, array $data, ?array $opts = null);

    public function delete(string $id);
}
