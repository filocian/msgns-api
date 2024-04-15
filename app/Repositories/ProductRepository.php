<?php

namespace App\Repositories;

use App\DTO\ProductDto;
use App\Exceptions\Product\ProductNotFoundException;
use App\Infrastructure\Contracts\RepositoryContract;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductRepository implements RepositoryContract
{

    public function getAll()
    {
        return Product::all();
    }

    public function query(callable $customQuery)
    {
        return $customQuery(Product::query());
    }

    public function findOne(int $id, ?array $opts = null): ProductDto
    {
        return ProductDto::fromModel(Product::findById($id));
    }

    /**
     * Find a product by a given attributes (not configs)
     * @param array $attributes
     * @param array|null $opts
     * @return ProductDto
     */
    public function findOneBy(array $attributes, ?array $opts = null): ProductDto
    {
        $found = Product::query()->where($attributes)->first();
        return ProductDto::fromModel($found, $opts);
    }

    /**
     * Retrieve a product with given id and configuration json pair: key -> value.
     *
     * @param array{id: int, configKey: string, configValue: string} $data
     * @param array|null $opts
     * @return ProductDto
     */
    public function findByConfigPair(array $data, ?array $opts = null): ProductDto
    {
        $productId = $data['id'];
        $configKey = $data['configKey'];
        $configValue = $data['configValue'];

        return ProductDto::fromModel(
            Product::findByConfigPair($productId, $configKey, $configValue)
        );
    }

    public function create(array $data, ?array $opts = null): ProductDto
    {
        return ProductDto::fromModel(
            Product::query()
                ->create($data)
                ->firstOrFail(),
            $opts
        );
    }

    /**
     * Retrieve a product with given id and configuration json pair: key -> value.
     *
     * @param int $id
     * @param array $data
     * @param array|null $opts
     * @return ProductDto
     */
    public function update(int $id, array $data, ?array $opts = null): ProductDto
    {
        Product::query()
            ->where('id', $id)
            ->update($data);

        return $this->findOne($id, $opts);

    }

    public function delete($id)
    {
        return Product::query()
            ->where('id', $id)
            ->delete();
    }
}
