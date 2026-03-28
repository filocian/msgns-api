<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Entities\ProductType;
use Src\Products\Domain\Events\ProductReset;
use Src\Products\Domain\Ports\ProductTypeRepository;
use Src\Products\Domain\Ports\ProductUsagePort;
use Src\Products\Domain\Services\ProductResetService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Errors\ValidationFailed;

function makeResetProduct(int $id = 1, int $productTypeId = 10): Product
{
    return Product::fromPersistence(
        id: $id,
        productTypeId: $productTypeId,
        userId: 77,
        model: 'S-GG-XX-RC',
        linkedToProductId: 99,
        password: 'secret',
        targetUrl: 'https://example.com/path',
        usage: 42,
        name: 'Custom Product Name',
        description: 'Demo description',
        active: true,
        configurationStatus: ConfigurationStatus::from('target-set'),
        assignedAt: new DateTimeImmutable('2024-02-20T10:00:00+00:00'),
        size: 'M',
        createdAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        updatedAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        deletedAt: null,
    );
}

function makeResetProductType(int $id, string $code): ProductType
{
    return ProductType::fromPersistence(
        id: $id,
        code: $code,
        name: 'Type ' . $code,
        primaryModel: 'S-GG-XX-RC',
        secondaryModel: null,
        createdAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        updatedAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
    );
}

describe('ProductResetService', function () {
    it('clears resettable fields, deletes usage once, and records ProductReset event', function () {
        $product = makeResetProduct(id: 11, productTypeId: 501);

        /** @var MockInterface&ProductTypeRepository $typeRepository */
        $typeRepository = Mockery::mock(ProductTypeRepository::class);
        /** @var MockInterface&ProductUsagePort $usagePort */
        $usagePort = Mockery::mock(ProductUsagePort::class);
        /** @var MockInterface&LoggerInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);

        $typeRepository->shouldReceive('findById')->once()->with(501)->andReturn(makeResetProductType(501, 'S-GG-XX-RC'));
        $usagePort->shouldReceive('deleteProductUsage')->once()->with(11);
        $logger->shouldNotReceive('error');

        $service = new ProductResetService($typeRepository, $usagePort, $logger);

        $service->reset($product);

        expect($product->userId)->toBeNull()
            ->and($product->targetUrl)->toBeNull()
            ->and($product->linkedToProductId)->toBeNull()
            ->and($product->assignedAt)->toBeNull()
            ->and($product->usage)->toBe(0)
            ->and($product->configurationStatus->value)->toBe('not-started')
            ->and($product->name->value)->toBe('S-GG-XX-RC (11)');

        $events = $product->releaseEvents();
        expect($events)->toHaveCount(1)
            ->and($events[0])->toBeInstanceOf(ProductReset::class)
            ->and($events[0]->productId)->toBe(11);
    });

    it('throws ValidationFailed for bracelet product types (B- and F-) before mutating', function (string $blockedCode) {
        $product = makeResetProduct(id: 12, productTypeId: 777);
        $originalName = $product->name->value;

        /** @var MockInterface&ProductTypeRepository $typeRepository */
        $typeRepository = Mockery::mock(ProductTypeRepository::class);
        /** @var MockInterface&ProductUsagePort $usagePort */
        $usagePort = Mockery::mock(ProductUsagePort::class);
        /** @var MockInterface&LoggerInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);

        $typeRepository->shouldReceive('findById')->once()->with(777)->andReturn(makeResetProductType(777, $blockedCode));
        $usagePort->shouldNotReceive('deleteProductUsage');
        $logger->shouldNotReceive('error');

        $service = new ProductResetService($typeRepository, $usagePort, $logger);

        expect(fn () => $service->reset($product))
            ->toThrow(function (ValidationFailed $error) use ($blockedCode) {
                expect($error->errorCode())->toBe('product_type_not_resettable')
                    ->and($error->context()['product_type_code'])->toBe($blockedCode);

                return true;
            });

        expect($product->name->value)->toBe($originalName)
            ->and($product->usage)->toBe(42);
    })->with([
        'bracelet' => 'B-GG-XX-RC',
        'fancelet' => 'F-GG-XX-RC',
    ]);

    it('logs and swallows DynamoDB failures without interrupting reset flow', function () {
        $product = makeResetProduct(id: 13, productTypeId: 901);

        /** @var MockInterface&ProductTypeRepository $typeRepository */
        $typeRepository = Mockery::mock(ProductTypeRepository::class);
        /** @var MockInterface&ProductUsagePort $usagePort */
        $usagePort = Mockery::mock(ProductUsagePort::class);
        /** @var MockInterface&LoggerInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);

        $typeRepository->shouldReceive('findById')->once()->with(901)->andReturn(makeResetProductType(901, 'S-GG-XX-RC'));
        $usagePort->shouldReceive('deleteProductUsage')->once()->with(13)->andThrow(new RuntimeException('dynamo timeout'));
        $logger->shouldReceive('error')->once()->with(
            'ProductResetService: DynamoDB deleteProductUsage failed',
            Mockery::on(static fn (array $context): bool => $context['product_id'] === 13
                && $context['error'] === 'dynamo timeout'),
        );

        $service = new ProductResetService($typeRepository, $usagePort, $logger);

        $service->reset($product);

        expect($product->usage)->toBe(0)
            ->and($product->name->value)->toBe('S-GG-XX-RC (13)');

        $events = $product->releaseEvents();
        expect($events)->toHaveCount(1)
            ->and($events[0])->toBeInstanceOf(ProductReset::class)
            ->and($events[0]->productId)->toBe(13);
    });
});
