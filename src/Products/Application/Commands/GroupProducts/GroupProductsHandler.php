<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\GroupProducts;

use Src\Products\Application\Resources\ProductResource;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Ports\ProductTypeRepository;
use Src\Products\Domain\Services\ProductGroupingService;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Errors\ValidationFailed;
use Src\Shared\Core\Ports\TransactionPort;

final class GroupProductsHandler implements CommandHandler
{
    public function __construct(
        private readonly ProductRepositoryPort $productRepository,
        private readonly ProductGroupingService $groupingService,
        private readonly ProductTypeRepository $productTypeRepository,
        private readonly TransactionPort $transaction,
    ) {}

    public function handle(Command $command): ProductResource
    {
        assert($command instanceof GroupProductsCommand);

        return $this->transaction->run(function () use ($command): ProductResource {
            $referenceProduct = $this->productRepository->findById($command->referenceId);

            if ($referenceProduct === null) {
                throw NotFound::entity('product', (string) $command->referenceId);
            }

            $candidateProduct = $this->productRepository->findById($command->candidateId);

            if ($candidateProduct === null) {
                throw NotFound::entity('product', (string) $command->candidateId);
            }

            $productType = $this->productTypeRepository->findById($referenceProduct->productTypeId);

            if ($productType === null) {
                throw NotFound::entity('product_type', (string) $referenceProduct->productTypeId);
            }

            $isInvalidModelCombination =
                $productType->models->primary !== $referenceProduct->model->value
                || $productType->models->primary === $candidateProduct->model->value;

            if ($isInvalidModelCombination) {
                throw ValidationFailed::because('invalid_model_combination', [
                    'primary_model' => $productType->models->primary,
                    'reference_model' => $referenceProduct->model->value,
                    'candidate_model' => $candidateProduct->model->value,
                ]);
            }

            $this->groupingService->link($referenceProduct, $candidateProduct);

            return ProductResource::fromEntity($this->productRepository->save($referenceProduct));
        });
    }
}
