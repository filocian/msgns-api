<?php

declare(strict_types=1);

use Src\Identity\Application\Commands\GoogleLogin\GoogleLoginCommand;
use Src\Identity\Application\Commands\GoogleLogin\GoogleLoginHandler;
use Src\Identity\Domain\DTOs\GoogleProfile;
use Src\Identity\Domain\Entities\IdentityUser;
use Src\Identity\Domain\Ports\GoogleAuthPort;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Shared\Core\Bus\EventBus;
use Src\Shared\Core\Errors\ValidationFailed;

beforeEach(function () {
    $this->google = Mockery::mock(GoogleAuthPort::class);
    $this->repo = Mockery::mock(IdentityUserRepository::class);
    $this->eventBus = Mockery::mock(EventBus::class);
    $this->handler = new GoogleLoginHandler($this->google, $this->repo, $this->eventBus);
});

afterEach(fn () => Mockery::close());

it('creates a new user and applies signup side effects for Google registration', function () {
    $now = new DateTimeImmutable();
    $profile = new GoogleProfile('new@test.com', 'New User', 'google-123');

    $this->google->shouldReceive('getProfile')->with('valid-token')->andReturn($profile);
    $this->repo->shouldReceive('findByGoogleId')->with('google-123')->andReturn(null);
    $this->repo->shouldReceive('findByEmail')->with('new@test.com')->andReturn(null);
    $this->repo->shouldReceive('save')->once()->andReturnUsing(
        fn ($u) => IdentityUser::fromPersistence(1, $u->email, $u->name, null, true, $now, 'google-123', null, null, false, $now, $now),
    );
    $this->repo->shouldReceive('applySignUpSideEffects')->once()->with(1, null);
    $this->eventBus->shouldReceive('publish')->twice();

    $result = $this->handler->handle(new GoogleLoginCommand(idToken: 'valid-token'));

    expect($result)->not->toBeNull();
});

it('links Google account to existing user and applies login side effects', function () {
    $now = new DateTimeImmutable();
    $profile = new GoogleProfile('existing@test.com', 'Existing', 'google-456');
    $existingUser = IdentityUser::fromPersistence(5, 'existing@test.com', 'Existing', 'hash', true, null, null, null, null, false, $now, $now);

    $this->google->shouldReceive('getProfile')->with('valid-token')->andReturn($profile);
    $this->repo->shouldReceive('findByGoogleId')->with('google-456')->andReturn(null);
    $this->repo->shouldReceive('findByEmail')->with('existing@test.com')->andReturn($existingUser);
    $this->repo->shouldReceive('save')->once()->andReturnUsing(fn ($u) => $u);
    $this->repo->shouldReceive('applyLoginSideEffects')->once()->with(5, null);
    $this->eventBus->shouldReceive('publish')->once();

    $result = $this->handler->handle(new GoogleLoginCommand(idToken: 'valid-token'));

    expect($result)->not->toBeNull();
});

it('applies login side effects for returning Google user', function () {
    $now = new DateTimeImmutable();
    $profile = new GoogleProfile('returning@test.com', 'Returning', 'google-789');
    $returningUser = IdentityUser::fromPersistence(10, 'returning@test.com', 'Returning', null, true, $now, 'google-789', null, null, false, $now, $now);

    $this->google->shouldReceive('getProfile')->with('valid-token')->andReturn($profile);
    $this->repo->shouldReceive('findByGoogleId')->with('google-789')->andReturn($returningUser);
    $this->repo->shouldNotReceive('save');
    $this->repo->shouldReceive('applyLoginSideEffects')->once()->with(10, null);
    $this->eventBus->shouldReceive('publish')->once();

    $result = $this->handler->handle(new GoogleLoginCommand(idToken: 'valid-token'));

    expect($result)->not->toBeNull();
});

it('throws if Google user account is inactive', function () {
    $now = new DateTimeImmutable();
    $profile = new GoogleProfile('inactive@test.com', 'Inactive', 'google-inactive');
    $inactiveUser = IdentityUser::fromPersistence(20, 'inactive@test.com', 'Inactive', null, false, $now, 'google-inactive', null, null, false, $now, $now);

    $this->google->shouldReceive('getProfile')->with('valid-token')->andReturn($profile);
    $this->repo->shouldReceive('findByGoogleId')->with('google-inactive')->andReturn($inactiveUser);
    $this->repo->shouldReceive('applyLoginSideEffects')->once()->with(20, null);
    $this->eventBus->shouldNotReceive('publish');

    $this->handler->handle(new GoogleLoginCommand(idToken: 'valid-token'));
})->throws(ValidationFailed::class);

it('passes userAgent to signup side effects for new user', function () {
    $now = new DateTimeImmutable();
    $profile = new GoogleProfile('new@test.com', 'New User', 'google-123');

    $this->google->shouldReceive('getProfile')->with('valid-token')->andReturn($profile);
    $this->repo->shouldReceive('findByGoogleId')->with('google-123')->andReturn(null);
    $this->repo->shouldReceive('findByEmail')->with('new@test.com')->andReturn(null);
    $this->repo->shouldReceive('save')->once()->andReturnUsing(
        fn ($u) => IdentityUser::fromPersistence(1, $u->email, $u->name, null, true, $now, 'google-123', null, null, false, $now, $now),
    );
    $this->repo->shouldReceive('applySignUpSideEffects')->once()->with(1, 'Mozilla/5.0');
    $this->eventBus->shouldReceive('publish')->twice();

    $result = $this->handler->handle(new GoogleLoginCommand(idToken: 'valid-token', userAgent: 'Mozilla/5.0'));

    expect($result)->not->toBeNull();
});

it('passes userAgent to login side effects for returning user', function () {
    $now = new DateTimeImmutable();
    $profile = new GoogleProfile('returning@test.com', 'Returning', 'google-789');
    $returningUser = IdentityUser::fromPersistence(10, 'returning@test.com', 'Returning', null, true, $now, 'google-789', null, null, false, $now, $now);

    $this->google->shouldReceive('getProfile')->with('valid-token')->andReturn($profile);
    $this->repo->shouldReceive('findByGoogleId')->with('google-789')->andReturn($returningUser);
    $this->repo->shouldNotReceive('save');
    $this->repo->shouldReceive('applyLoginSideEffects')->once()->with(10, 'Mozilla/5.0');
    $this->eventBus->shouldReceive('publish')->once();

    $result = $this->handler->handle(new GoogleLoginCommand(idToken: 'valid-token', userAgent: 'Mozilla/5.0'));

    expect($result)->not->toBeNull();
});
