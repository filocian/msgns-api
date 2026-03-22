<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Src\Identity\Domain\Events\UserLoggedIn;
use Tests\Support\AuthContractAssertions;



it('logs in with valid credentials', function () {
    $user = $this->create_user([
        'email' => 'user@example.com',
        'password' => 'Pass123456!',
        'user_agent' => null,
    ]);

    $response = $this->postWithHeaders('/api/v2/identity/login', [
        'email'    => 'user@example.com',
        'password' => 'Pass123456!',
        'user_agent' => 'login-test-agent',
    ]);

    $response->assertStatus(200)
             ->assertJsonPath('data.user.email', 'user@example.com');

    AuthContractAssertions::assertAuthSuccessContract($response->json(), 'login-success.json');

    $user->refresh();
    expect($user->last_access)->not->toBeNull()
        ->and($user->user_agent)->toBe('login-test-agent')
        ->and($user->hasRole('user'))->toBeTrue();
});

it('dispatches login event on successful login', function () {
    Event::fake([UserLoggedIn::class]);

    $this->create_user([
        'email' => 'event-login@example.com',
        'password' => 'Pass123456!',
    ]);

    $this->postWithHeaders('/api/v2/identity/login', [
        'email' => 'event-login@example.com',
        'password' => 'Pass123456!',
    ])->assertStatus(200);

    Event::assertDispatched(UserLoggedIn::class);
});

it('returns 422 for invalid credentials', function () {
    $this->create_user(['email' => 'user@example.com', 'password' => 'Pass123456!']);

    $response = $this->postWithHeaders('/api/v2/identity/login', [
        'email'    => 'user@example.com',
        'password' => 'WrongPassword!',
    ]);
    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'invalid_credentials');
    AuthContractAssertions::assertAuthErrorContract($response->json(), 'login-error.json');
});

it('returns 422 for non-existent user', function () {
    $response = $this->postWithHeaders('/api/v2/identity/login', [
        'email'    => 'nobody@example.com',
        'password' => 'Pass123!',
    ]);
    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'invalid_credentials');
    AuthContractAssertions::assertAuthErrorContract($response->json(), 'login-error.json');
});

it('returns 400 validation envelope for missing required fields', function () {
    $response = $this->postWithHeaders('/api/v2/identity/login', []);

    $response->assertStatus(400)
        ->assertJsonPath('error.code', 'validation_error');
    AuthContractAssertions::assertAuthErrorContract($response->json(), 'login-validation-error.json');
});

it('keeps existing user_agent on login when already present', function () {
    $user = $this->create_user([
        'email' => 'agent@example.com',
        'password' => 'Pass123456!',
        'user_agent' => 'kept-agent',
    ]);

    $this->postWithHeaders('/api/v2/identity/login', [
        'email' => 'agent@example.com',
        'password' => 'Pass123456!',
        'user_agent' => 'new-agent',
    ])->assertStatus(200);

    $user->refresh();
    expect($user->user_agent)->toBe('kept-agent');
});
