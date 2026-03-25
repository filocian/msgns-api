<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Persistence;

use Src\Products\Domain\Entities\ProductBusiness;
use Src\Products\Domain\Ports\ProductBusinessPort;

final class EloquentProductBusinessRepository implements ProductBusinessPort
{
    public function findByProductId(int $productId): ?ProductBusiness
    {
        $model = EloquentProductBusiness::where('product_id', $productId)->first();

        return $model instanceof EloquentProductBusiness ? $model->toDomainEntity() : null;
    }

    public function save(ProductBusiness $business): ProductBusiness
    {
        if ($business->id === 0) {
            // Create new
            $model = EloquentProductBusiness::create([
                'product_id' => $business->productId,
                'user_id' => $business->userId,
                'not_a_business' => $business->notABusiness,
                'name' => $business->name,
                'types' => json_encode($business->types),
                'place_types' => $business->placeTypes !== null ? json_encode($business->placeTypes) : null,
                'size' => $business->size,
            ]);

            return $model->toDomainEntity();
        }

        // Update existing
        $model = EloquentProductBusiness::findOrFail($business->id);
        $model->forceFill([
            'product_id' => $business->productId,
            'user_id' => $business->userId,
            'not_a_business' => $business->notABusiness,
            'name' => $business->name,
            'types' => json_encode($business->types),
            'place_types' => $business->placeTypes !== null ? json_encode($business->placeTypes) : null,
            'size' => $business->size,
        ])->save();
        $model->refresh();

        return $model->toDomainEntity();
    }
}
