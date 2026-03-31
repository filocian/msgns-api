<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Src\Products\Application\Resources\ProductListItemResource;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Products\Domain\ValueObjects\ProductDescription;
use Src\Products\Domain\ValueObjects\ProductModel;
use Src\Products\Domain\ValueObjects\ProductName;
use Src\Products\Domain\ValueObjects\ProductPassword;
use Src\Products\Domain\ValueObjects\TargetUrl;
use Src\Shared\Core\Bus\DomainEvent;
use Src\Shared\Core\Bus\EventBus;
use Src\Shared\Core\Bus\PaginatedResult;

final class EloquentProductRepository implements ProductRepositoryPort
{
    public function __construct(
        private readonly EventBus $eventBus,
    ) {}

    public function findById(int $id): ?Product
    {
        $model = EloquentProduct::find($id);

        return $model instanceof EloquentProduct ? $model->toDomainEntity() : null;
    }

    public function findByIdAndPassword(int $id, string $password): ?Product
    {
        $model = EloquentProduct::where('id', $id)
            ->where('password', $password)
            ->first();

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

            $this->publishReleasedEvents($product);

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

        $this->publishReleasedEvents($product);

        return $model->toDomainEntity();
    }

    private function publishReleasedEvents(Product $product): void
    {
        foreach ($product->releaseEvents() as $event) {
            if ($event instanceof DomainEvent) {
                $this->eventBus->publish($event);
            }
        }
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

    /**
     * Insert products in chunks and return the auto-assigned IDs in insertion order.
     *
     * Strategy: passwords are guaranteed unique per generation run. After each chunk
     * insert, we retrieve the inserted IDs by matching the password column.
     * This is safe inside a transaction and compatible with both MySQL and SQLite.
     *
     * @param list<Product> $products
     * @param int $chunkSize
     * @return list<int>
     */
    public function bulkInsertAndReturnIds(array $products, int $chunkSize = 1000): array
    {
        if ($products === []) {
            return [];
        }

        /** @var array<string, int> $passwordToId */
        $passwordToId = [];
        $chunks = array_chunk($products, $chunkSize);

        foreach ($chunks as $chunk) {
            $passwords = array_map(
                static fn (Product $p): string => $p->password->value,
                $chunk,
            );

            $data = array_map(function (Product $product): array {
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
            }, $chunk);

            EloquentProduct::insert($data);

            // Fetch back the IDs by password (passwords are unique within this generation)
            $rows = DB::table('products')
                ->whereIn('password', $passwords)
                ->whereNull('deleted_at')
                ->select(['id', 'password'])
                ->get();

            foreach ($rows as $row) {
                $passwordToId[$row->password] = $row->id;
            }
        }

        // Return IDs in the same order as the input products
        return array_map(
            static function (Product $product) use ($passwordToId): int {
                $id = $passwordToId[$product->password->value] ?? null;

                if ($id === null) {
                    throw new \RuntimeException(
                        'Could not retrieve ID for product with password: ' . $product->password->value,
                    );
                }

                return $id;
            },
            $products,
        );
    }

    /**
     * Update product names in batch using a single SQL CASE WHEN expression.
     *
     * @param array<int, string> $idToName
     */
    public function bulkUpdateNames(array $idToName): void
    {
        if ($idToName === []) {
            return;
        }

        // Build: UPDATE products SET name = CASE WHEN id = ? THEN ? ... END WHERE id IN (...)
        $cases = '';
        $bindings = [];

        foreach ($idToName as $id => $name) {
            $cases .= ' WHEN id = ? THEN ?';
            $bindings[] = $id;
            $bindings[] = $name;
        }

        $ids = implode(',', array_keys($idToName));
        $bindings[] = now()->format('Y-m-d H:i:s');

        DB::statement(
            "UPDATE products SET name = CASE {$cases} END, updated_at = ? WHERE id IN ({$ids})",
            $bindings,
        );
    }

    /**
     * @param array{
     *   userId: int,
     *   page?: int,
     *   perPage?: int,
     *   sortBy?: string,
     *   sortDir?: string,
     *   configurationStatus?: string|null,
     *   active?: bool|null,
     *   model?: string|null,
     *   targetUrl?: string|null,
     *   hasBusinessInfo?: bool|null,
     * } $params
     */
    public function listForUser(array $params): PaginatedResult
    {
        $userId = $params['userId'];
        $page = max(1, $params['page'] ?? 1);
        $perPage = min(100, max(1, $params['perPage'] ?? 15));
        $sortBy = $params['sortBy'] ?? 'assigned_at';
        $sortDir = $params['sortDir'] ?? 'desc';
        $configurationStatus = $params['configurationStatus'] ?? null;
        $active = $params['active'] ?? null;
        $model = $params['model'] ?? null;
        $targetUrl = $params['targetUrl'] ?? null;
        $hasBusinessInfo = $params['hasBusinessInfo'] ?? null;

        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        $allowedSortFields = ['name', 'usage', 'active', 'configuration_status', 'model', 'assigned_at'];
        $targetUrl = is_string($targetUrl) ? trim($targetUrl) : null;

        $query = EloquentProduct::query()
            ->where('products.user_id', $userId)
            ->whereNull('products.linked_to_product_id')
            ->with([
                'productType:id,code,name',
                'productBusiness:id,product_id,types,size',
            ]);

        if ($configurationStatus !== null) {
            $query->where('products.configuration_status', $configurationStatus);
        }

        if ($active !== null) {
            $query->where('products.active', $active);
        }

        if ($model !== null) {
            $query->where('products.model', $model);
        }

        if ($targetUrl !== null && $targetUrl !== '') {
            $escapedTargetUrl = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $targetUrl);
            $query->where('products.target_url', 'LIKE', '%' . $escapedTargetUrl . '%');
        }

        if ($hasBusinessInfo === true) {
            $query->whereHas('productBusiness');
        } elseif ($hasBusinessInfo === false) {
            $query->whereDoesntHave('productBusiness');
        }

        if ($sortBy === 'configuration_status') {
            $query->orderByRaw(
                "CASE WHEN products.configuration_status = 'completed' THEN 0 ELSE 1 END {$sortDir}"
            )->orderBy('products.assigned_at', 'desc');
        } elseif (in_array($sortBy, $allowedSortFields, true)) {
            $query->orderBy('products.' . $sortBy, $sortDir);
        } else {
            $query->orderBy('products.assigned_at', 'desc');
        }

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        /** @var list<EloquentProduct> $models */
        $models = $paginated->items();

        $items = array_map(
            static function (EloquentProduct $product): array {
                /** @var ProductTypeModel|null $productType */
                $productType = $product->getRelation('productType');
                /** @var EloquentProductBusiness|null $business */
                $business = $product->getRelation('productBusiness');

                $resource = new ProductListItemResource(
                    id: $product->id,
                    name: $product->name,
                    model: $product->model,
                    active: $product->active,
                    configurationStatus: $product->configuration_status,
                    usage: $product->usage,
                    targetUrl: $product->target_url,
                    assignedAt: $product->assigned_at?->toIso8601String(),
                    productType: [
                        'id' => $productType?->id ?? $product->product_type_id,
                        'code' => $productType?->code ?? '',
                        'name' => $productType?->name ?? '',
                    ],
                    business: $business !== null ? [
                        'types' => is_array($business->types) ? $business->types : [],
                        'size' => $business->size,
                    ] : null,
                );

                return $resource->toArray();
            },
            $models,
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
