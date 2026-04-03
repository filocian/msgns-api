<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\ChangeConfigStatus;

use InvalidArgumentException;
use Src\Products\Application\Resources\ProductResource;
use Src\Products\Domain\Events\ProductConfigStatusChanged;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ProductConfigStatusService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Errors\ValidationFailed;

final class ChangeConfigStatusHandler implements CommandHandler
{
    public function __construct(
        private readonly ProductRepositoryPort $productRepository,
        private readonly ProductConfigStatusService $configStatusService,
    ) {}

    public function handle(Command $command): ProductResource
    {
        assert($command instanceof ChangeConfigStatusCommand);

        $product = $this->productRepository->findById($command->productId);

        if ($product === null) {
            throw NotFound::entity('product', (string) $command->productId);
        }

        $status = ConfigurationStatus::from($command->status);
        $previousStatus = $product->configurationStatus->value;

        try {
            $this->configStatusService->transition($product, $status->value);
        } catch (InvalidArgumentException $exception) {
            throw ValidationFailed::because('invalid_configuration_status_transition', [
                'product_id' => $command->productId,
                'requested_status' => $status->value,
            ]);
        }

        $product->recordEvent(new ProductConfigStatusChanged(
            productId: $product->id,
            previousStatus: $previousStatus,
            newStatus: $status->value,
        ));

        return ProductResource::fromEntity($this->productRepository->save($product));
    }
}
