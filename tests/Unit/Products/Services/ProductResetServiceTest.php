<?php

declare(strict_types=1);

use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Events\ProductReset;
use Src\Products\Domain\Ports\ProductUsagePort;
use Src\Products\Domain\Services\ProductResetService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Mockery;

describe('ProductResetService', function () {

    beforeEach(function () {
        $this->usagePort = Mockery::mock(ProductUsagePort::class);
        $this->service = new ProductResetService($this->usagePort);
    });

    it('resets product and records event', function () {
        $this->usagePort->shouldReceive('deleteUsage')->once()->with(1);

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

        $this->service->reset($product);

        expect($product->userId)->toBeNull()
            ->and($product->targetUrl)->toBeNull()
            ->and($product->linkedToProductId)->toBeNull()
            ->and($product->assignedAt)->toBeNull()
            ->and($product->usage)->toBe(0)
            ->and($product->configurationStatus->value)->toBe('not-started')
            ->and($product->name->value)->toBe('GPT-4 (1)');
    });

    it('calls usage port to delete usage data', function () {
        $this->usagePort->shouldReceive('deleteUsage')->once()->with(42);

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

        $this->service->reset($product);
    });
});
