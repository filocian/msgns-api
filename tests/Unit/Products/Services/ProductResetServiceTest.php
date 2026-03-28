<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Ports\ProductTypeRepository;
use Src\Products\Domain\Ports\ProductUsagePort;
use Src\Products\Domain\Services\ProductResetService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;

describe('ProductResetService', function () {

    it('resets product and records event', function () {
        /** @var MockInterface&ProductTypeRepository $typeRepository */
        $typeRepository = Mockery::mock(ProductTypeRepository::class);
        /** @var MockInterface&ProductUsagePort $usagePort */
        $usagePort = Mockery::mock(ProductUsagePort::class);
        /** @var MockInterface&LoggerInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);

        $typeRepository->shouldReceive('findById')->once()->with(1)->andReturn(null); // @phpstan-ignore-line
        $usagePort->shouldReceive('deleteProductUsage')->once()->with(1); // @phpstan-ignore-line
        $logger->shouldNotReceive('error');

        $service = new ProductResetService($typeRepository, $usagePort, $logger);

        $product = Product::fromPersistence(
            id: 1,
            productTypeId: 1,
            userId: 10,
            model: 'GPT-4',
            linkedToProductId: 5,
            password: 'pass',
            targetUrl: 'https://example.com',
            usage: 100,
            name: 'Custom Name',
            description: 'A description',
            active: true,
            configurationStatus: ConfigurationStatus::from('target-set'),
            assignedAt: new DateTimeImmutable('2024-01-01'),
            size: 'large',
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $service->reset($product);

        expect($product->userId)->toBeNull()
            ->and($product->targetUrl)->toBeNull()
            ->and($product->linkedToProductId)->toBeNull()
            ->and($product->assignedAt)->toBeNull()
            ->and($product->usage)->toBe(0)
            ->and($product->configurationStatus->value)->toBe('not-started')
            ->and($product->name->value)->toBe('GPT-4 (1)');
    });

    it('calls usage port to delete usage data', function () {
        /** @var MockInterface&ProductTypeRepository $typeRepository */
        $typeRepository = Mockery::mock(ProductTypeRepository::class);
        /** @var MockInterface&ProductUsagePort $usagePort */
        $usagePort = Mockery::mock(ProductUsagePort::class);
        /** @var MockInterface&LoggerInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);

        $typeRepository->shouldReceive('findById')->once()->with(1)->andReturn(null); // @phpstan-ignore-line
        $usagePort->shouldReceive('deleteProductUsage')->once()->with(42); // @phpstan-ignore-line
        $logger->shouldNotReceive('error');

        $service = new ProductResetService($typeRepository, $usagePort, $logger);

        $product = Product::fromPersistence(
            id: 42,
            productTypeId: 1,
            userId: null,
            model: 'Claude-3',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: null,
            usage: 0,
            name: 'Test',
            description: null,
            active: false,
            configurationStatus: ConfigurationStatus::notStarted(),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $service->reset($product);
    });
});
