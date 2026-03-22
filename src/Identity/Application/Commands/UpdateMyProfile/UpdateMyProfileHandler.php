<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\UpdateMyProfile;

use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Application\Resources\UserResource;

final class UpdateMyProfileHandler implements CommandHandler
{
    public function __construct(
        private readonly IdentityUserRepository $repo,
    ) {}

    public function handle(Command $command): UserResource
    {
        assert($command instanceof UpdateMyProfileCommand);

        $user = $this->repo->findById($command->userId);
        if ($user === null) {
            throw NotFound::because('user_not_found');
        }

        $user->updateProfile(
            name: $command->name,
            phone: $command->phone,
            country: $command->country,
            defaultLocale: $command->defaultLocale,
        );

        $saved = $this->repo->save($user);

        return new UserResource(
            id: $saved->id,
            email: $saved->email,
            name: $saved->name,
            active: $saved->active,
            emailVerified: $saved->emailVerifiedAt !== null,
            phone: $saved->phone,
            country: $saved->country,
            hasGoogleLogin: $saved->isGoogleUser(),
            passwordResetRequired: $saved->passwordResetRequired,
            defaultLocale: $saved->defaultLocale,
            createdAt: $saved->createdAt->format('c'),
        );
    }
}
