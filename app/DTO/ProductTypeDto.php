<?php

namespace App\DTO;

use App\Infrastructure\Contracts\DTO;
use App\Models\ProductType;
use Illuminate\Support\Collection;

class ProductTypeDto implements DTO
{
    public int $id;
    public string $code;
    public string $name;
    public string $description;
    public array $config_template;

    /**
     * @param array $data (id:int, name:string)
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->code = $data['code'];
        $this->name = $data['name'];
        $this->description = $data['description'];
        $this->config_template = $data['config_template'];
    }

    /**
     * @param ProductType $model ;
     */
    public static function fromModel($model, ?array $opts = null): ProductTypeDto
    {
        return new ProductTypeDto([
            'id' => $model->id,
            'code' => $model->code,
            'name' => $model->name,
            'description' => $model->description,
            'config_template' => $model->config_template
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
            return self::fromModel($model);
        }, $data);
    }


}
