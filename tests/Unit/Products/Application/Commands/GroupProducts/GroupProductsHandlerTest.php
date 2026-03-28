<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Products\Application\Commands\GroupProducts\GroupProductsCommand;
use Src\Products\Application\Commands\GroupProducts\GroupProductsHandler;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Entities\ProductType;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Ports\ProductTypeRepository;
use Src\Products\Domain\Services\ProductGroupingService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Errors\ValidationFailed;
use Src\Shared\Core\Ports\TransactionPort;

function makeGroupProduct(int $id, int $typeId, int $userId, string $model, ?int $linkedToProductId = null): Product
{
    return Product::fromPersistence(
        id: $id,
        productTypeId: $typeId,
        userId: $userId,
        model: $model,
        linkedToProductId: $linkedToProductId,
        password: 'secret',
        targetUrl: null,
        usage: 0,
        name: 'Product',
        description: null,
        active: true,
        configurationStatus: ConfigurationStatus::from(ConfigurationStatus::ASSIGNED),
        assignedAt: null,
        size: null,
        createdAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        updatedAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        deletedAt: null,
    );
}

function makeGroupProductType(int $id = 5, string $primary = 'ModelA', ?string $secondary = 'ModelB'): ProductType
{
    return ProductType::fromPersistence(
        id: $id,
        code: 'TYPE-A',
        name: 'Type A',
        primaryModel: $primary,
        secondaryModel: $secondary,
        createdAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        updatedAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
    );
}

