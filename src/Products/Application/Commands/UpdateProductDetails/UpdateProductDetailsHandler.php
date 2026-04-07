<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\UpdateProductDetails;

use Src\Identity\Domain\Ports\RolePort;
use Src\Products\Application\Resources\ProductResource;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ProductDetailsService;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Errors\Unauthorized;

final class UpdateProductDetailsHandler implements CommandHandler
{
    public function __construct(
        private readonly ProductRepositoryPort $productRepository,
        private readonly RolePort $rolePort,
        private readonly ProductDetailsService $detailsService,
    ) {}

    public function handle(Command $command): ProductResource
    {
        assert($command instanceof UpdateProductDetailsCommand);

        $product = $this->productRepository->findById($command->productId);

        if ($product === null) {
            throw NotFound::entity('product', (string) $command->productId);
        }

        $isOwner = $product->userId === $command->actorUserId;
        $canElevate = $isOwner
            || $this->rolePort->hasRole($command->actorUserId, 'developer')
            || $this->rolePort->hasRole($command->actorUserId, 'backoffice');

        if (!$isOwner && !$canElevate) {
            throw Unauthorized::because('product_details_forbidden');
        }

        $this->detailsService->apply(
            product: $product,
            hasName: $command->hasName,
            name: $command->name,
            hasDescription: $command->hasDescription,
            description: $command->description,
        );

        return ProductResource::fromEntity($this->productRepository->save($product));
    }
}
