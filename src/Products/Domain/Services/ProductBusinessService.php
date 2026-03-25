<?php

declare(strict_types=1);

namespace Src\Products\Domain\Services;

use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Entities\ProductBusiness;
use Src\Products\Domain\Events\ProductBusinessUpdated;
use Src\Products\Domain\Ports\ProductBusinessPort;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;

final class ProductBusinessService
{
    public function __construct(
        private readonly ProductBusinessPort $businessPort,
    ) {}

    /**
     * @param array<string, mixed> $businessData
     */
    public function updateBusiness(Product $product, array $businessData): void
    {
        // Create or update ProductBusiness
        $existingBusiness = $this->businessPort->findByProductId($product->id);

        if ($existingBusiness === null) {
            // Create new business record
            $business = ProductBusiness::create(
                productId: $product->id,
                userId: $businessData['userId'] ?? throw new \InvalidArgumentException('userId is required'),
                notABusiness: $businessData['notABusiness'] ?? false,
                name: $businessData['name'] ?? null,
                types: $businessData['types'] ?? [],
                placeTypes: $businessData['placeTypes'] ?? null,
                size: $businessData['size'] ?? null,
            );
        } else {
            // Update existing business record
            $existingBusiness->notABusiness = $businessData['notABusiness'] ?? $existingBusiness->notABusiness;
            $existingBusiness->name = $businessData['name'] ?? $existingBusiness->name;
            $existingBusiness->types = $businessData['types'] ?? $existingBusiness->types;
            $existingBusiness->placeTypes = $businessData['placeTypes'] ?? $existingBusiness->placeTypes;
            $existingBusiness->size = $businessData['size'] ?? $existingBusiness->size;
            $business = $existingBusiness;
        }

        $this->businessPort->save($business);

        // Try to transition to 'business-set' status
        $currentStatus = $product->configurationStatus;
        $targetStatus = ConfigurationStatus::from(ConfigurationStatus::BUSINESS_SET);

        if ($currentStatus->canTransitionTo($targetStatus)) {
            $product->configurationStatus = $targetStatus;
        }

        // Record business updated event
        $product->recordEvent(new ProductBusinessUpdated($product->id, $businessData));
    }
}
