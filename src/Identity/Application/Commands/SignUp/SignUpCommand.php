<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\SignUp;

use Src\Shared\Core\Bus\Command;

final class SignUpCommand implements Command
{
    public function __construct(
        public readonly string $email,
        public readonly string $name,
        public readonly string $hashedPassword,
        public readonly ?string $country,
        public readonly ?string $phone,
        public readonly ?string $language,
        public readonly ?string $userAgent,
    ) {}

    public function commandName(): string
    {
        return 'identity.sign_up';
    }
}
