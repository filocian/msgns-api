<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\AdminUpdateUser;

use Src\Shared\Core\Bus\Command;

final class AdminUpdateUserCommand implements Command
{
    public function __construct(
        public readonly int $userId,
        public readonly ?string $name,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $country,
        public readonly ?string $defaultLocale,
    ) {}

    public function commandName(): string
    {
        return 'identity.admin_update_user';
    }
}
