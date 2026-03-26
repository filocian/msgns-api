<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Persistence;

use Src\Products\Domain\Entities\ProductType;
use Src\Products\Domain\Ports\ProductTypeRepository;
use Src\Shared\Core\Bus\PaginatedResult;

final class EloquentProductTypeRepository implements ProductTypeRepository
{
    public function findById(int $id): ?ProductType
    {
        $model = ProductTypeModel::find($id);

        return $model instanceof ProductTypeModel ? $model->toDomainEntity() : null;
    }

    public function save(ProductType $productType): ProductType
    {
        if ($productType->id === 0) {
            $model = ProductTypeModel::create([
                'code'            => $productType->code->value,
                'name'            => $productType->name,
                'image_ref'       => $productType->code->value,
                'primary_model'   => $productType->models->primary,
                'secondary_model' => $productType->models->secondary,
            ]);

            return $model->toDomainEntity();
        }

        $model = ProductTypeModel::findOrFail($productType->id);
        $model->forceFill([
            'code'            => $productType->code->value,
            'name'            => $productType->name,
            'primary_model'   => $productType->models->primary,
            'secondary_model' => $productType->models->secondary,
        ])->save();
        $model->refresh();

        return $model->toDomainEntity();
    }

    /**
     * @param list<int> $ids
     * @return list<ProductType>
     */
    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $models = ProductTypeModel::query()
            ->whereIn('id', $ids)
            ->get();

        return array_values(array_map(
            static fn (ProductTypeModel $model): ProductType => $model->toDomainEntity(),
            $models->all(),
        ));
    }

    /**
     * @param array{page?: int, perPage?: int, sortBy?: string, sortDir?: string} $params
     */
    public function list(array $params): PaginatedResult
    {
        $page    = $params['page'] ?? 1;
        $perPage = $params['perPage'] ?? 15;
        $sortBy  = $params['sortBy'] ?? 'name';
        $sortDir = $params['sortDir'] ?? 'asc';

        $paginated = ProductTypeModel::query()
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage, ['*'], 'page', $page);

        $items = array_map(
            static fn (ProductTypeModel $model): ProductType => $model->toDomainEntity(),
            $paginated->items(),
        );

        return new PaginatedResult(
            items: $items,
            currentPage: $paginated->currentPage(),
            perPage: $paginated->perPage(),
            total: $paginated->total(),
            lastPage: $paginated->lastPage(),
        );
    }
}
