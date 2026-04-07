<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Identity\Domain\Ports\RolePort;
use Src\Products\Application\Commands\RegisterProduct\RegisterProductCommand;
use Src\Products\Application\Commands\RegisterProduct\RegisterProductHandler;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ProductActivationService;
use Src\Products\Domain\Services\ProductAssignmentService;
use Src\Products\Domain\Services\ProductConfigStatusService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Errors\Unauthorized;
use Src\Shared\Core\Errors\ValidationFailed;
use Src\Shared\Core\Ports\TransactionPort;

function makeRegisterableProduct(int $id = 42, string $password = 'secret', ?int $userId = null): Product
{
    return Product::fromPersistence(
        id: $id,
        productTypeId: 1,
        userId: $userId,
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

/**
 * @param MockInterface&ProductRepositoryPort $repo
 * @param MockInterface&TransactionPort $transaction
 * @param MockInterface&RolePort $rolePort
 */
function makeRegisterHandler(
    mixed $repo,
    mixed $transaction,
    mixed $rolePort,
): RegisterProductHandler {
    return new RegisterProductHandler(
        $repo,
        new ProductAssignmentService(),
        new ProductActivationService(),
        new ProductConfigStatusService(),
        $transaction,
        $rolePort,
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
        /** @var MockInterface&RolePort $rolePort */
        $rolePort = Mockery::mock(RolePort::class);

        $repo->shouldReceive('findById')->once()->with(42)->andReturn($product);
        $repo->shouldReceive('save')->once()->with($product)->andReturnUsing(static fn (Product $saved): Product => $saved);

        $result = makeRegisterHandler($repo, $transaction, $rolePort)->handle(
            new RegisterProductCommand(productId: 42, userId: 7, password: 'secret', actorUserId: 7),
        );

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
        /** @var MockInterface&RolePort $rolePort */
        $rolePort = Mockery::mock(RolePort::class);

        $repo->shouldReceive('findById')->once()->with(42)->andReturn($product);
        $repo->shouldNotReceive('save');

        expect(fn () => makeRegisterHandler($repo, $transaction, $rolePort)->handle(
            new RegisterProductCommand(productId: 42, userId: 7, password: 'wrong-password', actorUserId: 7),
        ))->toThrow(ValidationFailed::class, 'invalid_product_password');
    });

    it('throws NotFound when product does not exist', function () {
        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): mixed => $callback());
        /** @var MockInterface&RolePort $rolePort */
        $rolePort = Mockery::mock(RolePort::class);

        $repo->shouldReceive('findById')->once()->with(999)->andReturn(null);
        $repo->shouldNotReceive('save');

        expect(fn () => makeRegisterHandler($repo, $transaction, $rolePort)->handle(
            new RegisterProductCommand(productId: 999, userId: 7, password: 'secret', actorUserId: 7),
        ))->toThrow(NotFound::class);
    });

    it('throws product_already_owned when a regular user tries to register an already-owned product', function () {
        $product = makeRegisterableProduct(password: 'secret', userId: 99);

        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): mixed => $callback());
        /** @var MockInterface&RolePort $rolePort */
        $rolePort = Mockery::mock(RolePort::class);
        $rolePort->shouldReceive('hasRole')->with(7, 'developer')->once()->andReturn(false);
        $rolePort->shouldReceive('hasRole')->with(7, 'backoffice')->once()->andReturn(false);

        $repo->shouldReceive('findById')->once()->with(42)->andReturn($product);
        $repo->shouldNotReceive('save');

        expect(fn () => makeRegisterHandler($repo, $transaction, $rolePort)->handle(
            new RegisterProductCommand(productId: 42, userId: 7, password: 'secret', actorUserId: 7),
        ))->toThrow(Unauthorized::class, 'product_already_owned');
    });

    it('allows an admin to reassign an already-owned product', function () {
        $product = makeRegisterableProduct(password: 'secret', userId: 99);

        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): mixed => $callback());
        /** @var MockInterface&RolePort $rolePort */
        $rolePort = Mockery::mock(RolePort::class);
        $rolePort->shouldReceive('hasRole')->with(5, 'developer')->once()->andReturn(true);

        $repo->shouldReceive('findById')->once()->with(42)->andReturn($product);
        $repo->shouldReceive('save')->once()->andReturnUsing(static fn (Product $saved): Product => $saved);

        $result = makeRegisterHandler($repo, $transaction, $rolePort)->handle(
            new RegisterProductCommand(productId: 42, userId: 5, password: 'secret', actorUserId: 5),
        );

        expect($result->userId)->toBe(5);
    });
});
