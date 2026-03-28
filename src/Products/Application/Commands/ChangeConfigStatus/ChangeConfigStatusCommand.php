<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\ChangeConfigStatus;

use Src\Shared\Core\Bus\Command;

final readonly class ChangeConfigStatusCommand implements Command
{
    public function __construct(
        public int $productId,
        public string $status,
    ) {}

    public function commandName(): string
    {
        return 'products.change_config_status';
    }
}