describe('GroupProductsHandler', function () {
    it('links products when reference is the primary model', function () {
        $reference = makeGroupProduct(id: 42, typeId: 5, userId: 7, model: 'ModelA');
        $candidate = makeGroupProduct(id: 84, typeId: 5, userId: 7, model: 'ModelB');

        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&ProductTypeRepository $typeRepo */
        $typeRepo = Mockery::mock(ProductTypeRepository::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): mixed => $callback());

        $repo->shouldReceive('findById')->once()->with(42)->andReturn($reference);
        $repo->shouldReceive('findById')->once()->with(84)->andReturn($candidate);
        $typeRepo->shouldReceive('findById')->once()->with(5)->andReturn(makeGroupProductType());
        $repo->shouldReceive('save')->once()->with($reference)->andReturnUsing(static fn (Product $saved): Product => $saved);

        $handler = new GroupProductsHandler($repo, new ProductGroupingService(), $typeRepo, $transaction);

        $result = $handler->handle(new GroupProductsCommand(referenceId: 42, candidateId: 84));

        expect($result->linkedToProductId)->toBe(84);
    });

    it('throws invalid_model_combination when reference is not primary model', function () {
        $reference = makeGroupProduct(id: 42, typeId: 5, userId: 7, model: 'ModelB');
        $candidate = makeGroupProduct(id: 84, typeId: 5, userId: 7, model: 'ModelA');

        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&ProductTypeRepository $typeRepo */
        $typeRepo = Mockery::mock(ProductTypeRepository::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): mixed => $callback());

        $repo->shouldReceive('findById')->once()->with(42)->andReturn($reference);
        $repo->shouldReceive('findById')->once()->with(84)->andReturn($candidate);
        $typeRepo->shouldReceive('findById')->once()->with(5)->andReturn(makeGroupProductType());
        $repo->shouldNotReceive('save');

        $handler = new GroupProductsHandler($repo, new ProductGroupingService(), $typeRepo, $transaction);

        expect(fn () => $handler->handle(new GroupProductsCommand(referenceId: 42, candidateId: 84)))
            ->toThrow(ValidationFailed::class, 'invalid_model_combination');
    });

    it('throws primary_product_already_linked when reference is already linked', function () {
        $reference = makeGroupProduct(id: 42, typeId: 5, userId: 7, model: 'ModelA', linkedToProductId: 99);
        $candidate = makeGroupProduct(id: 84, typeId: 5, userId: 7, model: 'ModelB');

        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&ProductTypeRepository $typeRepo */
        $typeRepo = Mockery::mock(ProductTypeRepository::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): mixed => $callback());

        $repo->shouldReceive('findById')->once()->with(42)->andReturn($reference);
        $repo->shouldReceive('findById')->once()->with(84)->andReturn($candidate);
        $typeRepo->shouldReceive('findById')->once()->with(5)->andReturn(makeGroupProductType());
        $repo->shouldNotReceive('save');

        $handler = new GroupProductsHandler($repo, new ProductGroupingService(), $typeRepo, $transaction);

        expect(fn () => $handler->handle(new GroupProductsCommand(referenceId: 42, candidateId: 84)))
            ->toThrow(ValidationFailed::class, 'primary_product_already_linked');
    });

    it('throws secondary_product_already_linked when candidate is already linked', function () {
        $reference = makeGroupProduct(id: 42, typeId: 5, userId: 7, model: 'ModelA');
        $candidate = makeGroupProduct(id: 84, typeId: 5, userId: 7, model: 'ModelB', linkedToProductId: 99);

        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&ProductTypeRepository $typeRepo */
        $typeRepo = Mockery::mock(ProductTypeRepository::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): mixed => $callback());

        $repo->shouldReceive('findById')->once()->with(42)->andReturn($reference);
        $repo->shouldReceive('findById')->once()->with(84)->andReturn($candidate);
        $typeRepo->shouldReceive('findById')->once()->with(5)->andReturn(makeGroupProductType());
        $repo->shouldNotReceive('save');

        $handler = new GroupProductsHandler($repo, new ProductGroupingService(), $typeRepo, $transaction);

        expect(fn () => $handler->handle(new GroupProductsCommand(referenceId: 42, candidateId: 84)))
            ->toThrow(ValidationFailed::class, 'secondary_product_already_linked');
    });

    it('throws products_must_have_same_user when products belong to different users', function () {
        $reference = makeGroupProduct(id: 42, typeId: 5, userId: 7, model: 'ModelA');
        $candidate = makeGroupProduct(id: 84, typeId: 5, userId: 9, model: 'ModelB');

        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&ProductTypeRepository $typeRepo */
        $typeRepo = Mockery::mock(ProductTypeRepository::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): mixed => $callback());

        $repo->shouldReceive('findById')->once()->with(42)->andReturn($reference);
        $repo->shouldReceive('findById')->once()->with(84)->andReturn($candidate);
        $typeRepo->shouldReceive('findById')->once()->with(5)->andReturn(makeGroupProductType());
        $repo->shouldNotReceive('save');

        $handler = new GroupProductsHandler($repo, new ProductGroupingService(), $typeRepo, $transaction);

        expect(fn () => $handler->handle(new GroupProductsCommand(referenceId: 42, candidateId: 84)))
            ->toThrow(ValidationFailed::class, 'products_must_have_same_user');
    });

    it('throws NotFound when reference product does not exist', function () {
        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&ProductTypeRepository $typeRepo */
        $typeRepo = Mockery::mock(ProductTypeRepository::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): mixed => $callback());

        $repo->shouldReceive('findById')->once()->with(42)->andReturn(null);
        $repo->shouldNotReceive('save');
        $typeRepo->shouldNotReceive('findById');

        $handler = new GroupProductsHandler($repo, new ProductGroupingService(), $typeRepo, $transaction);

        expect(fn () => $handler->handle(new GroupProductsCommand(referenceId: 42, candidateId: 84)))
            ->toThrow(NotFound::class);
    });

    it('throws NotFound when candidate product does not exist', function () {
        $reference = makeGroupProduct(id: 42, typeId: 5, userId: 7, model: 'ModelA');

        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&ProductTypeRepository $typeRepo */
        $typeRepo = Mockery::mock(ProductTypeRepository::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): mixed => $callback());

        $repo->shouldReceive('findById')->once()->with(42)->andReturn($reference);
        $repo->shouldReceive('findById')->once()->with(84)->andReturn(null);
        $repo->shouldNotReceive('save');
        $typeRepo->shouldNotReceive('findById');

        $handler = new GroupProductsHandler($repo, new ProductGroupingService(), $typeRepo, $transaction);

        expect(fn () => $handler->handle(new GroupProductsCommand(referenceId: 42, candidateId: 84)))
            ->toThrow(NotFound::class);
    });
});
