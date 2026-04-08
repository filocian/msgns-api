<?php

declare(strict_types=1);

use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;

function virginProduct(): Product
{
    return Product::fromPersistence(
        id: 1,
        productTypeId: 1,
        userId: null,
        model: 'google',
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
}

describe('Product::isVirgin()', function () {
    it('returns true when userId=null, targetUrl=null, status=NOT_STARTED', function () {
        $product = virginProduct();

        expect($product->isVirgin())->toBeTrue();
    });

    it('returns false when userId is set', function () {
        $product = Product::fromPersistence(
            id: 1,
            productTypeId: 1,
            userId: 1,
            model: 'google',
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

        expect($product->isVirgin())->toBeFalse();
    });

    it('returns false when targetUrl is set', function () {
        $product = Product::fromPersistence(
            id: 1,
            productTypeId: 1,
            userId: null,
            model: 'google',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: 'x',
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

        expect($product->isVirgin())->toBeFalse();
    });

    it('returns true even when active is true (active field is not part of the predicate)', function () {
        $product = Product::fromPersistence(
            id: 1,
            productTypeId: 1,
            userId: null,
            model: 'google',
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

        expect($product->isVirgin())->toBeTrue();
    });

    it('returns false when status is ASSIGNED', function () {
        $product = Product::fromPersistence(
            id: 1,
            productTypeId: 1,
            userId: null,
            model: 'google',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: null,
            usage: 0,
            name: 'Test',
            description: null,
            active: false,
            configurationStatus: ConfigurationStatus::from(ConfigurationStatus::ASSIGNED),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        expect($product->isVirgin())->toBeFalse();
    });
});

describe('Product::isDisabled()', function () {
    it('returns true when active=false and product is not virgin', function () {
        $product = Product::fromPersistence(
            id: 1,
            productTypeId: 1,
            userId: 1,
            model: 'google',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: null,
            usage: 0,
            name: 'Test',
            description: null,
            active: false,
            configurationStatus: ConfigurationStatus::from(ConfigurationStatus::COMPLETED),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        expect($product->isDisabled())->toBeTrue();
    });

    it('returns false when active=true', function () {
        $product = Product::fromPersistence(
            id: 1,
            productTypeId: 1,
            userId: 1,
            model: 'google',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: null,
            usage: 0,
            name: 'Test',
            description: null,
            active: true,
            configurationStatus: ConfigurationStatus::from(ConfigurationStatus::COMPLETED),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        expect($product->isDisabled())->toBeFalse();
    });

    it('returns false when active=false but product is virgin', function () {
        $product = virginProduct(); // active=false, userId=null, targetUrl=null, NOT_STARTED

        expect($product->isDisabled())->toBeFalse();
    });
});

describe('Product::isMisconfigured()', function () {
    it('returns true for ASSIGNED status', function () {
        $product = Product::fromPersistence(
            id: 1,
            productTypeId: 1,
            userId: 1,
            model: 'google',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: null,
            usage: 0,
            name: 'Test',
            description: null,
            active: true,
            configurationStatus: ConfigurationStatus::from(ConfigurationStatus::ASSIGNED),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        expect($product->isMisconfigured())->toBeTrue();
    });

    it('returns true for TARGET_SET status', function () {
        $product = Product::fromPersistence(
            id: 1,
            productTypeId: 1,
            userId: 1,
            model: 'google',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: 'https://google.com',
            usage: 0,
            name: 'Test',
            description: null,
            active: true,
            configurationStatus: ConfigurationStatus::from(ConfigurationStatus::TARGET_SET),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        expect($product->isMisconfigured())->toBeTrue();
    });

    it('returns true for BUSINESS_SET status', function () {
        $product = Product::fromPersistence(
            id: 1,
            productTypeId: 1,
            userId: 1,
            model: 'google',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: 'https://google.com',
            usage: 0,
            name: 'Test',
            description: null,
            active: true,
            configurationStatus: ConfigurationStatus::from(ConfigurationStatus::BUSINESS_SET),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        expect($product->isMisconfigured())->toBeTrue();
    });

    it('returns false for NOT_STARTED status', function () {
        $product = virginProduct();

        expect($product->isMisconfigured())->toBeFalse();
    });

    it('returns false for COMPLETED status', function () {
        $product = Product::fromPersistence(
            id: 1,
            productTypeId: 1,
            userId: 1,
            model: 'google',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: 'https://google.com',
            usage: 0,
            name: 'Test',
            description: null,
            active: true,
            configurationStatus: ConfigurationStatus::from(ConfigurationStatus::COMPLETED),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        expect($product->isMisconfigured())->toBeFalse();
    });
});

describe('Product::canBypassMisconfiguration()', function () {
    it('returns true when userId and targetUrl are both set', function () {
        $product = Product::fromPersistence(
            id: 1,
            productTypeId: 1,
            userId: 1,
            model: 'google',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: 'x',
            usage: 0,
            name: 'Test',
            description: null,
            active: true,
            configurationStatus: ConfigurationStatus::from(ConfigurationStatus::TARGET_SET),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        expect($product->canBypassMisconfiguration())->toBeTrue();
    });

    it('returns false when userId is null', function () {
        $product = Product::fromPersistence(
            id: 1,
            productTypeId: 1,
            userId: null,
            model: 'google',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: 'x',
            usage: 0,
            name: 'Test',
            description: null,
            active: true,
            configurationStatus: ConfigurationStatus::from(ConfigurationStatus::TARGET_SET),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        expect($product->canBypassMisconfiguration())->toBeFalse();
    });

    it('returns false when targetUrl is null', function () {
        $product = Product::fromPersistence(
            id: 1,
            productTypeId: 1,
            userId: 1,
            model: 'google',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: null,
            usage: 0,
            name: 'Test',
            description: null,
            active: true,
            configurationStatus: ConfigurationStatus::from(ConfigurationStatus::ASSIGNED),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        expect($product->canBypassMisconfiguration())->toBeFalse();
    });

    it('returns false when both userId and targetUrl are null', function () {
        $product = Product::fromPersistence(
            id: 1,
            productTypeId: 1,
            userId: null,
            model: 'google',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: null,
            usage: 0,
            name: 'Test',
            description: null,
            active: true,
            configurationStatus: ConfigurationStatus::from(ConfigurationStatus::ASSIGNED),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        expect($product->canBypassMisconfiguration())->toBeFalse();
    });
});
