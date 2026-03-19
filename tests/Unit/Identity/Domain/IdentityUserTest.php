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

it('updates profile with partial params — only non-null fields change', function () {
    $now = new DateTimeImmutable();
    $user = IdentityUser::fromPersistence(
        1, 'user@example.com', 'John', 'hashed', true, null, null,
        '+1 555', 'US', false, $now, $now, 'en',
    );
    $user->updateProfile(name: 'Jane', phone: null, country: null, defaultLocale: null);
    expect($user->name)->toBe('Jane')
        ->and($user->phone)->toBe('+1 555')
        ->and($user->country)->toBe('US')
        ->and($user->defaultLocale)->toBe('en');
});

it('changeEmail normalizes to lowercase and trims', function () {
    $user = IdentityUser::create('user@example.com', 'John', 'hashed');
    $user->changeEmail('  NEW@Example.COM  ');
    expect($user->email)->toBe('new@example.com');
});

it('adminUpdateProfile changes email and other fields', function () {
    $now = new DateTimeImmutable();
    $user = IdentityUser::fromPersistence(
        1, 'old@example.com', 'John', 'hashed', true, null, null,
        null, null, false, $now, $now,
    );
    $user->adminUpdateProfile(
        name: 'Jane',
        email: 'NEW@example.com',
        phone: '+34 600',
        country: 'ES',
        defaultLocale: 'es',
    );
    expect($user->name)->toBe('Jane')
        ->and($user->email)->toBe('new@example.com')
        ->and($user->phone)->toBe('+34 600')
        ->and($user->country)->toBe('ES')
        ->and($user->defaultLocale)->toBe('es');
});

it('changePassword sets new password with valid verifier', function () {
    $user = IdentityUser::create('user@example.com', 'John', 'old-hash');
    $user->changePassword(
        currentPlaintext: 'plain',
        newHashedPassword: 'new-hash',
        verifyCurrentPassword: fn(string $plain, string $hashed): bool => $plain === 'plain' && $hashed === 'old-hash',
    );
    expect($user->hashedPassword)->toBe('new-hash')
        ->and($user->passwordResetRequired)->toBeFalse();
});

it('changePassword throws with failing verifier', function () {
    $user = IdentityUser::create('user@example.com', 'John', 'old-hash');
    $user->changePassword(
        currentPlaintext: 'wrong',
        newHashedPassword: 'new-hash',
        verifyCurrentPassword: fn(string $plain, string $hashed): bool => false,
    );
})->throws(ValidationFailed::class, 'invalid_current_password');

it('changePassword throws when no password is set', function () {
    $user = IdentityUser::fromGoogle('user@example.com', 'John', 'google-123');
    $user->changePassword(
        currentPlaintext: 'anything',
        newHashedPassword: 'new-hash',
        verifyCurrentPassword: fn(string $plain, string $hashed): bool => true,
    );
})->throws(ValidationFailed::class, 'no_password_set');

it('adminSetPassword sets password hash', function () {
    $user = IdentityUser::create('user@example.com', 'John', 'old-hash');
    $user->adminSetPassword('admin-set-hash');
    expect($user->hashedPassword)->toBe('admin-set-hash')
        ->and($user->passwordResetRequired)->toBeFalse();
});

it('forceVerifyEmail sets timestamp when null', function () {
    $user = IdentityUser::create('user@example.com', 'John', 'hashed');
    expect($user->emailVerifiedAt)->toBeNull();
    $user->forceVerifyEmail();
    expect($user->emailVerifiedAt)->not->toBeNull();
});

it('forceVerifyEmail is idempotent when already verified', function () {
    $user = IdentityUser::fromGoogle('user@example.com', 'John', 'google-123');
    $original = $user->emailVerifiedAt;
    expect($original)->not->toBeNull();
    $user->forceVerifyEmail();
    expect($user->emailVerifiedAt)->toBe($original);
});
