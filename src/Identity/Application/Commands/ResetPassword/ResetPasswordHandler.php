<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\ResetPassword;

use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Bus\EventBus;
use Src\Shared\Core\Errors\NotFound;
use Src\Identity\Domain\Events\PasswordReset;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Domain\Ports\PasswordResetTokenPort;
use Src\Identity\Application\Resources\UserResource;

final class ResetPasswordHandler implements CommandHandler
{
    public function __construct(
        private readonly IdentityUserRepository $repo,
        private readonly PasswordResetTokenPort $tokenPort,
        private readonly EventBus $eventBus,
    ) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof ResetPasswordCommand);

        $email = $this->tokenPort->validate($command->token);

        $user = $this->repo->findByEmail($email);
        if ($user === null) {
            throw NotFound::because('user_not_found');
        }

        $user->resetPassword($command->newHashedPassword);
        $this->repo->save($user);

        $this->eventBus->publish(new PasswordReset($user->id));

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
            defaultLocale: $user->defaultLocale,
            createdAt: $user->createdAt->format('c'),
        );
    }
}
