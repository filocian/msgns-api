<?php

namespace App\Infrastructure\Contracts;

use Illuminate\Database\Eloquent\Model;

interface DTO
{
    /**
     * @param Model $model
     * @return DTO
     */
    public static function fromModel($model): DTO;
}
