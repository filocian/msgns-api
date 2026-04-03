<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\CompleteConfiguration;

use Src\Shared\Core\Bus\Command;

final readonly class CompleteConfigurationCommand implements Command
{
    public function __construct(
        public int $productId,
    ) {}

    public function commandName(): string
    {
        return 'products.complete_configuration';
    }
}
