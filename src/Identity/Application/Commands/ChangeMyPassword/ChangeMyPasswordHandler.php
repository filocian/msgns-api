<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\ChangeMyPassword;

use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Domain\Ports\PasswordHasherPort;

final class ChangeMyPasswordHandler implements CommandHandler
{
    public function __construct(
        private readonly IdentityUserRepository $repo,
        private readonly PasswordHasherPort $passwordHasher,
    ) {}

    public function handle(Command $command): null
    {
        assert($command instanceof ChangeMyPasswordCommand);

        $user = $this->repo->findById($command->userId);
        if ($user === null) {
            throw NotFound::because('user_not_found');
        }

        $user->changePassword(
            currentPlaintext: $command->currentPassword,
            newHashedPassword: $command->newHashedPassword,
            verifyCurrentPassword: fn(string $plain, string $hashed): bool => $this->passwordHasher->check($plain, $hashed),
        );

        $this->repo->save($user);

        return null;
    }
}
