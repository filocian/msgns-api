<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\ReportUsage;

use Src\Shared\Core\Bus\Command;

final readonly class ReportUsageCommand implements Command
{
    public function __construct(
        public int $productId,
        public int $userId,
        public string $productName,
        public string $scannedAt,
    ) {}

    public function commandName(): string
    {
        return 'products.report_usage';
    }
}
