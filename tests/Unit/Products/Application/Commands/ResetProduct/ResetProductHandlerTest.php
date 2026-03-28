<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Src\Products\Application\Commands\ResetProduct\ResetProductCommand;
use Src\Products\Application\Commands\ResetProduct\ResetProductHandler;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Entities\ProductType;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Ports\ProductTypeRepository;
use Src\Products\Domain\Ports\ProductUsagePort;
use Src\Products\Domain\Services\ProductResetService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Errors\NotFound;

function makeHandlerResetProduct(int $id = 42): Product
{
    return Product::fromPersistence(
        id: $id,
        productTypeId: 5,
        userId: 10,
        model: 'S-GG-XX-RC',
        linkedToProductId: 99,
        password: 'secret',
        targetUrl: 'https://example.com',
        usage: 12,
        name: 'Old Name',
        description: null,
        active: true,
        configurationStatus: ConfigurationStatus::from('target-set'),
        assignedAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        size: null,
        createdAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        updatedAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        deletedAt: null,
    );
}

function makeHandlerResetProductType(int $id, string $code = 'S-GG-XX-RC'): ProductType
{
    return ProductType::fromPersistence(
        id: $id,
        code: $code,
        name: 'Type ' . $code,
        primaryModel: 'S-GG-XX-RC',
        secondaryModel: null,
        createdAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        updatedAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
    );
}

describe('ResetProductHandler', function () {
    it('resets the product through ProductResetService and returns a ProductResource', function () {
        $product = makeHandlerResetProduct();

        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&ProductTypeRepository $typeRepository */
        $typeRepository = Mockery::mock(ProductTypeRepository::class);
        /** @var MockInterface&ProductUsagePort $usagePort */
        $usagePort = Mockery::mock(ProductUsagePort::class);
        /** @var MockInterface&LoggerInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);

        $resetService = new ProductResetService($typeRepository, $usagePort, $logger);

        $repo->shouldReceive('findById')->once()->with(42)->andReturn($product);
        $typeRepository->shouldReceive('findById')->once()->with(5)->andReturn(makeHandlerResetProductType(5));
        $usagePort->shouldReceive('deleteProductUsage')->once()->with(42);
        $logger->shouldNotReceive('error');
        $repo->shouldReceive('save')->once()->with($product)->andReturnUsing(static fn (Product $saved): Product => $saved);

        $handler = new ResetProductHandler($repo, $resetService);

        $result = $handler->handle(new ResetProductCommand(productId: 42));

        expect($result->id)->toBe(42);
    });

    it('throws NotFound when the product does not exist', function () {
        /** @var MockInterface&ProductRepositoryPort $repo */
        $repo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&ProductTypeRepository $typeRepository */
        $typeRepository = Mockery::mock(ProductTypeRepository::class);
        /** @var MockInterface&ProductUsagePort $usagePort */
        $usagePort = Mockery::mock(ProductUsagePort::class);
        /** @var MockInterface&LoggerInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);

        $resetService = new ProductResetService($typeRepository, $usagePort, $logger);

        $repo->shouldReceive('findById')->once()->with(999)->andReturn(null);
        $typeRepository->shouldNotReceive('findById');
        $usagePort->shouldNotReceive('deleteProductUsage');
        $logger->shouldNotReceive('error');
        $repo->shouldNotReceive('save');

        $handler = new ResetProductHandler($repo, $resetService);

        expect(fn () => $handler->handle(new ResetProductCommand(productId: 999)))
            ->toThrow(NotFound::class);
    });

    it('does not depend on ProductUsagePort directly', function () {
        $constructor = new ReflectionMethod(ResetProductHandler::class, '__construct');

        $parameterTypes = array_map(
            static fn (ReflectionParameter $parameter): string => $parameter->getType() instanceof ReflectionNamedType
                ? $parameter->getType()->getName()
                : '',
            $constructor->getParameters(),
        );

        expect($parameterTypes)->not->toContain(ProductUsagePort::class);
    });
});
