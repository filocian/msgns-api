<?php

declare(strict_types=1);

use Src\Identity\Application\Commands\Login\LoginCommand;
use Src\Identity\Application\Commands\Login\LoginHandler;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Shared\Core\Bus\EventBus;
use Src\Shared\Core\Errors\ValidationFailed;

beforeEach(function () {
    $this->repo = Mockery::mock(IdentityUserRepository::class);
    $this->eventBus = Mockery::mock(EventBus::class);
    $this->handler = new LoginHandler($this->repo, $this->eventBus);
});

afterEach(fn() => Mockery::close());

it('throws if user not found', function () {
    $this->repo->shouldReceive('findByEmail')->andReturn(null);
    (new LoginHandler($this->repo, $this->eventBus))
        ->handle(new LoginCommand(email: 'x@x.com', password: 'pass'));
})->throws(ValidationFailed::class);

it('throws if account inactive', function () {
    $now = new DateTimeImmutable();
    $user = \Src\Identity\Domain\Entities\IdentityUser::fromPersistence(1, 'x@x.com', 'X', 'hash', false, null, null, null, null, false, $now, $now);
    $this->repo->shouldReceive('findByEmail')->andReturn($user);
    (new LoginHandler($this->repo, $this->eventBus))
        ->handle(new LoginCommand(email: 'x@x.com', password: 'pass'));
})->throws(ValidationFailed::class);

it('throws on invalid password', function () {
    $now = new DateTimeImmutable();
    $hash = password_hash('correct', PASSWORD_BCRYPT);
    $user = \Src\Identity\Domain\Entities\IdentityUser::fromPersistence(1, 'x@x.com', 'X', $hash, true, null, null, null, null, false, $now, $now);
    $this->repo->shouldReceive('findByEmail')->andReturn($user);
    (new LoginHandler($this->repo, $this->eventBus))
        ->handle(new LoginCommand(email: 'x@x.com', password: 'wrong'));
})->throws(ValidationFailed::class);

it('logs in with correct credentials', function () {
    $now = new DateTimeImmutable();
    $hash = password_hash('correct', PASSWORD_BCRYPT);
    $user = \Src\Identity\Domain\Entities\IdentityUser::fromPersistence(1, 'x@x.com', 'X', $hash, true, null, null, null, null, false, $now, $now);
    $this->repo->shouldReceive('findByEmail')->andReturn($user);
    $this->eventBus->shouldReceive('publish')->once();

    $result = (new LoginHandler($this->repo, $this->eventBus))
        ->handle(new LoginCommand(email: 'x@x.com', password: 'correct'));
    expect($result)->not->toBeNull();
});
