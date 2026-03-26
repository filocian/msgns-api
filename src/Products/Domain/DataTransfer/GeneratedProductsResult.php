<?php

declare(strict_types=1);

namespace Src\Products\Domain\DataTransfer;

final readonly class GeneratedProductsResult
{
    /**
     * @param array<string, list<GeneratedProductItem>> $productsByTypeCode
     */
    public function __construct(
        public int $totalCount,
        public array $productsByTypeCode,
    ) {}

    /**
     * Returns the JSON-compatible legacy format:
     * { new_products_count: int, product_list: { code: [urls] } }
     *
     * @return array{new_products_count: int, product_list: array<string, list<string>>}
     */
    public function toJsonArray(): array
    {
        $productList = [];

        foreach ($this->productsByTypeCode as $code => $items) {
            $productList[$code] = array_map(
                static fn (GeneratedProductItem $item): string => $item->redirectUrl,
                $items,
            );
        }

        return [
            'new_products_count' => $this->totalCount,
            'product_list' => $productList,
        ];
    }
}
