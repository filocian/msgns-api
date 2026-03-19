<?php

declare(strict_types=1);

use Src\Identity\Application\Commands\ResetPassword\ResetPasswordCommand;
use Src\Identity\Application\Commands\ResetPassword\ResetPasswordHandler;
use Src\Identity\Domain\Entities\IdentityUser;
use Src\Identity\Domain\Events\PasswordReset;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Domain\Ports\PasswordResetTokenPort;
use Src\Shared\Core\Bus\EventBus;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Errors\ValidationFailed;

beforeEach(function () {
    $this->repo      = Mockery::mock(IdentityUserRepository::class);
    $this->tokenPort = Mockery::mock(PasswordResetTokenPort::class);
    $this->eventBus  = Mockery::mock(EventBus::class);
    $this->handler   = new ResetPasswordHandler($this->repo, $this->tokenPort, $this->eventBus);
});

afterEach(fn() => Mockery::close());

it('resets password, clears password_reset_required, and publishes PasswordReset event', function () {
    $now  = new DateTimeImmutable();
    $user = IdentityUser::fromPersistence(
        42, 'user@example.com', 'User', 'old-hash', true, null, null, null, null, true, $now, $now
    );

    $this->tokenPort->shouldReceive('validate')->with('valid-token')->andReturn('user@example.com');
    $this->repo->shouldReceive('findByEmail')->with('user@example.com')->andReturn($user);
    $this->repo->shouldReceive('save')->once()->andReturn($user);
    $this->eventBus->shouldReceive('publish')
        ->once()
        ->with(Mockery::on(fn(PasswordReset $e) => $e->userId === 42));

    $newHash = password_hash('NewPass123!', PASSWORD_BCRYPT);
    $result = $this->handler->handle(new ResetPasswordCommand(
        token: 'valid-token',
        newHashedPassword: $newHash,
    ));

    expect($result)->not->toBeNull();
    expect($user->passwordResetRequired)->toBeFalse();
    expect($user->hashedPassword)->toBe($newHash);
});

it('propagates ValidationFailed when token is invalid', function () {
    $this->tokenPort->shouldReceive('validate')->andThrow(ValidationFailed::because('invalid_or_expired_token'));

    $this->handler->handle(new ResetPasswordCommand(token: 'bad-token', newHashedPassword: 'hash'));
})->throws(ValidationFailed::class);

it('throws NotFound when user not found after token validation', function () {
    $this->tokenPort->shouldReceive('validate')->with('orphan-token')->andReturn('orphan@example.com');
    $this->repo->shouldReceive('findByEmail')->with('orphan@example.com')->andReturn(null);

    $this->handler->handle(new ResetPasswordCommand(token: 'orphan-token', newHashedPassword: 'hash'));
})->throws(NotFound::class);
