<?php

declare(strict_types=1);

use Src\Identity\Application\Commands\AdminActivateUser\AdminActivateUserCommand;
use Src\Identity\Application\Commands\AdminActivateUser\AdminActivateUserHandler;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Shared\Core\Bus\EventBus;
use Src\Shared\Core\Errors\NotFound;

beforeEach(function () {
    $this->repo = Mockery::mock(IdentityUserRepository::class);
    $this->eventBus = Mockery::mock(EventBus::class);
    $this->handler = new AdminActivateUserHandler($this->repo, $this->eventBus);
});

afterEach(fn() => Mockery::close());

it('activates a user', function () {
    $now = new DateTimeImmutable();
    $user = \Src\Identity\Domain\Entities\IdentityUser::fromPersistence(1, 'x@x.com', 'X', 'hash', false, null, null, null, null, false, $now, $now);
    $this->repo->shouldReceive('findById')->with(1)->andReturn($user);
    $this->repo->shouldReceive('save')->once()->andReturnUsing(fn($u) => $u);
    $this->eventBus->shouldReceive('publish')->once();

    $result = $this->handler->handle(new AdminActivateUserCommand(userId: 1, activatedBy: 2));
    expect($result)->not->toBeNull();
});

it('throws not found when user does not exist', function () {
    $this->repo->shouldReceive('findById')->andReturn(null);
    $this->handler->handle(new AdminActivateUserCommand(userId: 99, activatedBy: 1));
})->throws(NotFound::class);
