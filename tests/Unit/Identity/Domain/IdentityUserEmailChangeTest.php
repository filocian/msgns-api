<?php

declare(strict_types=1);

use Src\Identity\Domain\Entities\IdentityUser;
use Src\Shared\Core\Errors\ValidationFailed;

it('requestEmailChange sets pendingEmail and normalizes', function () {
    $user = IdentityUser::create('old@example.com', 'John', 'hashed');
    $user->requestEmailChange('  NEW@Example.COM  ');
    expect($user->pendingEmail)->toBe('new@example.com')
        ->and($user->email)->toBe('old@example.com');
});

it('requestEmailChange throws when new email equals current', function () {
    $user = IdentityUser::create('same@example.com', 'John', 'hashed');
    $user->requestEmailChange('same@example.com');
})->throws(ValidationFailed::class, 'email_unchanged');

it('requestEmailChange throws when new email equals current (case insensitive)', function () {
    $user = IdentityUser::create('same@example.com', 'John', 'hashed');
    $user->requestEmailChange('SAME@EXAMPLE.COM');
})->throws(ValidationFailed::class, 'email_unchanged');

it('confirmEmailChange moves pendingEmail to email and nulls emailVerifiedAt', function () {
    $now = new DateTimeImmutable();
    $user = IdentityUser::fromPersistence(
        1, 'old@example.com', 'John', 'hashed', true, $now, null,
        null, null, false, $now, $now, null, null, 'new@example.com',
    );
    $user->confirmEmailChange();
    expect($user->email)->toBe('new@example.com')
        ->and($user->pendingEmail)->toBeNull()
        ->and($user->emailVerifiedAt)->toBeNull();
});

it('confirmEmailChange throws when no pending email', function () {
    $user = IdentityUser::create('user@example.com', 'John', 'hashed');
    $user->confirmEmailChange();
})->throws(ValidationFailed::class, 'no_pending_email_change');

it('cancelPendingEmailChange nulls pendingEmail', function () {
    $now = new DateTimeImmutable();
    $user = IdentityUser::fromPersistence(
        1, 'old@example.com', 'John', 'hashed', true, null, null,
        null, null, false, $now, $now, null, null, 'new@example.com',
    );
    $user->cancelPendingEmailChange();
    expect($user->pendingEmail)->toBeNull();
});

it('cancelPendingEmailChange throws when no pending email', function () {
    $user = IdentityUser::create('user@example.com', 'John', 'hashed');
    $user->cancelPendingEmailChange();
})->throws(ValidationFailed::class, 'no_pending_email_change');

it('changeEmail (admin) resets emailVerifiedAt and clears pendingEmail', function () {
    $now = new DateTimeImmutable();
    $user = IdentityUser::fromPersistence(
        1, 'old@example.com', 'John', 'hashed', true, $now, null,
        null, null, false, $now, $now, null, null, 'pending@example.com',
    );
    $user->changeEmail('admin-set@example.com');
    expect($user->email)->toBe('admin-set@example.com')
        ->and($user->emailVerifiedAt)->toBeNull()
        ->and($user->pendingEmail)->toBeNull();
});

it('create() initializes pendingEmail as null', function () {
    $user = IdentityUser::create('user@example.com', 'John', 'hashed');
    expect($user->pendingEmail)->toBeNull();
});

it('fromGoogle() initializes pendingEmail as null', function () {
    $user = IdentityUser::fromGoogle('user@example.com', 'John', 'google-123');
    expect($user->pendingEmail)->toBeNull();
});

it('requestEmailChange overwrites existing pending email', function () {
    $now = new DateTimeImmutable();
    $user = IdentityUser::fromPersistence(
        1, 'old@example.com', 'John', 'hashed', true, null, null,
        null, null, false, $now, $now, null, null, 'first@example.com',
    );
    $user->requestEmailChange('second@example.com');
    expect($user->pendingEmail)->toBe('second@example.com');
});
