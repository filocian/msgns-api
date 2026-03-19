<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\AdminSetPassword;

use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;
use Src\Identity\Domain\Ports\IdentityUserRepository;

final class AdminSetPasswordHandler implements CommandHandler
{
    public function __construct(
        private readonly IdentityUserRepository $repo,
    ) {}

    public function handle(Command $command): null
    {
        assert($command instanceof AdminSetPasswordCommand);

        $user = $this->repo->findById($command->userId);
        if ($user === null) {
            throw NotFound::because('user_not_found');
        }

        $user->adminSetPassword($command->hashedPassword);
        $this->repo->save($user);

        return null;
    }
}
