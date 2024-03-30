<?php

namespace App\Infrastructure\Contracts;

interface UseCaseContract
{
    public function run(?array $data = null, ?array $opts = null);
}
