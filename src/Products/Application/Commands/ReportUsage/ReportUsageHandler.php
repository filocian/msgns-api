<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\ReportUsage;

use DateTimeImmutable;
use DateTimeZone;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Ports\ProductUsagePort;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;

final class ReportUsageHandler implements CommandHandler
{
    public function __construct(
        private readonly ProductRepositoryPort $productRepository,
        private readonly ProductUsagePort $productUsagePort,
    ) {}

    public function handle(Command $command): null
    {
        assert($command instanceof ReportUsageCommand);

        $product = $this->productRepository->findById($command->productId);

        if ($product === null) {
            throw NotFound::entity('product', (string) $command->productId);
        }

        $timestamp = (new DateTimeImmutable($command->scannedAt))
            ->setTimezone(new DateTimeZone('UTC'));

        $this->productUsagePort->writeUsageEvent(
            productId: $command->productId,
            userId: $command->userId,
            productName: $command->productName,
            timestamp: $timestamp,
        );

        return null;
    }
}
