<?php

declare(strict_types=1);

use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Events\ProductsPaired;
use Src\Products\Domain\Services\ProductGroupingService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Errors\ValidationFailed;

describe('ProductGroupingService', function () {

    beforeEach(function () {
        $this->productRepo = Mockery::mock(ProductRepositoryPort::class);
        $this->service = new ProductGroupingService($this->productRepo);
    });

    it('links two products and records event', function () {
        $primary = Product::fromPersistence(
            id: 1,
            productTypeId: 1,
            userId: 10,
            model: 'GPT-4',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: null,
            usage: 0,
            name: 'Primary',
            description: null,
            active: false,
            configurationStatus: ConfigurationStatus::notStarted(),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $secondary = Product::fromPersistence(
            id: 2,
            productTypeId: 1,
            userId: 10,
            model: 'GPT-4',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: null,
            usage: 0,
            name: 'Secondary',
            description: null,
            active: false,
            configurationStatus: ConfigurationStatus::notStarted(),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $this->service->link($primary, $secondary);

        expect($primary->linkedToProductId)->toBe(2);
        expect($primary->hasEvents())->toBeTrue();

        $events = $primary->releaseEvents();
        expect($events)->toHaveCount(1);
        expect($events[0])->toBeInstanceOf(ProductsPaired::class);
        expect($events[0]->mainProductId)->toBe(1);
        expect($events[0]->childProductId)->toBe(2);
    });

    it('throws when product types differ', function () {
        $primary = Product::fromPersistence(
            id: 1,
            productTypeId: 1,
            userId: 10,
            model: 'GPT-4',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: null,
            usage: 0,
            name: 'Primary',
            description: null,
            active: false,
            configurationStatus: ConfigurationStatus::notStarted(),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $secondary = Product::fromPersistence(
            id: 2,
            productTypeId: 2, // Different type
            userId: 10,
            model: 'Claude-3',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: null,
            usage: 0,
            name: 'Secondary',
            description: null,
            active: false,
            configurationStatus: ConfigurationStatus::notStarted(),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $this->service->link($primary, $secondary);
    })->throws(ValidationFailed::class, 'products_must_have_same_type');

    it('throws when users differ', function () {
        $primary = Product::fromPersistence(
            id: 1,
            productTypeId: 1,
            userId: 10,
            model: 'GPT-4',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: null,
            usage: 0,
            name: 'Primary',
            description: null,
            active: false,
            configurationStatus: ConfigurationStatus::notStarted(),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $secondary = Product::fromPersistence(
            id: 2,
            productTypeId: 1,
            userId: 20, // Different user
            model: 'GPT-4',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: null,
            usage: 0,
            name: 'Secondary',
            description: null,
            active: false,
            configurationStatus: ConfigurationStatus::notStarted(),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $this->service->link($primary, $secondary);
    })->throws(ValidationFailed::class, 'products_must_have_same_user');

    it('throws when primary is already linked', function () {
        $primary = Product::fromPersistence(
            id: 1,
            productTypeId: 1,
            userId: 10,
            model: 'GPT-4',
            linkedToProductId: 99, // Already linked
            password: 'pass',
            targetUrl: null,
            usage: 0,
            name: 'Primary',
            description: null,
            active: false,
            configurationStatus: ConfigurationStatus::notStarted(),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $secondary = Product::fromPersistence(
            id: 2,
            productTypeId: 1,
            userId: 10,
            model: 'GPT-4',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: null,
            usage: 0,
            name: 'Secondary',
            description: null,
            active: false,
            configurationStatus: ConfigurationStatus::notStarted(),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $this->service->link($primary, $secondary);
    })->throws(ValidationFailed::class, 'primary_product_already_linked');

    it('throws when secondary is already linked', function () {
        $primary = Product::fromPersistence(
            id: 1,
            productTypeId: 1,
            userId: 10,
            model: 'GPT-4',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: null,
            usage: 0,
            name: 'Primary',
            description: null,
            active: false,
            configurationStatus: ConfigurationStatus::notStarted(),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $secondary = Product::fromPersistence(
            id: 2,
            productTypeId: 1,
            userId: 10,
            model: 'GPT-4',
            linkedToProductId: 88, // Already linked
            password: 'pass',
            targetUrl: null,
            usage: 0,
            name: 'Secondary',
            description: null,
            active: false,
            configurationStatus: ConfigurationStatus::notStarted(),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $this->service->link($primary, $secondary);
    })->throws(ValidationFailed::class, 'secondary_product_already_linked');

    it('unlinks a product', function () {
        $product = Product::fromPersistence(
            id: 1,
            productTypeId: 1,
            userId: 10,
            model: 'GPT-4',
            linkedToProductId: 5,
            password: 'pass',
            targetUrl: null,
            usage: 0,
            name: 'Product',
            description: null,
            active: false,
            configurationStatus: ConfigurationStatus::notStarted(),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $this->service->unlink($product);

        expect($product->linkedToProductId)->toBeNull();
    });
});
