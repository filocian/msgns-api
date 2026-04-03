<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\RemoveProductLink;

use Src\Products\Application\Resources\ProductResource;
use Src\Products\Domain\Events\ProductUnlinked;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ProductGroupingService;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;

final class RemoveProductLinkHandler implements CommandHandler
{
    public function __construct(
        private readonly ProductRepositoryPort $productRepository,
        private readonly ProductGroupingService $groupingService,
    ) {}

    public function handle(Command $command): ProductResource
    {
        assert($command instanceof RemoveProductLinkCommand);

        $product = $this->productRepository->findById($command->productId);

        if ($product === null) {
            throw NotFound::entity('product', (string) $command->productId);
        }

        $previousLinkedId = $product->linkedToProductId;
        $this->groupingService->unlink($product);

        if ($previousLinkedId !== null) {
            $product->recordEvent(new ProductUnlinked(
                productId: $product->id,
                previousLinkedProductId: $previousLinkedId,
            ));
        }

        return ProductResource::fromEntity($this->productRepository->save($product));
    }
}
