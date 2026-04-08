<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands;

use Src\Shared\Core\Bus\Command;

interface ConfigureProductCommand extends Command
{
    public function productId(): int;
}
