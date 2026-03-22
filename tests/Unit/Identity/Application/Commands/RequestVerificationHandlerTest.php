<?php

declare(strict_types=1);

use Src\Identity\Application\Commands\RequestVerification\RequestVerificationCommand;
use Src\Identity\Application\Commands\RequestVerification\RequestVerificationHandler;
use Src\Identity\Domain\Entities\IdentityUser;
use Src\Identity\Domain\Events\VerificationRequested;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Domain\Ports\VerificationTokenPort;
use Src\Shared\Core\Bus\EventBus;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Errors\ValidationFailed;

beforeEach(function () {
    $this->repo      = Mockery::mock(IdentityUserRepository::class);
    $this->tokenPort = Mockery::mock(VerificationTokenPort::class);
    $this->eventBus  = Mockery::mock(EventBus::class);
    $this->handler   = new RequestVerificationHandler($this->repo, $this->tokenPort, $this->eventBus);
});

afterEach(fn () => Mockery::close());

it('publishes VerificationRequested event for known unverified email', function () {
    $now  = new DateTimeImmutable();
    $user = IdentityUser::fromPersistence(
        1, 'user@example.com', 'User', 'hash', true, null, null, null, null, false, $now, $now
    );

    $this->repo->shouldReceive('findByEmail')->with('user@example.com')->andReturn($user);
    $this->tokenPort->shouldReceive('generate')->with('user@example.com')->andReturn('encrypted-token');
    $this->eventBus->shouldReceive('publish')
        ->once()
        ->with(Mockery::on(function (VerificationRequested $event) {
            return $event->email === 'user@example.com' && $event->token === 'encrypted-token';
        }));

    $result = $this->handler->handle(new RequestVerificationCommand(email: 'user@example.com'));

    expect($result)->toBeNull();
});

it('throws NotFound for unknown email', function () {
    $this->repo->shouldReceive('findByEmail')->with('ghost@example.com')->andReturn(null);
    $this->tokenPort->shouldNotReceive('generate');
    $this->eventBus->shouldNotReceive('publish');

    expect(fn () => $this->handler->handle(new RequestVerificationCommand(email: 'ghost@example.com')))
        ->toThrow(NotFound::class);
});

it('throws ValidationFailed for already-verified email', function () {
    $now  = new DateTimeImmutable();
    $user = IdentityUser::fromPersistence(
        1, 'verified@example.com', 'User', 'hash', true, $now, null, null, null, false, $now, $now
    );

    $this->repo->shouldReceive('findByEmail')->with('verified@example.com')->andReturn($user);
    $this->tokenPort->shouldNotReceive('generate');
    $this->eventBus->shouldNotReceive('publish');

    expect(fn () => $this->handler->handle(new RequestVerificationCommand(email: 'verified@example.com')))
        ->toThrow(ValidationFailed::class);
});
