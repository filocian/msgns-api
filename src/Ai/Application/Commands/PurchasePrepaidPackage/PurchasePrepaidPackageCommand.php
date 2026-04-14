<?php

declare(strict_types=1);

namespace Src\Ai\Application\Commands\PurchasePrepaidPackage;

use Src\Shared\Core\Bus\Command;

final readonly class PurchasePrepaidPackageCommand implements Command
{
    public function __construct(
        public int $packageId,
        public string $paymentMethodId,
        public int $userId,
    ) {}

    public function commandName(): string
    {
        return 'ai.purchase_prepaid_package';
    }
}
