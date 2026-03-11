<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\RequestPasswordReset;

use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Domain\Ports\PasswordResetTokenPort;

final class RequestPasswordResetHandler implements CommandHandler
{
    public function __construct(
        private readonly IdentityUserRepository $repo,
        private readonly PasswordResetTokenPort $tokenPort,
    ) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof RequestPasswordResetCommand);

        $email = strtolower(trim($command->email));

        $user = $this->repo->findByEmail($email);
        if ($user === null) {
            return null;
        }

        $token = $this->tokenPort->generate($user->email);

        return $token;
    }
}
