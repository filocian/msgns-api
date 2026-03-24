<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\UpdateProductType;

use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;
use Src\Products\Domain\Ports\ProductTypeRepository;
use Src\Products\Domain\Services\ProductTypeUsageInspector;
use Src\Products\Application\Resources\ProductTypeResource;

final class UpdateProductTypeHandler implements CommandHandler
{
    public function __construct(
        private readonly ProductTypeRepository $repo,
        private readonly ProductTypeUsageInspector $usageInspector,
    ) {}

    public function handle(Command $command): ProductTypeResource
    {
        assert($command instanceof UpdateProductTypeCommand);

        $productType = $this->repo->findById($command->productTypeId);

        if ($productType === null) {
            throw NotFound::entity('product_type', (string) $command->productTypeId);
        }

        $isInUse = $this->usageInspector->isUsed($command->productTypeId);

        $productType->applyUpdate(
            isUsed: $isInUse,
            code: $command->code,
            name: $command->name,
            primaryModel: $command->primaryModel,
            secondaryModel: $command->secondaryModel,
        );

        $saved = $this->repo->save($productType);

        return new ProductTypeResource(
            id: $saved->id,
            code: $saved->code->value,
            name: $saved->name,
            primaryModel: $saved->models->primary,
            secondaryModel: $saved->models->secondary,
            createdAt: $saved->createdAt->format('c'),
            updatedAt: $saved->updatedAt->format('c'),
        );
    }
}
