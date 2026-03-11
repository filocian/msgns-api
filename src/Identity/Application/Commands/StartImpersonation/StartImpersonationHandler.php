<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\StartImpersonation;

use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Bus\EventBus;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Errors\Unauthorized;
use Src\Identity\Domain\Events\ImpersonationStarted;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Domain\Ports\ImpersonationPort;
use Src\Identity\Domain\Ports\RolePort;
use Src\Identity\Application\Resources\ImpersonationResource;
use Src\Identity\Application\Resources\UserResource;

final class StartImpersonationHandler implements CommandHandler
{
    public function __construct(
        private readonly IdentityUserRepository $repo,
        private readonly ImpersonationPort $impersonation,
        private readonly RolePort $roles,
        private readonly EventBus $eventBus,
    ) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof StartImpersonationCommand);

        $target = $this->repo->findById($command->targetUserId);
        if ($target === null) {
            throw NotFound::because('user_not_found');
        }

        $targetRoles = $this->roles->getRolesForUser($command->targetUserId);
        if (in_array('developer', $targetRoles, true) || in_array('backoffice', $targetRoles, true)) {
            throw Unauthorized::because('cannot_impersonate_admin');
        }

        $this->impersonation->startImpersonation($command->adminUserId, $command->targetUserId);

        $this->eventBus->publish(new ImpersonationStarted($command->adminUserId, $command->targetUserId));

        return new ImpersonationResource(
            user: new UserResource(
                id: $target->id,
                email: $target->email,
                name: $target->name,
                active: $target->active,
                emailVerified: $target->emailVerifiedAt !== null,
                phone: $target->phone,
                country: $target->country,
                hasGoogleLogin: $target->isGoogleUser(),
                passwordResetRequired: $target->passwordResetRequired,
                createdAt: $target->createdAt->format('c'),
            ),
            action: 'started',
        );
    }
}
