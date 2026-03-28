<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Products\Application\Commands\RegisterProduct\RegisterProductCommand;
use Src\Products\Application\Commands\RegisterProduct\RegisterProductHandler;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ProductActivationService;
use Src\Products\Domain\Services\ProductAssignmentService;
use Src\Products\Domain\Services\ProductConfigStatusService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Errors\ValidationFailed;
use Src\Shared\Core\Ports\TransactionPort;

function makeRegisterableProduct(int $id = 42, string $password = 'secret'): Product
{
    return Product::fromPersistence(
        id: $id,
        productTypeId: 1,
        userId: null,
        model: 'ModelA',
        linkedToProductId: null,
        password: $password,
        targetUrl: null,
        usage: 0,
        name: 'Product',
        description: null,
        active: false,
        configurationStatus: ConfigurationStatus::notStarted(),
        assignedAt: null,
        size: null,
        createdAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        updatedAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        deletedAt: null,
    );
}

describe('RegisterProductHandler', function () {
    it('registers product with assignment, activation and assigned status', function () {
        $product = makeRegisterableProduct();

        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): mixed => $callback());

        $repo->shouldReceive('findById')->once()->with(42)->andReturn($product);
        $repo->shouldReceive('save')->once()->with($product)->andReturnUsing(static fn (Product $saved): Product => $saved);

        $handler = new RegisterProductHandler(
            $repo,
            new ProductAssignmentService(),
            new ProductActivationService(),
            new ProductConfigStatusService(),
            $transaction,
        );

        $result = $handler->handle(new RegisterProductCommand(
            productId: 42,
            userId: 7,
            password: 'secret',
        ));

        expect($result->userId)->toBe(7)
            ->and($result->active)->toBeTrue()
            ->and($result->configurationStatus)->toBe(ConfigurationStatus::ASSIGNED);
    });

    it('throws invalid_product_password when password does not match', function () {
        $product = makeRegisterableProduct(password: 'correct-password');

        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): mixed => $callback());

        $repo->shouldReceive('findById')->once()->with(42)->andReturn($product);
        $repo->shouldNotReceive('save');

        $handler = new RegisterProductHandler(
            $repo,
            new ProductAssignmentService(),
            new ProductActivationService(),
            new ProductConfigStatusService(),
            $transaction,
        );

        expect(fn () => $handler->handle(new RegisterProductCommand(productId: 42, userId: 7, password: 'wrong-password')))
            ->toThrow(ValidationFailed::class, 'invalid_product_password');
    });

    it('throws NotFound when product does not exist', function () {
        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): mixed => $callback());

        $repo->shouldReceive('findById')->once()->with(999)->andReturn(null);
        $repo->shouldNotReceive('save');

        $handler = new RegisterProductHandler(
            $repo,
            new ProductAssignmentService(),
            new ProductActivationService(),
            new ProductConfigStatusService(),
            $transaction,
        );

        expect(fn () => $handler->handle(new RegisterProductCommand(productId: 999, userId: 7, password: 'secret')))
            ->toThrow(NotFound::class);
    });
});
