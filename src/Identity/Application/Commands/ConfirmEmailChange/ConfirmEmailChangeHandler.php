<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\ConfirmEmailChange;

use Src\Identity\Application\Resources\UserResource;
use Src\Identity\Domain\Events\EmailChangeConfirmed;
use Src\Identity\Domain\Ports\EmailChangeTokenPort;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Bus\EventBus;
use Src\Shared\Core\Errors\Conflict;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Errors\ValidationFailed;

final class ConfirmEmailChangeHandler implements CommandHandler
{
    public function __construct(
        private readonly IdentityUserRepository $repo,
        private readonly EmailChangeTokenPort $tokenPort,
        private readonly EventBus $eventBus,
    ) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof ConfirmEmailChangeCommand);

        // 1. Validate token — returns {userId, newEmail} or throws
        $payload = $this->tokenPort->validate($command->token);

        // 2. Load user by ID from token
        $user = $this->repo->findById($payload['userId']);
        if ($user === null) {
            throw NotFound::because('user_not_found');
        }

        // 3. Guard: pending_email must match token's newEmail
        if ($user->pendingEmail === null || strtolower($user->pendingEmail) !== strtolower($payload['newEmail'])) {
            throw ValidationFailed::because('invalid_or_expired_token');
        }

        // 4. Uniqueness final check (race condition safety)
        $existingByEmail = $this->repo->findByEmail($payload['newEmail']);
        if ($existingByEmail !== null && $existingByEmail->id !== $user->id) {
            throw Conflict::because('email_already_taken');
        }

        // 5. Domain method — moves pendingEmail to email, nulls emailVerifiedAt
        $oldEmail = $user->email;
        $user->confirmEmailChange();

        // 6. Persist
        $saved = $this->repo->save($user);

        // 7. Publish event
        $this->eventBus->publish(new EmailChangeConfirmed(
            userId: $saved->id,
            oldEmail: $oldEmail,
            newEmail: $saved->email,
        ));

        return new UserResource(
            id: $saved->id,
            email: $saved->email,
            name: $saved->name,
            active: $saved->active,
            emailVerified: $saved->emailVerifiedAt !== null,
            phone: $saved->phone,
            country: $saved->country,
            hasGoogleLogin: $saved->isGoogleUser(),
            passwordResetRequired: $saved->passwordResetRequired,
            defaultLocale: $saved->defaultLocale,
            createdAt: $saved->createdAt->format('c'),
            pendingEmail: $saved->pendingEmail,
        );
    }
}
