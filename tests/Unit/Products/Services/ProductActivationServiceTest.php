<?php

declare(strict_types=1);

use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Events\ProductActivated;
use Src\Products\Domain\Events\ProductDeactivated;
use Src\Products\Domain\Services\ProductActivationService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;

describe('ProductActivationService', function () {

    it('activates an inactive product and records event', function () {
        $service = new ProductActivationService();

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
            configurationStatus: ConfigurationStatus::notStarted(),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $service->activate($product);

        expect($product->active)->toBeTrue();
        expect($product->hasEvents())->toBeTrue();

        $events = $product->releaseEvents();
        expect($events)->toHaveCount(1);
        expect($events[0])->toBeInstanceOf(ProductActivated::class);
        // @phpstan-ignore-next-line
        expect($events[0]->productId)->toBe(1);
    });

    it('does not record event when product is already active', function () {
        $service = new ProductActivationService();

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
            active: true,
            configurationStatus: ConfigurationStatus::notStarted(),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $service->activate($product);

        expect($product->active)->toBeTrue()
            ->and($product->hasEvents())->toBeFalse();
    });

    it('deactivates an active product and records event', function () {
        $service = new ProductActivationService();

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
            active: true,
            configurationStatus: ConfigurationStatus::notStarted(),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $service->deactivate($product);

        expect($product->active)->toBeFalse();
        expect($product->hasEvents())->toBeTrue();

        $events = $product->releaseEvents();
        expect($events)->toHaveCount(1);
        expect($events[0])->toBeInstanceOf(ProductDeactivated::class);
        // @phpstan-ignore-next-line
        expect($events[0]->productId)->toBe(3);
    });

    it('does not record event when product is already inactive', function () {
        $service = new ProductActivationService();

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
            configurationStatus: ConfigurationStatus::notStarted(),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $service->deactivate($product);

        expect($product->active)->toBeFalse()
            ->and($product->hasEvents())->toBeFalse();
    });
});
