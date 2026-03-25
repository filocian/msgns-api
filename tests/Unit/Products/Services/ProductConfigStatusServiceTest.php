<?php

declare(strict_types=1);

use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Services\ProductConfigStatusService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;

describe('ProductConfigStatusService', function () {

    it('performs valid forward transition', function () {
        $service = new ProductConfigStatusService();

        $product = Product::fromPersistence(
            id: 1,
            productTypeId: 1,
            userId: null,
            model: 'GPT-4',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: null,
            usage: 0,
            name: 'Test',
            description: null,
            active: false,
            configurationStatus: ConfigurationStatus::from('not-started'),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $service->transition($product, 'assigned');

        expect($product->configurationStatus->value)->toBe('assigned');
    });

    it('performs multiple forward transitions', function () {
        $service = new ProductConfigStatusService();

        $product = Product::fromPersistence(
            id: 2,
            productTypeId: 1,
            userId: null,
            model: 'GPT-4',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: null,
            usage: 0,
            name: 'Test',
            description: null,
            active: false,
            configurationStatus: ConfigurationStatus::from('not-started'),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $service->transition($product, 'assigned');
        expect($product->configurationStatus->value)->toBe('assigned');

        $service->transition($product, 'target-set');
        expect($product->configurationStatus->value)->toBe('target-set');

        $service->transition($product, 'business-set');
        expect($product->configurationStatus->value)->toBe('business-set');

        $service->transition($product, 'completed');
        expect($product->configurationStatus->value)->toBe('completed');
    });

    it('throws on invalid backward transition', function () {
        $service = new ProductConfigStatusService();

        $product = Product::fromPersistence(
            id: 3,
            productTypeId: 1,
            userId: null,
            model: 'GPT-4',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: null,
            usage: 0,
            name: 'Test',
            description: null,
            active: false,
            configurationStatus: ConfigurationStatus::from('assigned'),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $service->transition($product, 'not-started');
    })->throws(\InvalidArgumentException::class, 'Invalid configuration status transition');

    it('throws on same status transition', function () {
        $service = new ProductConfigStatusService();

        $product = Product::fromPersistence(
            id: 4,
            productTypeId: 1,
            userId: null,
            model: 'GPT-4',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: null,
            usage: 0,
            name: 'Test',
            description: null,
            active: false,
            configurationStatus: ConfigurationStatus::from('target-set'),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $service->transition($product, 'target-set');
    })->throws(\InvalidArgumentException::class, 'Invalid configuration status transition');
});
