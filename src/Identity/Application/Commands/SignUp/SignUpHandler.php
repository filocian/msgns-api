<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\SignUp;

use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Bus\EventBus;
use Src\Shared\Core\Errors\ValidationFailed;
use Src\Identity\Domain\Entities\IdentityUser;
use Src\Identity\Domain\Events\UserRegistered;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Application\Resources\UserResource;

final class SignUpHandler implements CommandHandler
{
    public function __construct(
        private readonly IdentityUserRepository $repo,
        private readonly EventBus $eventBus,
    ) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof SignUpCommand);

        $email = strtolower(trim($command->email));

        if ($this->repo->findByEmail($email) !== null) {
            throw ValidationFailed::because('email_already_registered');
        }

        $user = IdentityUser::create($email, $command->name, $command->hashedPassword);
        $user = $this->repo->save($user);

        $this->eventBus->publish(new UserRegistered($user->id, $user->email));

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
