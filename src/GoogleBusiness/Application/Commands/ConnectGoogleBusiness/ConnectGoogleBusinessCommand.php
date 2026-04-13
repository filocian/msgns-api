<?php

declare(strict_types=1);

namespace Src\GoogleBusiness\Application\Commands\ConnectGoogleBusiness;

use Src\Shared\Core\Bus\Command;

final readonly class ConnectGoogleBusinessCommand implements Command
{
    public function __construct(
        public int $userId,
        public string $googleAccountId,
        public string $accessToken,
        public ?string $refreshToken,
        public int $expiresIn,
        public ?string $businessLocationId = null,
        public ?string $businessName = null,
    ) {}

    public function commandName(): string
    {
        return 'google_business.connect';
    }
}
