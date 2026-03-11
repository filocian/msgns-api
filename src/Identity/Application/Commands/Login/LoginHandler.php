<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\Login;

use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Bus\EventBus;
use Src\Shared\Core\Errors\ValidationFailed;
use Src\Identity\Domain\Events\UserLoggedIn;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Application\Resources\UserResource;

final class LoginHandler implements CommandHandler
{
    public function __construct(
        private readonly IdentityUserRepository $repo,
        private readonly EventBus $eventBus,
    ) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof LoginCommand);

        $email = strtolower(trim($command->email));

        $user = $this->repo->findByEmail($email);
        if ($user === null) {
            throw ValidationFailed::because('invalid_credentials');
        }

        if (!$user->active) {
            throw ValidationFailed::because('account_inactive');
        }

        if ($user->hashedPassword === null) {
            throw ValidationFailed::because('password_login_not_available');
        }

        if (!password_verify($command->password, $user->hashedPassword)) {
            throw ValidationFailed::because('invalid_credentials');
        }

        $this->eventBus->publish(new UserLoggedIn($user->id, 'password'));

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
