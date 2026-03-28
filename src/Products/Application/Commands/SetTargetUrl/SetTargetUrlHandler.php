<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\SetTargetUrl;

use Src\Products\Application\Resources\ProductResource;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ProductConfigurationService;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;

final class SetTargetUrlHandler implements CommandHandler
{
    public function __construct(
        private readonly ProductRepositoryPort $productRepository,
        private readonly ProductConfigurationService $configurationService,
    ) {}

    public function handle(Command $command): ProductResource
    {
        assert($command instanceof SetTargetUrlCommand);

        $product = $this->productRepository->findById($command->productId);

        if ($product === null) {
            throw NotFound::entity('product', (string) $command->productId);
        }

        $this->configurationService->setTargetUrl($product, $command->targetUrl);

        return ProductResource::fromEntity($this->productRepository->save($product));
    }
}
