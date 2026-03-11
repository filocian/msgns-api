<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\VerifyEmail;

use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Bus\EventBus;
use Src\Shared\Core\Errors\NotFound;
use Src\Identity\Domain\Events\EmailVerified;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Domain\Ports\VerificationTokenPort;
use Src\Identity\Application\Resources\UserResource;

final class VerifyEmailHandler implements CommandHandler
{
    public function __construct(
        private readonly IdentityUserRepository $repo,
        private readonly VerificationTokenPort $tokenPort,
        private readonly EventBus $eventBus,
    ) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof VerifyEmailCommand);

        $email = $this->tokenPort->validate($command->token);

        $user = $this->repo->findByEmail($email);
        if ($user === null) {
            throw NotFound::because('user_not_found');
        }

        $user->verifyEmail();
        $this->repo->save($user);

        $this->eventBus->publish(new EmailVerified($user->id, $user->email));

        return new UserResource(
            id: $user->id,
            email: $user->email,
            name: $user->name,
            active: $user->active,
            emailVerified: $user->emailVerifiedAt !== null,
            phone: $user->phone,
            country: $user->country,
            hasGoogleLogin: $user->isGoogleUser(),
            passwordResetRequired: $user->passwordResetRequired,
            createdAt: $user->createdAt->format('c'),
        );
    }
}
