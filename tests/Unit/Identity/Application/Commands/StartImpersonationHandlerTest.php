<?php

declare(strict_types=1);

use Src\Identity\Application\Commands\StartImpersonation\StartImpersonationCommand;
use Src\Identity\Application\Commands\StartImpersonation\StartImpersonationHandler;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Domain\Ports\ImpersonationPort;
use Src\Identity\Domain\Ports\RolePort;
use Src\Shared\Core\Bus\EventBus;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Errors\Unauthorized;

beforeEach(function () {
    $this->repo = Mockery::mock(IdentityUserRepository::class);
    $this->impersonation = Mockery::mock(ImpersonationPort::class);
    $this->roles = Mockery::mock(RolePort::class);
    $this->eventBus = Mockery::mock(EventBus::class);
    $this->handler = new StartImpersonationHandler($this->repo, $this->impersonation, $this->roles, $this->eventBus);
});

afterEach(fn() => Mockery::close());

it('throws not found when target user does not exist', function () {
    $this->repo->shouldReceive('findById')->andReturn(null);
    $this->handler->handle(new StartImpersonationCommand(adminUserId: 1, targetUserId: 99));
})->throws(NotFound::class);

it('throws unauthorized when target is admin', function () {
    $now = new DateTimeImmutable();
    $target = \Src\Identity\Domain\Entities\IdentityUser::fromPersistence(2, 'admin@x.com', 'Admin', 'hash', true, null, null, null, null, false, $now, $now);
    $this->repo->shouldReceive('findById')->andReturn($target);
    $this->roles->shouldReceive('getRolesForUser')->with(2)->andReturn(['developer']);
    $this->handler->handle(new StartImpersonationCommand(adminUserId: 1, targetUserId: 2));
})->throws(Unauthorized::class);

it('starts impersonation for regular user', function () {
    $now = new DateTimeImmutable();
    $target = \Src\Identity\Domain\Entities\IdentityUser::fromPersistence(2, 'user@x.com', 'User', 'hash', true, null, null, null, null, false, $now, $now);
    $this->repo->shouldReceive('findById')->andReturn($target);
    $this->roles->shouldReceive('getRolesForUser')->with(2)->andReturn(['user_role']);
    $this->impersonation->shouldReceive('startImpersonation')->with(1, 2)->once();
    $this->eventBus->shouldReceive('publish')->once();

    $result = $this->handler->handle(new StartImpersonationCommand(adminUserId: 1, targetUserId: 2));
    expect($result)->not->toBeNull();
});
