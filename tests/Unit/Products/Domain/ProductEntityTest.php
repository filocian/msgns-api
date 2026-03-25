<?php

declare(strict_types=1);

use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;

describe('Product Entity', function () {

    // ─── create() factory ───────────────────────────────────────────────────

    it('creates a new product with correct defaults', function () {
        $product = Product::create(
            productTypeId: 1,
            model: 'GPT-4',
            password: 'secret123',
        );

        expect($product->id)->toBe(0)
            ->and($product->productTypeId)->toBe(1)
            ->and($product->userId)->toBeNull()
            ->and($product->model->value)->toBe('GPT-4')
            ->and($product->linkedToProductId)->toBeNull()
            ->and($product->password->value)->toBe('secret123')
            ->and($product->targetUrl)->toBeNull()
            ->and($product->usage)->toBe(0)
            ->and($product->name->value)->toBe('GPT-4') // Initial name is model; updated to "model (id)" after persist
            ->and($product->description)->toBeNull()
            ->and($product->active)->toBeFalse()
            ->and($product->configurationStatus->value)->toBe('not-started')
            ->and($product->assignedAt)->toBeNull()
            ->and($product->size)->toBeNull()
            ->and($product->createdAt)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($product->updatedAt)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($product->deletedAt)->toBeNull();
    });

    // ─── fromPersistence() factory ───────────────────────────────────────────

    it('rehydrates from persistence with all fields', function () {
        $createdAt = new DateTimeImmutable('2024-01-01T00:00:00Z');
        $updatedAt = new DateTimeImmutable('2024-06-01T12:00:00Z');
        $assignedAt = new DateTimeImmutable('2024-05-01T10:00:00Z');
        $deletedAt = new DateTimeImmutable('2024-07-01T14:00:00Z');

        $product = Product::fromPersistence(
            id: 42,
            productTypeId: 1,
            userId: 10,
            model: 'Claude-3',
            linkedToProductId: 5,
            password: 'hashedpassword',
            targetUrl: 'https://example.com',
            usage: 100,
            name: 'My Product',
            description: 'A test product',
            active: true,
            configurationStatus: ConfigurationStatus::from('target-set'),
            assignedAt: $assignedAt,
            size: 'large',
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            deletedAt: $deletedAt,
        );

        expect($product->id)->toBe(42)
            ->and($product->productTypeId)->toBe(1)
            ->and($product->userId)->toBe(10)
            ->and($product->model->value)->toBe('Claude-3')
            ->and($product->linkedToProductId)->toBe(5)
            ->and($product->password->value)->toBe('hashedpassword')
            ->and($product->targetUrl)->toBe('https://example.com')
            ->and($product->usage)->toBe(100)
            ->and($product->name->value)->toBe('My Product')
            ->and($product->description?->value)->toBe('A test product')
            ->and($product->active)->toBeTrue()
            ->and($product->configurationStatus->value)->toBe('target-set')
            ->and($product->assignedAt)->toBe($assignedAt)
            ->and($product->size)->toBe('large')
            ->and($product->createdAt)->toBe($createdAt)
            ->and($product->updatedAt)->toBe($updatedAt)
            ->and($product->deletedAt)->toBe($deletedAt);
    });

    // ─── generateDefaultName() ───────────────────────────────────────────────

    it('generates default name with model and id', function () {
        $product = Product::fromPersistence(
            id: 123,
            productTypeId: 1,
            userId: null,
            model: 'GPT-4',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: null,
            usage: 0,
            name: 'Old Name', // Start with any name
            description: null,
            active: false,
            configurationStatus: ConfigurationStatus::notStarted(),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $product->generateDefaultName();

        expect($product->name->value)->toBe('GPT-4 (123)');
    });

    // ─── Event recording ─────────────────────────────────────────────────────

    it('records and releases events', function () {
        $product = Product::create(
            productTypeId: 1,
            model: 'Model-X',
            password: 'pass',
        );

        // Record a mock event
        $event = new class {
            public function eventName(): string { return 'test.event'; }
        };
        $product->recordEvent($event);

        expect($product->hasEvents())->toBeTrue();

        $released = $product->releaseEvents();

        expect($released)->toHaveCount(1)
            ->and($product->hasEvents())->toBeFalse();
    });
});
