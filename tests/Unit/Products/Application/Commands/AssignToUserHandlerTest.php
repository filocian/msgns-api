<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Products\Application\Commands\AssignToUser\AssignToUserCommand;
use Src\Products\Application\Commands\AssignToUser\AssignToUserHandler;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ProductAssignmentService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Errors\NotFound;

function makeAssignableProduct(int $id = 42): Product
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
        configurationStatus: ConfigurationStatus::notStarted(),
        assignedAt: null,
        size: null,
        createdAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        updatedAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        deletedAt: null,
    );
}

describe('AssignToUserHandler', function () {
    it('assigns the product and returns a ProductResource', function () {
        $product = makeAssignableProduct();

        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        $service = new ProductAssignmentService();

        $repo->shouldReceive('findById')->once()->with(42)->andReturn($product);
        $repo->shouldReceive('save')->once()->with($product)->andReturnUsing(static fn (Product $saved): Product => $saved);

        $handler = new AssignToUserHandler($repo, $service);

        $result = $handler->handle(new AssignToUserCommand(productId: 42, userId: 7));

        expect($result->id)->toBe(42)
            ->and($result->userId)->toBe(7);
    });

    it('throws NotFound when the product does not exist', function () {
        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        $service = new ProductAssignmentService();

        $repo->shouldReceive('findById')->once()->with(999)->andReturn(null);
        $repo->shouldNotReceive('save');

        $handler = new AssignToUserHandler($repo, $service);

        expect(fn () => $handler->handle(new AssignToUserCommand(productId: 999, userId: 7)))
            ->toThrow(NotFound::class);
    });
});
