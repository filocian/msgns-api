<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\AdminUpdateUser;

use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Application\Resources\UserResource;

final class AdminUpdateUserHandler implements CommandHandler
{
    public function __construct(
        private readonly IdentityUserRepository $repo,
    ) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof AdminUpdateUserCommand);

        $user = $this->repo->findById($command->userId);
        if ($user === null) {
            throw NotFound::because('user_not_found');
        }

        $user->updateProfile($command->name, $command->email);
        $this->repo->save($user);

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
