<?php

declare(strict_types=1);

use Src\Identity\Domain\Entities\IdentityUser;
use Src\Shared\Core\Errors\ValidationFailed;

it('creates a new user with create()', function () {
    $user = IdentityUser::create('user@example.com', 'John', 'hashed');
    expect($user->id)->toBe(0)
        ->and($user->email)->toBe('user@example.com')
        ->and($user->active)->toBeTrue()
        ->and($user->emailVerifiedAt)->toBeNull()
        ->and($user->hasPassword())->toBeTrue();
});

it('creates a google user with fromGoogle()', function () {
    $user = IdentityUser::fromGoogle('user@example.com', 'John', 'google-123');
    expect($user->googleId)->toBe('google-123')
        ->and($user->hashedPassword)->toBeNull()
        ->and($user->isGoogleUser())->toBeTrue()
        ->and($user->emailVerifiedAt)->not->toBeNull();
});

it('verifies email', function () {
    $user = IdentityUser::create('user@example.com', 'John', 'hashed');
    $user->verifyEmail();
    expect($user->emailVerifiedAt)->not->toBeNull();
});

it('throws when verifying already verified email', function () {
    $user = IdentityUser::fromGoogle('user@example.com', 'John', 'google-123');
    $user->verifyEmail();
})->throws(ValidationFailed::class);

it('deactivates an active user', function () {
    $user = IdentityUser::create('user@example.com', 'John', 'hashed');
    $user->deactivate();
    expect($user->active)->toBeFalse();
});

it('throws when deactivating already inactive user', function () {
    $now = new DateTimeImmutable();
    $user = IdentityUser::fromPersistence(1, 'user@example.com', 'John', 'hashed', false, null, null, null, null, false, $now, $now);
    $user->deactivate();
})->throws(ValidationFailed::class);

it('activates an inactive user', function () {
    $now = new DateTimeImmutable();
    $user = IdentityUser::fromPersistence(1, 'user@example.com', 'John', 'hashed', false, null, null, null, null, false, $now, $now);
    $user->activate();
    expect($user->active)->toBeTrue();
});

it('throws when activating already active user', function () {
    $user = IdentityUser::create('user@example.com', 'John', 'hashed');
    $user->activate();
})->throws(ValidationFailed::class);

it('resets password', function () {
    $user = IdentityUser::create('user@example.com', 'John', 'old-hash');
    $user->resetPassword('new-hash');
    expect($user->hashedPassword)->toBe('new-hash')
        ->and($user->passwordResetRequired)->toBeFalse();
});
