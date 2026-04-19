<?php

declare(strict_types=1);

namespace Src\GoogleBusiness\Application\Commands\DisconnectGoogleBusiness;

use Src\Shared\Core\Bus\Command;

final readonly class DisconnectGoogleBusinessCommand implements Command
{
    public function __construct(
        public int $userId,
    ) {}

    public function commandName(): string
    {
        return 'google_business.disconnect';
    }
}
