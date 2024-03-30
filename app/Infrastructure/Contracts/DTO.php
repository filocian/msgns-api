<?php

namespace App\Infrastructure\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface DTO
{
    /**
     * @param Model $models
     * @param array $opts
     * @return DTO
     */
    public static function fromModel($model, ?array $opts = null): DTO;


    /**
     * @param array<Model>|Collection<Model> $models
     * @param array $opts
     * @return array<DTO>
     */
    public static function fromModelCollection(array|Collection $models, ?array $opts = null): array;
}
