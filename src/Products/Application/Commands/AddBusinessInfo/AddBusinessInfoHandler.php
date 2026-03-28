<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\AddBusinessInfo;

use Src\Products\Application\Resources\ProductResource;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ProductBusinessService;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Ports\TransactionPort;

final class AddBusinessInfoHandler implements CommandHandler
{
    public function __construct(
        private readonly ProductRepositoryPort $productRepository,
        private readonly ProductBusinessService $businessService,
        private readonly TransactionPort $transaction,
    ) {}

    public function handle(Command $command): ProductResource
    {
        assert($command instanceof AddBusinessInfoCommand);

        return $this->transaction->run(function () use ($command): ProductResource {
            $product = $this->productRepository->findById($command->productId);

            if ($product === null) {
                throw NotFound::entity('product', (string) $command->productId);
            }

            $this->businessService->updateBusiness($product, [
                'userId' => $command->userId,
                'notABusiness' => $command->notABusiness,
                'name' => $command->name,
                'types' => $command->types,
                'placeTypes' => $command->placeTypes,
                'size' => $command->size,
            ]);

            return ProductResource::fromEntity($this->productRepository->save($product));
        });
    }
}
