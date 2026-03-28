<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\RegisterProduct;

use Src\Products\Application\Resources\ProductResource;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ProductActivationService;
use Src\Products\Domain\Services\ProductAssignmentService;
use Src\Products\Domain\Services\ProductConfigStatusService;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Errors\ValidationFailed;
use Src\Shared\Core\Ports\TransactionPort;

final class RegisterProductHandler implements CommandHandler
{
    public function __construct(
        private readonly ProductRepositoryPort $productRepository,
        private readonly ProductAssignmentService $assignmentService,
        private readonly ProductActivationService $activationService,
        private readonly ProductConfigStatusService $configStatusService,
        private readonly TransactionPort $transaction,
    ) {}

    public function handle(Command $command): ProductResource
    {
        assert($command instanceof RegisterProductCommand);

        return $this->transaction->run(function () use ($command): ProductResource {
            $product = $this->productRepository->findById($command->productId);

            if ($product === null) {
                throw NotFound::entity('product', (string) $command->productId);
            }

            if ($product->password->value !== $command->password) {
                throw ValidationFailed::because('invalid_product_password');
            }

            $this->assignmentService->assign($product, $command->userId);
            $this->activationService->activate($product);
            $this->configStatusService->transition($product, 'assigned');

            return ProductResource::fromEntity($this->productRepository->save($product));
        });
    }
}
