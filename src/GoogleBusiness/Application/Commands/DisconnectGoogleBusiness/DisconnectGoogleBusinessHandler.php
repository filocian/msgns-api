<?php

declare(strict_types=1);

namespace Src\GoogleBusiness\Application\Commands\DisconnectGoogleBusiness;

use Src\GoogleBusiness\Domain\Ports\GoogleBusinessConnectionRepositoryPort;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;

final class DisconnectGoogleBusinessHandler implements CommandHandler
{
    public function __construct(
        private readonly GoogleBusinessConnectionRepositoryPort $repository,
    ) {}

    public function handle(Command $command): mixed
    {
        /** @var DisconnectGoogleBusinessCommand $command */
        $this->repository->deleteByUserId($command->userId);

        return null;
    }
}
