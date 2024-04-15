<?php

namespace App\Infrastructure\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface DTO
{
    /**
     * @param Model $model
     * @param array|null $opts
     * @return DTO
     */
    public static function fromModel(Model $model, ?array $opts = null): DTO;


    /**
     * @param array<Model>|Collection<Model> $models
     * @param array $opts
     * @return array<DTO>
     */
    public static function fromModelCollection(array|Collection $models, ?array $opts = null): array;
}
