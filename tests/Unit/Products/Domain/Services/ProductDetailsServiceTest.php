<?php

declare(strict_types=1);

use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Services\ProductDetailsService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Errors\ValidationFailed;

function makeDetailsServiceProduct(
    string $name = 'Before Name',
    ?string $description = 'Before description',
): Product {
    return Product::fromPersistence(
        id: 10,
        productTypeId: 3,
        userId: 7,
        model: 'ModelA',
        linkedToProductId: null,
        password: 'secret',
        targetUrl: null,
        usage: 0,
        name: $name,
        description: $description,
        active: true,
        configurationStatus: ConfigurationStatus::from(ConfigurationStatus::ASSIGNED),
        assignedAt: null,
        size: null,
        createdAt: new DateTimeImmutable('2025-01-01T00:00:00+00:00'),
        updatedAt: new DateTimeImmutable('2025-01-01T00:00:00+00:00'),
        deletedAt: null,
    );
}

describe('ProductDetailsService', function () {
    it('updates only name and preserves existing description', function (): void {
        $service = new ProductDetailsService();
        $product = makeDetailsServiceProduct('Before Name', 'Preserved description');

        $service->apply(
            product: $product,
            hasName: true,
            name: 'After Name',
            hasDescription: false,
            description: null,
        );

        expect($product->name->value)->toBe('After Name')
            ->and($product->description?->value)->toBe('Preserved description');
    });

    it('updates only description and preserves existing name', function (): void {
        $service = new ProductDetailsService();
        $product = makeDetailsServiceProduct('Preserved Name', 'Before description');

        $service->apply(
            product: $product,
            hasName: false,
            name: null,
            hasDescription: true,
            description: 'After description',
        );

        expect($product->name->value)->toBe('Preserved Name')
            ->and($product->description?->value)->toBe('After description');
    });

    it('updates both name and description together', function (): void {
        $service = new ProductDetailsService();
        $product = makeDetailsServiceProduct();

        $service->apply(
            product: $product,
            hasName: true,
            name: 'After Name',
            hasDescription: true,
            description: 'After description',
        );

        expect($product->name->value)->toBe('After Name')
            ->and($product->description?->value)->toBe('After description');
    });

    it('clears description when description is explicitly null', function (): void {
        $service = new ProductDetailsService();
        $product = makeDetailsServiceProduct('Name', 'Description to clear');

        $service->apply(
            product: $product,
            hasName: false,
            name: null,
            hasDescription: true,
            description: null,
        );

        expect($product->description?->value)->toBeNull();
    });

    it('rejects empty payload when no updatable field is present', function (): void {
        $service = new ProductDetailsService();
        $product = makeDetailsServiceProduct();

        $service->apply(
            product: $product,
            hasName: false,
            name: null,
            hasDescription: false,
            description: null,
        );
    })->throws(ValidationFailed::class, 'product_details_empty_payload');
});
