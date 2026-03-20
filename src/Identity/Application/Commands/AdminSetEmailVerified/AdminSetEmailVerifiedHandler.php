<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\AdminSetEmailVerified;

use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Domain\Ports\RolePort;
use Src\Identity\Application\Resources\AdminUserResource;

final class AdminSetEmailVerifiedHandler implements CommandHandler
{
    public function __construct(
        private readonly IdentityUserRepository $repo,
        private readonly RolePort $rolePort,
    ) {}

    public function handle(Command $command): AdminUserResource
    {
        assert($command instanceof AdminSetEmailVerifiedCommand);

        $user = $this->repo->findById($command->userId);
        if ($user === null) {
            throw NotFound::because('user_not_found');
        }

        $user->forceVerifyEmail();
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
            pendingEmail: $saved->pendingEmail,
        );
    }
}
