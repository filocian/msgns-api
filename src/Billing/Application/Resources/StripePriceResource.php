<?php

declare(strict_types=1);

namespace Src\Billing\Application\Resources;

use OpenApi\Attributes as OA;
use Src\Billing\Domain\DataTransferObjects\StripeCatalogPrice;

#[OA\Schema(
    schema: 'StripePriceResource',
    title: 'StripePriceResource',
    properties: [
        new OA\Property(property: 'id', type: 'string', example: 'price_1Abc'),
        new OA\Property(property: 'productId', type: 'string', example: 'prod_1Abc'),
        new OA\Property(property: 'currency', type: 'string', example: 'eur'),
        new OA\Property(property: 'unit_amount', type: 'integer', example: 999, description: 'Amount in minor units (cents).'),
        new OA\Property(property: 'type', type: 'string', enum: ['recurring', 'one_time'], example: 'recurring'),
        new OA\Property(property: 'interval', type: 'string', nullable: true, enum: ['month', 'year'], example: 'month'),
        new OA\Property(property: 'active', type: 'boolean', example: true),
    ],
)]
final readonly class StripePriceResource
{
    public function __construct(
        public string $id,
        public string $productId,
        public string $currency,
        public int $unit_amount,
        public string $type,
        public ?string $interval,
        public bool $active,
    ) {}

    public static function fromDomain(StripeCatalogPrice $price): self
    {
        return new self(
            id: $price->id,
            productId: $price->productId,
            currency: $price->currency,
            unit_amount: $price->unitAmount,
            type: $price->type,
            interval: $price->interval,
            active: $price->active,
        );
    }
}
