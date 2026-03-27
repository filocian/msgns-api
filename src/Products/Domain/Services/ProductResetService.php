<?php

declare(strict_types=1);

namespace Src\Products\Domain\Services;

use Psr\Log\LoggerInterface;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Events\ProductReset;
use Src\Products\Domain\Ports\ProductTypeRepository;
use Src\Products\Domain\Ports\ProductUsagePort;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Products\Domain\ValueObjects\ProductName;
use Src\Shared\Core\Errors\ValidationFailed;

final class ProductResetService
{
    public function __construct(
        private readonly ProductTypeRepository $typeRepository,
        private readonly ProductUsagePort $usagePort,
        private readonly LoggerInterface $logger,
    ) {}

    public function reset(Product $product): void
    {
        $productType = $this->typeRepository->findById($product->productTypeId);
        $code = $productType?->code->value ?? '';

        if (str_starts_with($code, 'B-') || str_starts_with($code, 'F-')) {
            throw ValidationFailed::because('product_type_not_resettable', [
                'product_type_code' => $code,
            ]);
        }

        // Clear all configurable fields
        $product->userId = null;
        $product->targetUrl = null;
        $product->linkedToProductId = null;
        $product->assignedAt = null;
        $product->usage = 0;
        $product->configurationStatus = ConfigurationStatus::notStarted();

        // Reset name to default format: "{model} ({id})"
        $product->name = ProductName::from(
            sprintf('%s (%d)', $product->model->value, $product->id)
        );

        // Delete usage data (DynamoDB failures must not rollback the DB reset)
        try {
            $this->usagePort->deleteProductUsage($product->id);
        } catch (\Throwable $e) {
            $this->logger->error('ProductResetService: DynamoDB deleteProductUsage failed', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Record reset event
        $product->recordEvent(new ProductReset($product->id));
    }
}
