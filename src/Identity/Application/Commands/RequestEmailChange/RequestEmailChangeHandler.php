<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\RequestEmailChange;

use Src\Identity\Domain\Events\EmailChangeRequested;
use Src\Identity\Domain\Ports\EmailChangeTokenPort;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Domain\Ports\PasswordHasherPort;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Bus\EventBus;
use Src\Shared\Core\Errors\Conflict;
use Src\Shared\Core\Errors\InvalidCredentials;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Errors\ValidationFailed;

final class RequestEmailChangeHandler implements CommandHandler
{
    public function __construct(
        private readonly IdentityUserRepository $repo,
        private readonly EmailChangeTokenPort $tokenPort,
        private readonly PasswordHasherPort $passwordHasher,
        private readonly EventBus $eventBus,
    ) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof RequestEmailChangeCommand);

        // 1. Load user
        $user = $this->repo->findById($command->userId);
        if ($user === null) {
            throw NotFound::because('user_not_found');
        }

        // 2. Verify password
        if ($user->hashedPassword === null) {
            throw InvalidCredentials::because('no_password_set');
        }
        if (!$this->passwordHasher->check($command->password, $user->hashedPassword)) {
            throw InvalidCredentials::because('invalid_password');
        }

        // 3. Normalize new email
        $newEmail = strtolower(trim($command->newEmail));

        // 4. Check new email differs from current
        if ($newEmail === strtolower($user->email)) {
            throw ValidationFailed::because('email_unchanged');
        }

        // 5. Uniqueness check: email column
        $existingByEmail = $this->repo->findByEmail($newEmail);
        if ($existingByEmail !== null) {
            throw Conflict::because('email_already_taken');
        }

        // 6. Uniqueness check: pending_email column
        $existingByPending = $this->repo->findByPendingEmail($newEmail);
        if ($existingByPending !== null && $existingByPending->id !== $user->id) {
            throw Conflict::because('email_already_taken');
        }

        // 7. Domain method — sets pendingEmail
        $user->requestEmailChange($newEmail);

        // 8. Persist
        $this->repo->save($user);

        // 9. Generate token AFTER persist
        $token = $this->tokenPort->generate($user->id, $newEmail);

        // 10. Publish event (listener sends email)
        $this->eventBus->publish(new EmailChangeRequested(
            userId: $user->id,
            currentEmail: $user->email,
            newEmail: $newEmail,
            token: $token,
        ));

        return null;
    }
}
