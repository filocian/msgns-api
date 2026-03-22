<?php

declare(strict_types=1);

use Src\Identity\Application\Commands\RequestPasswordReset\RequestPasswordResetCommand;
use Src\Identity\Application\Commands\RequestPasswordReset\RequestPasswordResetHandler;
use Src\Identity\Domain\Events\PasswordResetRequested;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Domain\Ports\PasswordResetTokenPort;
use Src\Shared\Core\Bus\EventBus;

beforeEach(function () {
    $this->repo      = Mockery::mock(IdentityUserRepository::class);
    $this->tokenPort = Mockery::mock(PasswordResetTokenPort::class);
    $this->eventBus  = Mockery::mock(EventBus::class);
    $this->handler   = new RequestPasswordResetHandler($this->repo, $this->tokenPort, $this->eventBus);
});

afterEach(fn() => Mockery::close());

it('publishes PasswordResetRequested event for known email', function () {
    $now  = new DateTimeImmutable();
    $user = \Src\Identity\Domain\Entities\IdentityUser::fromPersistence(
        1, 'user@example.com', 'User', 'hash', true, null, null, null, null, false, $now, $now
    );

    $this->repo->shouldReceive('findByEmail')->with('user@example.com')->andReturn($user);
    $this->tokenPort->shouldReceive('generate')->with('user@example.com')->andReturn('encrypted-token');
    $this->eventBus->shouldReceive('publish')
        ->once()
        ->with(Mockery::on(function (PasswordResetRequested $event) {
            return $event->email === 'user@example.com' && $event->token === 'encrypted-token';
        }));

    $result = $this->handler->handle(new RequestPasswordResetCommand(email: 'user@example.com'));

    expect($result)->toBeNull();
});

it('returns null and does not publish event for unknown email', function () {
    $this->repo->shouldReceive('findByEmail')->with('ghost@example.com')->andReturn(null);
    $this->tokenPort->shouldNotReceive('generate');
    $this->eventBus->shouldNotReceive('publish');

    $result = $this->handler->handle(new RequestPasswordResetCommand(email: 'ghost@example.com'));

    expect($result)->toBeNull();
});
