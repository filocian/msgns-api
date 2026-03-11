<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\GoogleLogin;

use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Bus\EventBus;
use Src\Shared\Core\Errors\ValidationFailed;
use Src\Identity\Domain\Entities\IdentityUser;
use Src\Identity\Domain\Events\UserLoggedIn;
use Src\Identity\Domain\Events\UserRegistered;
use Src\Identity\Domain\Ports\GoogleAuthPort;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Application\Resources\UserResource;

final class GoogleLoginHandler implements CommandHandler
{
    public function __construct(
        private readonly GoogleAuthPort $google,
        private readonly IdentityUserRepository $repo,
        private readonly EventBus $eventBus,
    ) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof GoogleLoginCommand);

        $profile = $this->google->getProfile($command->idToken);

        $user = $this->repo->findByGoogleId($profile->googleId);

        if ($user === null) {
            $user = $this->repo->findByEmail($profile->email);
        }

        if ($user === null) {
            $user = IdentityUser::fromGoogle($profile->email, $profile->name, $profile->googleId);
            $user = $this->repo->save($user);
            $this->eventBus->publish(new UserRegistered($user->id, $user->email));
        } else {
            if ($user->googleId === null) {
                $user->googleId = $profile->googleId;
                $user = $this->repo->save($user);
            }
        }

        if (!$user->active) {
            throw ValidationFailed::because('account_inactive');
        }

        $this->eventBus->publish(new UserLoggedIn($user->id, 'google'));

        return new UserResource(
            id: $user->id,
            email: $user->email,
            name: $user->name,
            active: $user->active,
            emailVerified: $user->emailVerifiedAt !== null,
            phone: $user->phone,
            country: $user->country,
            hasGoogleLogin: $user->isGoogleUser(),
            passwordResetRequired: $user->passwordResetRequired,
            createdAt: $user->createdAt->format('c'),
        );
    }
}
