<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\SetTargetUrl;

use Src\Shared\Core\Bus\Command;

final readonly class SetTargetUrlCommand implements Command
{
    public function __construct(
        public int $productId,
        public string $targetUrl,
    ) {}

    public function commandName(): string
    {
        return 'products.set_target_url';
    }
}
