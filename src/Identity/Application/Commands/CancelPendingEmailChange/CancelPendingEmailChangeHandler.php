<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\CancelPendingEmailChange;

use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;

final class CancelPendingEmailChangeHandler implements CommandHandler
{
    public function __construct(
        private readonly IdentityUserRepository $repo,
    ) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof CancelPendingEmailChangeCommand);

        $user = $this->repo->findById($command->userId);
        if ($user === null) {
            throw NotFound::because('user_not_found');
        }

        if ($user->pendingEmail === null) {
            throw NotFound::because('no_pending_email_change');
        }

        $user->cancelPendingEmailChange();
        $this->repo->save($user);

        return null;
    }
}
