<?php

declare(strict_types=1);

use Src\Products\Domain\Entities\ProductBusiness;

describe('ProductBusiness Entity', function () {

    // ─── create() factory ───────────────────────────────────────────────────

    it('creates a new product business with correct values', function () {
        $business = ProductBusiness::create(
            productId: 1,
            userId: 10,
            notABusiness: false,
            name: 'My Business',
            types: ['restaurant', 'cafe'],
            placeTypes: ['food', 'drink'],
            size: 'medium',
        );

        expect($business->id)->toBe(0)
            ->and($business->productId)->toBe(1)
            ->and($business->userId)->toBe(10)
            ->and($business->notABusiness)->toBeFalse()
            ->and($business->name)->toBe('My Business')
            ->and($business->types)->toBe(['restaurant', 'cafe'])
            ->and($business->placeTypes)->toBe(['food', 'drink'])
            ->and($business->size)->toBe('medium')
            ->and($business->createdAt)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($business->updatedAt)->toBeInstanceOf(DateTimeImmutable::class);
    });

    it('creates with default values', function () {
        $business = ProductBusiness::create(
            productId: 2,
            userId: 20,
        );

        expect($business->id)->toBe(0)
            ->and($business->productId)->toBe(2)
            ->and($business->userId)->toBe(20)
            ->and($business->notABusiness)->toBeFalse()
            ->and($business->name)->toBeNull()
            ->and($business->types)->toBe([])
            ->and($business->placeTypes)->toBeNull()
            ->and($business->size)->toBeNull();
    });

    // ─── fromPersistence() factory ───────────────────────────────────────────

    it('rehydrates from persistence with all fields', function () {
        $createdAt = new DateTimeImmutable('2024-01-01T00:00:00Z');
        $updatedAt = new DateTimeImmutable('2024-06-01T12:00:00Z');

        $business = ProductBusiness::fromPersistence(
            id: 42,
            productId: 5,
            userId: 15,
            notABusiness: true,
            name: 'Existing Business',
            types: ['shop'],
            placeTypes: ['store'],
            size: 'small',
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );

        expect($business->id)->toBe(42)
            ->and($business->productId)->toBe(5)
            ->and($business->userId)->toBe(15)
            ->and($business->notABusiness)->toBeTrue()
            ->and($business->name)->toBe('Existing Business')
            ->and($business->types)->toBe(['shop'])
            ->and($business->placeTypes)->toBe(['store'])
            ->and($business->size)->toBe('small')
            ->and($business->createdAt)->toBe($createdAt)
            ->and($business->updatedAt)->toBe($updatedAt);
    });
});
