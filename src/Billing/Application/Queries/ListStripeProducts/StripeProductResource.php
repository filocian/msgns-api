<?php

declare(strict_types=1);

namespace Src\Billing\Application\Queries\ListStripeProducts;

use OpenApi\Attributes as OA;
use Src\Billing\Application\Resources\StripePriceResource;
use Src\Billing\Domain\DataTransferObjects\StripeCatalogProduct;

#[OA\Schema(
    schema: 'StripeProductResource',
    title: 'StripeProductResource',
    properties: [
        new OA\Property(property: 'id', type: 'string', example: 'prod_1Abc'),
        new OA\Property(property: 'name', type: 'string', example: 'Pro'),
        new OA\Property(property: 'active', type: 'boolean', example: true),
        new OA\Property(
            property: 'prices',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/StripePriceResource'),
        ),
        new OA\Property(
            property: 'metadata',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(type: 'string'),
        ),
    ],
)]
final readonly class StripeProductResource
{
    /**
     * @param list<StripePriceResource> $prices
     * @param array<string, string>     $metadata
     */
    public function __construct(
        public string $id,
        public string $name,
        public bool $active,
        public array $prices,
        public array $metadata,
    ) {}

    public static function fromDomain(StripeCatalogProduct $product): self
    {
        return new self(
            id: $product->id,
            name: $product->name,
            active: $product->active,
            prices: array_map(
                static fn ($p): StripePriceResource => StripePriceResource::fromDomain($p),
                $product->prices,
            ),
            metadata: $product->metadata,
        );
    }
}
