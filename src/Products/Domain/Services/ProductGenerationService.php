<?php

declare(strict_types=1);

namespace Src\Products\Domain\Services;

use Src\Products\Domain\DataTransfer\GeneratedProductItem;
use Src\Products\Domain\DataTransfer\GeneratedProductsResult;
use Src\Products\Domain\DataTransfer\GenerateProductsInputItem;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Entities\ProductType;
use Src\Products\Domain\Ports\PasswordGeneratorPort;
use Src\Products\Domain\ValueObjects\ProductDescription;
use Src\Products\Domain\ValueObjects\ProductName;

final class ProductGenerationService
{
    public function __construct(
        private readonly PasswordGeneratorPort $passwordGenerator,
    ) {}

    /**
     * Build Product entities for batch insertion.
     *
     * - Single-model types: `quantity` products with primary model
     * - Dual-model types: `quantity × 2` products (primary + secondary, NOT linked)
     * - active = true, user_id = null, linked_to_product_id = null
     * - description: item description if provided, else ProductType.description
     * - name is a placeholder; it MUST be updated post-insert via assignNames()
     *
     * @param array<int, ProductType> $typeMap   ID → ProductType
     * @param list<GenerateProductsInputItem> $items
     * @param int $passwordLength
     * @return list<Product>
     */
    public function buildProducts(
        array $typeMap,
        array $items,
        int $passwordLength,
    ): array {
        $products = [];

        foreach ($items as $item) {
            $type = $typeMap[$item->typeId];
            $description = $item->description ?? $type->description;

            for ($i = 0; $i < $item->quantity; $i++) {
                $primary = $this->makeProduct(
                    type: $type,
                    model: $type->models->primary,
                    passwordLength: $passwordLength,
                    size: $item->size,
                    description: $description,
                );
                $products[] = $primary;

                if ($type->models->secondary !== null) {
                    $secondary = $this->makeProduct(
                        type: $type,
                        model: $type->models->secondary,
                        passwordLength: $passwordLength,
                        size: $item->size,
                        description: $description,
                    );
                    $products[] = $secondary;
                }
            }
        }

        return $products;
    }

    /**
     * Build the result DTO after products have been persisted and IDs assigned.
     *
     * @param list<Product> $products   Products with DB-assigned IDs and updated names
     * @param string $frontUrl          Base URL for redirect links
     * @return GeneratedProductsResult
     */
    public function buildResult(array $products, string $frontUrl): GeneratedProductsResult
    {
        /** @var array<string, list<GeneratedProductItem>> $byCode */
        $byCode = [];

        foreach ($products as $product) {
            $code = $product->model->value;
            $byCode[$code][] = new GeneratedProductItem(
                id: $product->id,
                name: $product->name->value,
                password: $product->password->value,
                model: $product->model->value,
                redirectUrl: rtrim($frontUrl, '/') . '/' . $product->password->value,
            );
        }

        return new GeneratedProductsResult(
            totalCount: count($products),
            productsByTypeCode: $byCode,
        );
    }

    private function makeProduct(
        ProductType $type,
        string $model,
        int $passwordLength,
        ?string $size,
        ?string $description,
    ): Product {
        $product = Product::create(
            productTypeId: $type->id,
            model: $model,
            password: $this->passwordGenerator->generate($passwordLength),
        );

        // Override defaults set by Product::create()
        $product->active = true;
        $product->userId = null;
        $product->linkedToProductId = null;
        $product->size = $size;

        if ($description !== null) {
            $product->description = ProductDescription::from($description);
        }

        // Name will be updated post-insert — set placeholder to avoid empty string
        $product->name = ProductName::from($model);

        return $product;
    }
}
