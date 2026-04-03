<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Products\Application\Commands\ChangeConfigStatus\ChangeConfigStatusCommand;
use Src\Products\Application\Commands\ChangeConfigStatus\ChangeConfigStatusHandler;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Events\ProductConfigStatusChanged;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ProductConfigStatusService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Errors\ValidationFailed;

function makeConfigStatusProduct(int $id = 42, string $status = ConfigurationStatus::NOT_STARTED): Product
{
    return Product::fromPersistence(
        id: $id,
        productTypeId: 1,
        userId: null,
        model: 'GPT-4',
        linkedToProductId: null,
        password: 'secret',
        targetUrl: null,
        usage: 0,
        name: 'Product',
        description: null,
        active: true,
        configurationStatus: ConfigurationStatus::from($status),
        assignedAt: null,
        size: null,
        createdAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        updatedAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        deletedAt: null,
    );
}

describe('ChangeConfigStatusHandler', function () {
    it('changes the configuration status and returns a ProductResource', function () {
        $product = makeConfigStatusProduct(status: ConfigurationStatus::NOT_STARTED);
        $recordedEvents = [];

        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        $service = new ProductConfigStatusService();

        $repo->shouldReceive('findById')->once()->with(42)->andReturn($product);
        $repo->shouldReceive('save')->once()->with($product)->andReturnUsing(static function (Product $saved) use (&$recordedEvents): Product {
            $recordedEvents = $saved->releaseEvents();

            return $saved;
        });

        $handler = new ChangeConfigStatusHandler($repo, $service);

        $result = $handler->handle(new ChangeConfigStatusCommand(productId: 42, status: 'assigned'));

        expect($result->configurationStatus)->toBe('assigned')
            ->and($recordedEvents)->toHaveCount(1)
            ->and($recordedEvents[0])->toBeInstanceOf(ProductConfigStatusChanged::class)
            ->and($recordedEvents[0]->productId)->toBe(42)
            ->and($recordedEvents[0]->previousStatus)->toBe(ConfigurationStatus::NOT_STARTED)
            ->and($recordedEvents[0]->newStatus)->toBe(ConfigurationStatus::ASSIGNED);
    });

    it('throws NotFound when the product does not exist', function () {
        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        $service = new ProductConfigStatusService();

        $repo->shouldReceive('findById')->once()->with(999)->andReturn(null);
        $repo->shouldNotReceive('save');

        $handler = new ChangeConfigStatusHandler($repo, $service);

        expect(fn () => $handler->handle(new ChangeConfigStatusCommand(productId: 999, status: 'assigned')))
            ->toThrow(NotFound::class);
    });

    it('throws ValidationFailed when the status string is invalid', function () {
        $product = makeConfigStatusProduct();

        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        $service = new ProductConfigStatusService();

        $repo->shouldReceive('findById')->once()->with(42)->andReturn($product);
        $repo->shouldNotReceive('save');

        $handler = new ChangeConfigStatusHandler($repo, $service);

        expect(fn () => $handler->handle(new ChangeConfigStatusCommand(productId: 42, status: 'not_a_real_status')))
            ->toThrow(ValidationFailed::class);
    });

    it('wraps InvalidArgumentException from the service as ValidationFailed', function () {
        $product = makeConfigStatusProduct(status: ConfigurationStatus::COMPLETED);

        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        $service = new ProductConfigStatusService();

        $repo->shouldReceive('findById')->once()->with(42)->andReturn($product);
        $repo->shouldNotReceive('save');

        $handler = new ChangeConfigStatusHandler($repo, $service);

        expect(fn () => $handler->handle(new ChangeConfigStatusCommand(productId: 42, status: 'assigned')))
            ->toThrow(ValidationFailed::class, 'invalid_configuration_status_transition');
    });
});
