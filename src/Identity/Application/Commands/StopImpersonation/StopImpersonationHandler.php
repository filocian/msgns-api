<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\StopImpersonation;

use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Bus\EventBus;
use Src\Shared\Core\Errors\NotFound;
use Src\Identity\Domain\Events\ImpersonationStopped;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Domain\Ports\ImpersonationPort;
use Src\Identity\Application\Resources\ImpersonationResource;
use Src\Identity\Application\Resources\UserResource;

final class StopImpersonationHandler implements CommandHandler
{
    public function __construct(
        private readonly IdentityUserRepository $repo,
        private readonly ImpersonationPort $impersonation,
        private readonly EventBus $eventBus,
    ) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof StopImpersonationCommand);

        $adminUserId = $this->impersonation->stopImpersonation();

        $admin = $this->repo->findById($adminUserId);
        if ($admin === null) {
            throw NotFound::because('user_not_found');
        }

        $this->eventBus->publish(new ImpersonationStopped($command->adminUserId, $adminUserId));

        return new ImpersonationResource(
            user: new UserResource(
                id: $admin->id,
                email: $admin->email,
                name: $admin->name,
                active: $admin->active,
                emailVerified: $admin->emailVerifiedAt !== null,
                phone: $admin->phone,
                country: $admin->country,
                hasGoogleLogin: $admin->isGoogleUser(),
                passwordResetRequired: $admin->passwordResetRequired,
                defaultLocale: $admin->defaultLocale,
                createdAt: $admin->createdAt->format('c'),
            ),
            action: 'stopped',
        );
    }
}
