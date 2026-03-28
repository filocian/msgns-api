<?php

declare(strict_types=1);

use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Events\ProductTargetUrlSet;
use Src\Products\Domain\Services\ProductConfigurationService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Errors\ValidationFailed;

describe('ProductConfigurationService', function () {

    it('sets target URL and records event', function () {
        $service = new ProductConfigurationService();

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

        $service->setTargetUrl($product, 'https://example.com');

        expect($product->targetUrl)->toBe('https://example.com');
        expect($product->hasEvents())->toBeTrue();

        $events = $product->releaseEvents();
        expect($events)->toHaveCount(1);
        expect($events[0])->toBeInstanceOf(ProductTargetUrlSet::class);
        // @phpstan-ignore-next-line
        expect($events[0]->productId)->toBe(1);
        // @phpstan-ignore-next-line
        expect($events[0]->targetUrl)->toBe('https://example.com');
    });

    it('throws on invalid URL', function () {
        $service = new ProductConfigurationService();

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

        $service->setTargetUrl($product, 'not-a-url');
    })->throws(ValidationFailed::class, 'target_url_invalid');

    it('updates existing target URL', function () {
        $service = new ProductConfigurationService();

        $product = Product::fromPersistence(
            id: 3,
            productTypeId: 1,
            userId: null,
            model: 'GPT-4',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: 'https://old.example.com',
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

        $service->setTargetUrl($product, 'https://new.example.com');

        expect($product->targetUrl)->toBe('https://new.example.com');
    });
});
