<?php

namespace App\DTO;

use App\Infrastructure\Contracts\DTO;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ProductDto implements DTO
{
    public int $id;
    public array $config;
    public ProductTypeDto|null $type;
    public UserDto|null $user = null;
    public bool $active;

    /**
     * @param array $data (id:int, user_id:int, qr_url:string, target_url:string, user: UserDto, type: ProductTypeDto)
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->config = $data['config'];
        $this->type = $data['type'] ?? null;
        $this->user = $data['user'] ?? null;
        $this->active = $data['active'];
    }

    /**
     * @param Model $model ;
     * @param array|null $opts
     * @return ProductDto
     */
    public static function fromModel(Model $model, ?array $opts = null): ProductDto
    {
        $user = $model->user ? UserDto::fromModel($model->user) : null;
        $type = ProductTypeDto::fromModel($model->productType);

        $data = [
            'id' => $model->id,
            'config' => $model->config,
            'type' => $type,
            'user' => $user,
            'active' => $model->active,
        ];

        return new ProductDto($data);
    }


    /**
     * @param array<Product>|Collection $models
     * @return array<ProductDto>
     */
    public static function fromModelCollection(array|Collection $models, ?array $opts = null): array
    {
        $data = $models instanceof Collection ? $models->all() : $models;

        return array_map(function ($model) use ($opts) {
            return ProductDto::fromModel($model, $opts ?? null);
        }, $data);
    }


}
