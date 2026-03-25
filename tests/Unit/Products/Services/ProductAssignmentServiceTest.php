<?php

declare(strict_types=1);

use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Events\ProductAssigned;
use Src\Products\Domain\Services\ProductAssignmentService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;

describe('ProductAssignmentService', function () {

    beforeEach(function () {
        $this->service = new ProductAssignmentService();
    });

    it('assigns product to user and records event', function () {
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

        $this->service->assign($product, 42);

        expect($product->userId)->toBe(42)
            ->and($product->assignedAt)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($product->hasEvents())->toBeTrue();

        $events = $product->releaseEvents();
        expect($events)->toHaveCount(1);
        expect($events[0])->toBeInstanceOf(ProductAssigned::class);
        expect($events[0]->productId)->toBe(1);
        expect($events[0]->userId)->toBe(42);
    });

    it('reassigns product to different user', function () {
        $product = Product::fromPersistence(
            id: 2,
            productTypeId: 1,
            userId: 10,
            model: 'GPT-4',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: null,
            usage: 0,
            name: 'Test',
            description: null,
            active: false,
            configurationStatus: ConfigurationStatus::notStarted(),
            assignedAt: new DateTimeImmutable('2024-01-01'),
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $this->service->assign($product, 20);

        expect($product->userId)->toBe(20)
            ->and($product->assignedAt)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($product->assignedAt->format('Y-m-d'))->not->toBe('2024-01-01');
    });
});
