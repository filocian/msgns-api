<?php

declare(strict_types=1);

use Src\Identity\Application\Commands\VerifyEmail\VerifyEmailCommand;
use Src\Identity\Application\Commands\VerifyEmail\VerifyEmailHandler;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Domain\Ports\VerificationTokenPort;
use Src\Shared\Core\Bus\EventBus;
use Src\Shared\Core\Errors\ValidationFailed;

beforeEach(function () {
    $this->repo = Mockery::mock(IdentityUserRepository::class);
    $this->tokenPort = Mockery::mock(VerificationTokenPort::class);
    $this->eventBus = Mockery::mock(EventBus::class);
    $this->handler = new VerifyEmailHandler($this->repo, $this->tokenPort, $this->eventBus);
});

afterEach(fn() => Mockery::close());

it('verifies email with valid token', function () {
    $now = new DateTimeImmutable();
    $user = \Src\Identity\Domain\Entities\IdentityUser::fromPersistence(1, 'user@example.com', 'John', 'hash', true, null, null, null, null, false, $now, $now);
    $this->tokenPort->shouldReceive('validate')->with('valid-token')->andReturn('user@example.com');
    $this->repo->shouldReceive('findByEmail')->with('user@example.com')->andReturn($user);
    $this->repo->shouldReceive('save')->once()->andReturnUsing(fn($u) => $u);
    $this->eventBus->shouldReceive('publish')->once();

    $result = $this->handler->handle(new VerifyEmailCommand(token: 'valid-token'));
    expect($result)->not->toBeNull();
});

it('throws on invalid token', function () {
    $this->tokenPort->shouldReceive('validate')->andThrow(ValidationFailed::because('invalid_or_expired_token'));
    $this->handler->handle(new VerifyEmailCommand(token: 'bad-token'));
})->throws(ValidationFailed::class);
