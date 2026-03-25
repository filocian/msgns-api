<?php

declare(strict_types=1);

use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Events\ProductRenamed;
use Src\Products\Domain\Services\ProductRenameService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Errors\ValidationFailed;

describe('ProductRenameService', function () {

    beforeEach(function () {
        $this->service = new ProductRenameService();
    });

    it('renames product and records event', function () {
        $product = Product::fromPersistence(
            id: 1,
            productTypeId: 1,
            userId: null,
            model: 'GPT-4',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: null,
            usage: 0,
            name: 'Old Name',
            description: null,
            active: false,
            configurationStatus: ConfigurationStatus::notStarted(),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $this->service->rename($product, 'New Name');

        expect($product->name->value)->toBe('New Name');
        expect($product->hasEvents())->toBeTrue();

        $events = $product->releaseEvents();
        expect($events)->toHaveCount(1);
        expect($events[0])->toBeInstanceOf(ProductRenamed::class);
        expect($events[0]->productId)->toBe(1);
        expect($events[0]->name)->toBe('New Name');
    });

    it('throws on empty name', function () {
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
            configurationStatus: ConfigurationStatus::notStarted(),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $this->service->rename($product, '');
    })->throws(ValidationFailed::class, 'product_name_empty');

    it('throws on name that is too long', function () {
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
            configurationStatus: ConfigurationStatus::notStarted(),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $longName = str_repeat('a', \Src\Products\Domain\ValueObjects\ProductName::MAX_LENGTH + 1);
        $this->service->rename($product, $longName);
    })->throws(ValidationFailed::class, 'product_name_too_long');
});
