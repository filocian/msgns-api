<?php

declare(strict_types=1);

namespace Src\Identity\Application\Queries\GetCurrentUser;

use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;
use Src\Shared\Core\Errors\NotFound;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Domain\Ports\RolePort;
use Src\Identity\Application\Resources\AdminUserResource;

final class GetCurrentUserHandler implements QueryHandler
{
    public function __construct(
        private readonly IdentityUserRepository $repo,
        private readonly RolePort $roles,
    ) {}

    public function handle(Query $query): AdminUserResource
    {
        assert($query instanceof GetCurrentUserQuery);

        $user = $this->repo->findById($query->userId);

        if ($user === null) {
            throw NotFound::because('user_not_found');
        }

        $userRoles = $this->roles->getRolesForUser($user->id);

        return new AdminUserResource(
            id: $user->id,
            email: $user->email,
            name: $user->name,
            active: $user->active,
            emailVerified: $user->emailVerifiedAt !== null,
            phone: $user->phone,
            country: $user->country,
            hasGoogleLogin: $user->isGoogleUser(),
            passwordResetRequired: $user->passwordResetRequired,
            roles: $userRoles,
            defaultLocale: $user->defaultLocale,
            createdAt: $user->createdAt->format('c'),
            updatedAt: $user->updatedAt->format('c'),
        );
    }
}
