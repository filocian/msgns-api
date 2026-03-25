<?php

declare(strict_types=1);

use Mockery;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Entities\ProductBusiness;
use Src\Products\Domain\Events\ProductBusinessUpdated;
use Src\Products\Domain\Ports\ProductBusinessPort;
use Src\Products\Domain\Services\ProductBusinessService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;

describe('ProductBusinessService', function () {

    beforeEach(function () {
        $this->businessPort = Mockery::mock(ProductBusinessPort::class);
        $this->service = new ProductBusinessService($this->businessPort);
    });

    it('creates business info and records ProductBusinessUpdated event', function () {
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

        $this->businessPort->shouldReceive('findByProductId')
            ->with(1)
            ->andReturn(null);

        $this->businessPort->shouldReceive('save')
            ->once()
            ->andReturnUsing(function ($business) {
                return $business;
            });

        $businessData = [
            'userId' => 10,
            'notABusiness' => false,
            'name' => 'My Business',
            'types' => ['restaurant'],
            'placeTypes' => null,
            'size' => 'small',
        ];

        $this->service->updateBusiness($product, $businessData);

        expect($product->hasEvents())->toBeTrue();

        $events = $product->releaseEvents();
        expect($events)->toHaveCount(1);
        expect($events[0])->toBeInstanceOf(ProductBusinessUpdated::class);
        expect($events[0]->productId)->toBe(1);
    });

    it('triggers config status transition to business-set when possible', function () {
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

        $this->businessPort->shouldReceive('findByProductId')
            ->with(2)
            ->andReturn(null);

        $this->businessPort->shouldReceive('save')
            ->once()
            ->andReturnUsing(function ($business) {
                return $business;
            });

        $businessData = [
            'userId' => 10,
            'notABusiness' => false,
            'name' => 'My Business',
            'types' => ['restaurant'],
            'placeTypes' => null,
            'size' => 'small',
        ];

        $this->service->updateBusiness($product, $businessData);

        // Status should transition from notStarted to businessSet
        expect($product->configurationStatus->value)->toBe(ConfigurationStatus::BUSINESS_SET);
    });

    it('does not transition status when already past business-set', function () {
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

        $this->businessPort->shouldReceive('findByProductId')
            ->with(3)
            ->andReturn(null);

        $this->businessPort->shouldReceive('save')
            ->once()
            ->andReturnUsing(function ($business) {
                return $business;
            });

        $businessData = [
            'userId' => 10,
            'notABusiness' => false,
            'name' => 'My Business',
            'types' => ['restaurant'],
            'placeTypes' => null,
            'size' => 'small',
        ];

        $this->service->updateBusiness($product, $businessData);

        // Status should remain at completed (cannot go backwards to business-set)
        expect($product->configurationStatus->value)->toBe(ConfigurationStatus::COMPLETED);
    });
});
