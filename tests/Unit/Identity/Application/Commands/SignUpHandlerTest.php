<?php

declare(strict_types=1);

use Src\Identity\Application\Commands\SignUp\SignUpCommand;
use Src\Identity\Application\Commands\SignUp\SignUpHandler;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Domain\Events\UserRegistered;
use Src\Shared\Core\Bus\EventBus;
use Src\Shared\Core\Errors\ValidationFailed;

beforeEach(function () {
    $this->repo = Mockery::mock(IdentityUserRepository::class);
    $this->eventBus = Mockery::mock(EventBus::class);
    $this->handler = new SignUpHandler($this->repo, $this->eventBus);
});

afterEach(fn() => Mockery::close());

it('signs up a new user', function () {
    $this->repo->shouldReceive('findByEmail')->with('user@example.com')->andReturn(null);
    $this->repo->shouldReceive('save')->once()->andReturnUsing(fn($user) => $user);
    $this->eventBus->shouldReceive('publish')->once();

    $command = new SignUpCommand(email: 'user@example.com', name: 'John', hashedPassword: 'hash');
    $result = $this->handler->handle($command);

    expect($result)->not->toBeNull();
});

it('normalizes email to lowercase', function () {
    $this->repo->shouldReceive('findByEmail')->with('user@example.com')->andReturn(null);
    $this->repo->shouldReceive('save')->once()->andReturnUsing(fn($user) => $user);
    $this->eventBus->shouldReceive('publish')->once();

    $command = new SignUpCommand(email: 'USER@EXAMPLE.COM', name: 'John', hashedPassword: 'hash');
    $this->handler->handle($command);
});

it('throws if email already registered', function () {
    $now = new DateTimeImmutable();
    $existingUser = \Src\Identity\Domain\Entities\IdentityUser::fromPersistence(1, 'user@example.com', 'John', 'hash', true, null, null, null, null, false, $now, $now);
    $this->repo->shouldReceive('findByEmail')->andReturn($existingUser);

    $command = new SignUpCommand(email: 'user@example.com', name: 'John', hashedPassword: 'hash');
    $this->handler->handle($command);
})->throws(ValidationFailed::class);
