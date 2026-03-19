<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\AdminUpdateUser;

use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Errors\ValidationFailed;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Domain\Ports\RolePort;
use Src\Identity\Application\Resources\AdminUserResource;

final class AdminUpdateUserHandler implements CommandHandler
{
    public function __construct(
        private readonly IdentityUserRepository $repo,
        private readonly RolePort $rolePort,
    ) {}

    public function handle(Command $command): AdminUserResource
    {
        assert($command instanceof AdminUpdateUserCommand);

        $user = $this->repo->findById($command->userId);
        if ($user === null) {
            throw NotFound::because('user_not_found');
        }

        if ($command->email !== null && strtolower(trim($command->email)) !== strtolower($user->email)) {
            $existing = $this->repo->findByEmail($command->email);
            if ($existing !== null && $existing->id !== $user->id) {
                throw ValidationFailed::because('email_already_taken');
            }
        }

        $user->adminUpdateProfile(
            name: $command->name,
            email: $command->email,
            phone: $command->phone,
            country: $command->country,
            defaultLocale: $command->defaultLocale,
        );

        $saved = $this->repo->save($user);
        $roles = $this->rolePort->getRolesForUser($saved->id);

        return new AdminUserResource(
            id: $saved->id,
            email: $saved->email,
            name: $saved->name,
            active: $saved->active,
            emailVerified: $saved->emailVerifiedAt !== null,
            phone: $saved->phone,
            country: $saved->country,
            hasGoogleLogin: $saved->isGoogleUser(),
            passwordResetRequired: $saved->passwordResetRequired,
            roles: $roles,
            defaultLocale: $saved->defaultLocale,
            createdAt: $saved->createdAt->format('c'),
            updatedAt: $saved->updatedAt->format('c'),
        );
    }
}
