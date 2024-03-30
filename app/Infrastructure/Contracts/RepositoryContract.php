<?php

namespace App\Infrastructure\Contracts;

interface RepositoryContract
{
    public function getAll();

    public function findOne(string $id, ?array $opts = null);

    public function create(array $data, ?array $opts = null);

    public function update(string $id, array $data, ?array $opts = null);

    public function delete(string $id);
}
