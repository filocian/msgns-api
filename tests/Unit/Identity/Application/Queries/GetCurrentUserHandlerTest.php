<?php

declare(strict_types=1);

use Src\Identity\Application\Queries\GetCurrentUser\GetCurrentUserHandler;
use Src\Identity\Application\Queries\GetCurrentUser\GetCurrentUserQuery;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Domain\Ports\RolePort;
use Src\Shared\Core\Errors\NotFound;

beforeEach(function () {
    $this->repo = Mockery::mock(IdentityUserRepository::class);
    $this->roles = Mockery::mock(RolePort::class);
    $this->handler = new GetCurrentUserHandler($this->repo, $this->roles);
});

afterEach(fn() => Mockery::close());

it('returns admin user resource for existing user', function () {
    $now = new DateTimeImmutable();
    $user = \Src\Identity\Domain\Entities\IdentityUser::fromPersistence(1, 'user@x.com', 'User', 'hash', true, null, null, null, null, false, $now, $now);
    $this->repo->shouldReceive('findById')->with(1)->andReturn($user);
    $this->roles->shouldReceive('getRolesForUser')->with(1)->andReturn(['user_role']);

    $result = $this->handler->handle(new GetCurrentUserQuery(userId: 1));
    expect($result)->not->toBeNull();
});

it('throws not found when user does not exist', function () {
    $this->repo->shouldReceive('findById')->andReturn(null);
    $this->handler->handle(new GetCurrentUserQuery(userId: 99));
})->throws(NotFound::class);
