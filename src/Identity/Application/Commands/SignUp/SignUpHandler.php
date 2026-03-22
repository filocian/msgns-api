<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\SignUp;

use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Bus\EventBus;
use Src\Shared\Core\Errors\ValidationFailed;
use Src\Identity\Application\Contracts\LocaleMapper;
use Src\Identity\Domain\Entities\IdentityUser;
use Src\Identity\Domain\Events\UserRegistered;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Application\Resources\UserResource;

final class SignUpHandler implements CommandHandler
{
    public function __construct(
        private readonly IdentityUserRepository $repo,
        private readonly EventBus $eventBus,
        private readonly LocaleMapper $localeMapper,
    ) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof SignUpCommand);

        $email = strtolower(trim($command->email));

        if ($this->repo->findByEmail($email) !== null) {
            throw ValidationFailed::because('email_already_registered');
        }

        $user = IdentityUser::create(
            email: $email,
            name: $command->name,
            hashedPassword: $command->hashedPassword,
            phone: $command->phone,
            country: $command->country,
            defaultLocale: $this->localeMapper->mapLanguageToLocale($command->language),
            userAgent: $command->userAgent,
        );
        $user = $this->repo->save($user);
        $this->repo->applySignUpSideEffects($user->id, $command->userAgent);

        $this->eventBus->publish(new UserRegistered($user->id, $user->email));

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
            defaultLocale: $user->defaultLocale,
            createdAt: $user->createdAt->format('c'),
        );
    }
}
