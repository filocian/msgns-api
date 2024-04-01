<?php

namespace App\DTO;

use App\Infrastructure\Contracts\DTO;
use App\Models\ProductType;
use Illuminate\Support\Collection;

class ProductTypeDto implements DTO
{
    public int $id;
    public string $name;

    /**
     * @param array $data (id:int, name:string)
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->name = $data['name'];
    }

    /**
     * @param ProductType $model ;
     */
    public static function fromModel($model, ?array $opts = null): DTO
    {
        return new ProductTypeDto([
            'id' => $model->id,
            'name' => $model->name,
        ]);
    }


    /**
     * @param array<ProductType>|Collection $models
     * @return array<ProductTypeDto>
     */
    public static function fromModelCollection(array|Collection $models, ?array $opts = null): array
    {
        $data = $models instanceof Collection ? $models->all() : $models;

        return array_map(function ($model) {
            return ProductTypeDto::fromModel($model);
        }, $data);
    }


}
