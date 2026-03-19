<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\UpdateMyProfile;

use Src\Shared\Core\Bus\Command;

final class UpdateMyProfileCommand implements Command
{
    public function __construct(
        public readonly int $userId,
        public readonly ?string $name,
        public readonly ?string $phone,
        public readonly ?string $country,
        public readonly ?string $defaultLocale,
    ) {}

    public function commandName(): string
    {
        return 'identity.update_my_profile';
    }
}
