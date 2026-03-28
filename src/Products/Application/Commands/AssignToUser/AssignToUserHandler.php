<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\AssignToUser;

use Src\Products\Application\Resources\ProductResource;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ProductAssignmentService;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;

final class AssignToUserHandler implements CommandHandler
{
    public function __construct(
        private readonly ProductRepositoryPort $productRepository,
        private readonly ProductAssignmentService $assignmentService,
    ) {}

    public function handle(Command $command): ProductResource
    {
        assert($command instanceof AssignToUserCommand);

        $product = $this->productRepository->findById($command->productId);

        if ($product === null) {
            throw NotFound::entity('product', (string) $command->productId);
        }

        $this->assignmentService->assign($product, $command->userId);

        return ProductResource::fromEntity($this->productRepository->save($product));
    }
}
