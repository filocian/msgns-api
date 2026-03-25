<?php

declare(strict_types=1);

use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Services\ProductCloneService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;

describe('ProductCloneService', function () {

    it('copies target URL from source to target', function () {
        $service = new ProductCloneService();

        $source = Product::fromPersistence(
            id: 1,
            productTypeId: 1,
            userId: 10,
            model: 'GPT-4',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: 'https://source.example.com',
            usage: 0,
            name: 'Source',
            description: null,
            active: true,
            configurationStatus: ConfigurationStatus::notStarted(),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $target = Product::fromPersistence(
            id: 2,
            productTypeId: 1,
            userId: 10,
            model: 'GPT-4',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: null,
            usage: 0,
            name: 'Target',
            description: null,
            active: true,
            configurationStatus: ConfigurationStatus::notStarted(),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $service->clone($source, $target);

        expect($target->targetUrl)->toBe('https://source.example.com');
    });

    it('copies configuration status when target can advance', function () {
        $service = new ProductCloneService();

        $source = Product::fromPersistence(
            id: 1,
            productTypeId: 1,
            userId: 10,
            model: 'GPT-4',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: 'https://source.example.com',
            usage: 0,
            name: 'Source',
            description: null,
            active: true,
            configurationStatus: ConfigurationStatus::from(ConfigurationStatus::BUSINESS_SET),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $target = Product::fromPersistence(
            id: 2,
            productTypeId: 1,
            userId: 10,
            model: 'GPT-4',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: null,
            usage: 0,
            name: 'Target',
            description: null,
            active: true,
            configurationStatus: ConfigurationStatus::notStarted(),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $service->clone($source, $target);

        // Target can advance to business-set, so status should be copied
        expect($target->configurationStatus->value)->toBe(ConfigurationStatus::BUSINESS_SET);
    });

    it('does NOT record domain events', function () {
        $service = new ProductCloneService();

        $source = Product::fromPersistence(
            id: 1,
            productTypeId: 1,
            userId: 10,
            model: 'GPT-4',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: 'https://source.example.com',
            usage: 0,
            name: 'Source',
            description: null,
            active: true,
            configurationStatus: ConfigurationStatus::notStarted(),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $target = Product::fromPersistence(
            id: 2,
            productTypeId: 1,
            userId: 10,
            model: 'GPT-4',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: null,
            usage: 0,
            name: 'Target',
            description: null,
            active: true,
            configurationStatus: ConfigurationStatus::notStarted(),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $service->clone($source, $target);

        expect($target->hasEvents())->toBeFalse();
        expect($target->releaseEvents())->toBeEmpty();
    });

    it('does not copy status when target cannot advance to source status', function () {
        $service = new ProductCloneService();

        // Source is at business-set (3)
        $source = Product::fromPersistence(
            id: 1,
            productTypeId: 1,
            userId: 10,
            model: 'GPT-4',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: 'https://source.example.com',
            usage: 0,
            name: 'Source',
            description: null,
            active: true,
            configurationStatus: ConfigurationStatus::from(ConfigurationStatus::BUSINESS_SET),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        // Target is already at business-set (3), same level, cannot advance
        $target = Product::fromPersistence(
            id: 2,
            productTypeId: 1,
            userId: 10,
            model: 'GPT-4',
            linkedToProductId: null,
            password: 'pass',
            targetUrl: null,
            usage: 0,
            name: 'Target',
            description: null,
            active: true,
            configurationStatus: ConfigurationStatus::from(ConfigurationStatus::BUSINESS_SET),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $service->clone($source, $target);

        // Target is already at business-set, cannot advance to same level
        // so status should remain at business-set
        expect($target->configurationStatus->value)->toBe(ConfigurationStatus::BUSINESS_SET);
    });
});
