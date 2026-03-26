<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Products\Application\Commands\ReportUsage\ReportUsageCommand;
use Src\Products\Application\Commands\ReportUsage\ReportUsageHandler;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Ports\ProductUsagePort;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Errors\NotFound;

// ─── Helpers ───────────────────────────────────────────────────────────────────

function makeProduct(int $id = 42): Product
{
    return Product::fromPersistence(
        id: $id,
        productTypeId: 1,
        userId: null,
        model: 'GPT-4',
        linkedToProductId: null,
        password: 'pass',
        targetUrl: null,
        usage: 0,
        name: 'Test Product',
        description: null,
        active: true,
        configurationStatus: ConfigurationStatus::notStarted(),
        assignedAt: null,
        size: null,
        createdAt: new DateTimeImmutable('2024-01-01'),
        updatedAt: new DateTimeImmutable('2024-01-01'),
        deletedAt: null,
    );
}

// ─── Tests ─────────────────────────────────────────────────────────────────────

describe('ReportUsageHandler', function () {

    it('delegates to writeUsageEvent with correct arguments when product exists', function () {
        /** @var MockInterface&ProductRepositoryPort $productRepo */
        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&ProductUsagePort $usagePort */
        $usagePort = Mockery::mock(ProductUsagePort::class);

        $productRepo->shouldReceive('findById')
            ->once()
            ->with(42)
            ->andReturn(makeProduct(42));

        $usagePort->shouldReceive('writeUsageEvent')
            ->once()
            ->withArgs(function (int $productId, int $userId, string $productName, DateTimeImmutable $timestamp): bool {
                return $productId === 42
                    && $userId === 7
                    && $productName === 'GPT-4 Pro'
                    && $timestamp->getTimezone()->getName() === 'UTC';
            });

        $handler = new ReportUsageHandler($productRepo, $usagePort);

        $result = $handler->handle(new ReportUsageCommand(
            productId: 42,
            userId: 7,
            productName: 'GPT-4 Pro',
            scannedAt: '2024-06-15T10:30:00+00:00',
        ));

        expect($result)->toBeNull();
    });

    it('throws NotFound when the product does not exist', function () {
        /** @var MockInterface&ProductRepositoryPort $productRepo */
        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&ProductUsagePort $usagePort */
        $usagePort = Mockery::mock(ProductUsagePort::class);

        $productRepo->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        $usagePort->shouldNotReceive('writeUsageEvent');

        $handler = new ReportUsageHandler($productRepo, $usagePort);

        expect(fn () => $handler->handle(new ReportUsageCommand(
            productId: 999,
            userId: 7,
            productName: 'GPT-4 Pro',
            scannedAt: '2024-06-15T10:30:00+00:00',
        )))->toThrow(NotFound::class);
    });

    it('normalizes non-UTC scannedAt to UTC before calling writeUsageEvent', function () {
        /** @var MockInterface&ProductRepositoryPort $productRepo */
        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&ProductUsagePort $usagePort */
        $usagePort = Mockery::mock(ProductUsagePort::class);

        $productRepo->shouldReceive('findById')
            ->once()
            ->andReturn(makeProduct(1));

        // 2024-06-15T12:30:00+02:00 must arrive as UTC (10:30:00)
        $usagePort->shouldReceive('writeUsageEvent')
            ->once()
            ->withArgs(function (int $productId, int $userId, string $productName, DateTimeImmutable $timestamp): bool {
                return $timestamp->getTimezone()->getName() === 'UTC'
                    && $timestamp->format('Y-m-d H:i:s') === '2024-06-15 10:30:00';
            });

        $handler = new ReportUsageHandler($productRepo, $usagePort);

        $handler->handle(new ReportUsageCommand(
            productId: 1,
            userId: 3,
            productName: 'Claude',
            scannedAt: '2024-06-15T12:30:00+02:00',
        ));

        expect(true)->toBeTrue();
    });

    it('does not call writeUsageEvent when product is not found', function () {
        /** @var MockInterface&ProductRepositoryPort $productRepo */
        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&ProductUsagePort $usagePort */
        $usagePort = Mockery::mock(ProductUsagePort::class);

        $productRepo->shouldReceive('findById')
            ->once()
            ->andReturn(null);

        $usagePort->shouldNotReceive('writeUsageEvent');

        $handler = new ReportUsageHandler($productRepo, $usagePort);

        try {
            $handler->handle(new ReportUsageCommand(
                productId: 0,
                userId: 1,
                productName: 'Test',
                scannedAt: '2024-01-01T00:00:00+00:00',
            ));
        } catch (NotFound) {
            // expected
        }
    });
});
