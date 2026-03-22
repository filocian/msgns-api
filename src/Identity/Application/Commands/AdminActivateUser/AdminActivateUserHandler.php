<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\AdminActivateUser;

use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Bus\EventBus;
use Src\Shared\Core\Errors\NotFound;
use Src\Identity\Domain\Events\UserActivated;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Application\Resources\UserResource;

final class AdminActivateUserHandler implements CommandHandler
{
    public function __construct(
        private readonly IdentityUserRepository $repo,
        private readonly EventBus $eventBus,
    ) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof AdminActivateUserCommand);

        $user = $this->repo->findById($command->userId);
        if ($user === null) {
            throw NotFound::because('user_not_found');
        }

        $user->activate();
        $this->repo->save($user);

        $this->eventBus->publish(new UserActivated($user->id, $command->activatedBy));

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
