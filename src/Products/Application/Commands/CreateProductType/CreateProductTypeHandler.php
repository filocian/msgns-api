<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\CreateProductType;

use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Products\Domain\Entities\ProductType;
use Src\Products\Domain\Ports\ProductTypeRepository;
use Src\Products\Application\Resources\ProductTypeResource;

final class CreateProductTypeHandler implements CommandHandler
{
    public function __construct(
        private readonly ProductTypeRepository $repo,
    ) {}

    public function handle(Command $command): ProductTypeResource
    {
        assert($command instanceof CreateProductTypeCommand);

        $productType = ProductType::create(
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
