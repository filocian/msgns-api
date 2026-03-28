<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Products\Application\Commands\AddBusinessInfo\AddBusinessInfoCommand;
use Src\Products\Application\Commands\AddBusinessInfo\AddBusinessInfoHandler;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Entities\ProductBusiness;
use Src\Products\Domain\Ports\ProductBusinessPort;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ProductBusinessService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Ports\TransactionPort;

function makeBusinessProduct(int $id = 42): Product
{
    return Product::fromPersistence(
        id: $id,
        productTypeId: 1,
        userId: 7,
        model: 'ModelA',
        linkedToProductId: null,
        password: 'secret',
        targetUrl: 'https://example.com',
        usage: 0,
        name: 'Product',
        description: null,
        active: true,
        configurationStatus: ConfigurationStatus::from(ConfigurationStatus::TARGET_SET),
        assignedAt: null,
        size: null,
        createdAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        updatedAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        deletedAt: null,
    );
}

describe('AddBusinessInfoHandler', function () {
    it('updates business info and transitions to business-set', function () {
        $product = makeBusinessProduct();

        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&ProductBusinessPort $businessPort */
        $businessPort = Mockery::mock(ProductBusinessPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): mixed => $callback());

        $repo->shouldReceive('findById')->once()->with(42)->andReturn($product);
        $businessPort->shouldReceive('findByProductId')->once()->with(42)->andReturn(null);
        $businessPort->shouldReceive('save')->once()->with(Mockery::type(ProductBusiness::class))
            ->andReturnUsing(static fn (ProductBusiness $business): ProductBusiness => $business);
        $repo->shouldReceive('save')->once()->with($product)->andReturnUsing(static fn (Product $saved): Product => $saved);

        $handler = new AddBusinessInfoHandler($repo, new ProductBusinessService($businessPort), $transaction);

        $result = $handler->handle(new AddBusinessInfoCommand(
            productId: 42,
            userId: 7,
            notABusiness: false,
            types: ['restaurant' => true],
            name: 'My Biz',
            placeTypes: ['bar' => true],
            size: 'M',
        ));

        expect($result->configurationStatus)->toBe(ConfigurationStatus::BUSINESS_SET);
    });

    it('throws NotFound when product does not exist', function () {
        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&ProductBusinessPort $businessPort */
        $businessPort = Mockery::mock(ProductBusinessPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $callback): mixed => $callback());

        $repo->shouldReceive('findById')->once()->with(999)->andReturn(null);
        $repo->shouldNotReceive('save');
        $businessPort->shouldNotReceive('findByProductId');
        $businessPort->shouldNotReceive('save');

        $handler = new AddBusinessInfoHandler($repo, new ProductBusinessService($businessPort), $transaction);

        expect(fn () => $handler->handle(new AddBusinessInfoCommand(
            productId: 999,
            userId: 7,
            notABusiness: false,
            types: ['restaurant' => true],
            name: null,
            placeTypes: null,
            size: null,
        )))->toThrow(NotFound::class);
    });
});
