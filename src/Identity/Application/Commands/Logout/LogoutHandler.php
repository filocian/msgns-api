<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\Logout;

use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;

final class LogoutHandler implements CommandHandler
{
    public function handle(Command $command): mixed
    {
        assert($command instanceof LogoutCommand);

        return null;
    }
}
