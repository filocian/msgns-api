<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Events\ProductBusinessUpdated;
use Src\Products\Domain\Ports\ProductBusinessPort;
use Src\Products\Domain\Services\ProductBusinessService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;

describe('ProductBusinessService', function () {

    it('creates business info and records ProductBusinessUpdated event', function () {
        /** @var MockInterface&ProductBusinessPort $businessPort */
        $businessPort = Mockery::mock(ProductBusinessPort::class);
        $service = new ProductBusinessService($businessPort);

        $product = Product::fromPersistence(
            id: 1,
            productTypeId: 1,
            userId: 10,
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

        $businessPort->shouldReceive('findByProductId')->with(1)->andReturn(null); // @phpstan-ignore-line
        $businessPort->shouldReceive('save')->once()->andReturnUsing(fn ($business) => $business); // @phpstan-ignore-line

        $businessData = [
            'userId' => 10,
            'notABusiness' => false,
            'name' => 'My Business',
            'types' => ['restaurant'],
            'placeTypes' => null,
            'size' => 'small',
        ];

        $service->updateBusiness($product, $businessData);

        expect($product->hasEvents())->toBeTrue();

        $events = $product->releaseEvents();
        expect($events)->toHaveCount(1);
        expect($events[0])->toBeInstanceOf(ProductBusinessUpdated::class);
        expect($events[0]->productId)->toBe(1); // @phpstan-ignore-line
    });

    it('triggers config status transition to business-set when possible', function () {
        /** @var MockInterface&ProductBusinessPort $businessPort */
        $businessPort = Mockery::mock(ProductBusinessPort::class);
        $service = new ProductBusinessService($businessPort);

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
            active: true,
            configurationStatus: ConfigurationStatus::notStarted(),
            assignedAt: null,
            size: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );

        $businessPort->shouldReceive('findByProductId')->with(2)->andReturn(null); // @phpstan-ignore-line
        $businessPort->shouldReceive('save')->once()->andReturnUsing(fn ($business) => $business); // @phpstan-ignore-line

        $businessData = [
            'userId' => 10,
            'notABusiness' => false,
            'name' => 'My Business',
            'types' => ['restaurant'],
            'placeTypes' => null,
            'size' => 'small',
        ];

        $service->updateBusiness($product, $businessData);

        // Status should transition from notStarted to businessSet
        expect($product->configurationStatus->value)->toBe(ConfigurationStatus::BUSINESS_SET);
    });

    it('does not transition status when already past business-set', function () {
        /** @var MockInterface&ProductBusinessPort $businessPort */
        $businessPort = Mockery::mock(ProductBusinessPort::class);
        $service = new ProductBusinessService($businessPort);

        // Product is already completed (4), cannot go backwards to business-set (3)
        $product = Product::fromPersistence(
            id: 3,
            productTypeId: 1,
            userId: 10,
            model: 'GPT-4',
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

        $businessPort->shouldReceive('findByProductId')->with(3)->andReturn(null); // @phpstan-ignore-line
        $businessPort->shouldReceive('save')->once()->andReturnUsing(fn ($business) => $business); // @phpstan-ignore-line

        $businessData = [
            'userId' => 10,
            'notABusiness' => false,
            'name' => 'My Business',
            'types' => ['restaurant'],
            'placeTypes' => null,
            'size' => 'small',
        ];

        $service->updateBusiness($product, $businessData);

        // Status should remain at completed (cannot go backwards to business-set)
        expect($product->configurationStatus->value)->toBe(ConfigurationStatus::COMPLETED);
    });
});
