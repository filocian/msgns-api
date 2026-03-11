<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\RequestVerification;

use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Errors\ValidationFailed;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Domain\Ports\VerificationTokenPort;

final class RequestVerificationHandler implements CommandHandler
{
    public function __construct(
        private readonly IdentityUserRepository $repo,
        private readonly VerificationTokenPort $tokenPort,
    ) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof RequestVerificationCommand);

        $email = strtolower(trim($command->email));

        $user = $this->repo->findByEmail($email);
        if ($user === null) {
            throw NotFound::because('user_not_found');
        }

        if ($user->emailVerifiedAt !== null) {
            throw ValidationFailed::because('email_already_verified');
        }

        $token = $this->tokenPort->generate($user->email);

        return $token;
    }
}
