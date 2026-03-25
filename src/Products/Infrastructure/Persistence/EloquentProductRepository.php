<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Persistence;

use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Products\Domain\ValueObjects\ProductDescription;
use Src\Products\Domain\ValueObjects\ProductModel;
use Src\Products\Domain\ValueObjects\ProductName;
use Src\Products\Domain\ValueObjects\ProductPassword;
use Src\Products\Domain\ValueObjects\TargetUrl;

final class EloquentProductRepository implements ProductRepositoryPort
{
    public function findById(int $id): ?Product
    {
        $model = EloquentProduct::find($id);

        return $model instanceof EloquentProduct ? $model->toDomainEntity() : null;
    }

    public function findByIdWithTrashed(int $id): ?Product
    {
        $model = EloquentProduct::withTrashed()->find($id);

        return $model instanceof EloquentProduct ? $model->toDomainEntity() : null;
    }

    public function save(Product $product): Product
    {
        if ($product->id === 0) {
            // Create new product
            $model = EloquentProduct::create([
                'product_type_id' => $product->productTypeId,
                'user_id' => $product->userId,
                'model' => $product->model->value,
                'linked_to_product_id' => $product->linkedToProductId,
                'password' => $product->password->value,
                'target_url' => $product->targetUrl,
                'usage' => $product->usage,
                'name' => $product->name->value,
                'description' => $product->description?->value,
                'active' => $product->active,
                'configuration_status' => $product->configurationStatus->value,
                'assigned_at' => $product->assignedAt,
                'size' => $product->size,
            ]);

            // Generate default name if empty
            if ($product->name->value === '') {
                $product->name = ProductName::from(sprintf('%s (%d)', $product->model->value, $model->id));
                $model->name = $product->name->value;
                $model->save();
            }

            return $model->toDomainEntity();
        }

        // Update existing product
        $model = EloquentProduct::findOrFail($product->id);
        $model->forceFill([
            'product_type_id' => $product->productTypeId,
            'user_id' => $product->userId,
            'model' => $product->model->value,
            'linked_to_product_id' => $product->linkedToProductId,
            'password' => $product->password->value,
            'target_url' => $product->targetUrl,
            'usage' => $product->usage,
            'name' => $product->name->value,
            'description' => $product->description?->value,
            'active' => $product->active,
            'configuration_status' => $product->configurationStatus->value,
            'assigned_at' => $product->assignedAt,
            'size' => $product->size,
        ])->save();
        $model->refresh();

        return $model->toDomainEntity();
    }

    public function delete(int $id): void
    {
        EloquentProduct::destroy($id);
    }

    public function restore(int $id): void
    {
        EloquentProduct::withTrashed()->findOrFail($id)->restore();
    }

    /**
     * @param array<Product> $products
     */
    public function bulkInsert(array $products): void
    {
        $data = array_map(function (Product $product) {
            return [
                'product_type_id' => $product->productTypeId,
                'user_id' => $product->userId,
                'model' => $product->model->value,
                'linked_to_product_id' => $product->linkedToProductId,
                'password' => $product->password->value,
                'target_url' => $product->targetUrl,
                'usage' => $product->usage,
                'name' => $product->name->value,
                'description' => $product->description?->value,
                'active' => $product->active,
                'configuration_status' => $product->configurationStatus->value,
                'assigned_at' => $product->assignedAt?->format('Y-m-d H:i:s'),
                'size' => $product->size,
                'created_at' => $product->createdAt->format('Y-m-d H:i:s'),
                'updated_at' => $product->updatedAt->format('Y-m-d H:i:s'),
            ];
        }, $products);

        EloquentProduct::insert($data);
    }
}
