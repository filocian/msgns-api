<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Src\Identity\Domain\Ports\EmailChangeTokenPort;
use Src\Shared\Core\Ports\MailPort;

beforeEach(function () {
    $this->user = $this->create_user([
        'email'    => 'old@example.com',
        'password' => Hash::make('Pass123!'),
    ]);
    $this->actingAs($this->user, 'stateful-api');

    // Suppress actual email sending
    $mailPort = Mockery::mock(MailPort::class);
    $mailPort->shouldReceive('send')->zeroOrMoreTimes();
    $this->app->instance(MailPort::class, $mailPort);
});

afterEach(fn () => Mockery::close());

// --- AC-001: successful request ---
it('returns 200 and sets pending_email on valid request', function () {
    $response = $this->postJson('/api/v2/identity/me/email', [
        'new_email' => 'new@example.com',
        'password'  => 'Pass123!',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.message', 'email_change_requested');

    $this->user->refresh();
    expect($this->user->pending_email)->toBe('new@example.com')
        ->and($this->user->email)->toBe('old@example.com');
});

// --- AC-002: wrong password ---
it('returns 400 with incorrect password', function () {
    $response = $this->postJson('/api/v2/identity/me/email', [
        'new_email' => 'new@example.com',
        'password'  => 'WrongPassword!',
    ]);

    $response->assertStatus(400);
});

// --- AC-003: same email ---
it('returns 422 when new email is same as current', function () {
    $response = $this->postJson('/api/v2/identity/me/email', [
        'new_email' => 'old@example.com',
        'password'  => 'Pass123!',
    ]);

    $response->assertStatus(422);
});

// --- AC-004: email taken (primary) ---
it('returns 409 when new email is taken by another user primary email', function () {
    $this->create_user(['email' => 'taken@example.com']);

    $response = $this->postJson('/api/v2/identity/me/email', [
        'new_email' => 'taken@example.com',
        'password'  => 'Pass123!',
    ]);

    $response->assertStatus(409);
});

// --- AC-005: email taken (pending) ---
it('returns 409 when new email is pending for another user', function () {
    $other = $this->create_user(['email' => 'other@example.com']);
    $other->forceFill(['pending_email' => 'pending@example.com'])->save();

    $response = $this->postJson('/api/v2/identity/me/email', [
        'new_email' => 'pending@example.com',
        'password'  => 'Pass123!',
    ]);

    $response->assertStatus(409);
});

// --- AC-006: overwrite existing pending ---
it('overwrites existing pending_email on new request', function () {
    $this->user->forceFill(['pending_email' => 'first@example.com'])->save();

    $response = $this->postJson('/api/v2/identity/me/email', [
        'new_email' => 'second@example.com',
        'password'  => 'Pass123!',
    ]);

    $response->assertStatus(200);

    $this->user->refresh();
    expect($this->user->pending_email)->toBe('second@example.com');
});

// --- AC-008: rate limit ---
it('returns 429 after 3 requests within the rate limit window', function () {
    for ($i = 1; $i <= 3; $i++) {
        $this->postJson('/api/v2/identity/me/email', [
            'new_email' => "rate{$i}@example.com",
            'password'  => 'Pass123!',
        ])->assertStatus(200);
    }

    $response = $this->postJson('/api/v2/identity/me/email', [
        'new_email' => 'rate4@example.com',
        'password'  => 'Pass123!',
    ]);

    $response->assertStatus(429);
});

// --- AC-009: successful confirm ---
it('confirms email change with valid token', function () {
    $this->user->forceFill(['pending_email' => 'new@example.com'])->save();

    $tokenService = app(EmailChangeTokenPort::class);
    $token = $tokenService->generate($this->user->id, 'new@example.com');

    // Confirm endpoint is public — no auth needed, but we can still call it
    $response = $this->postJson('/api/v2/identity/email/confirm-change', [
        'token' => $token,
    ]);

    $response->assertStatus(200);

    $this->user->refresh();
    expect($this->user->email)->toBe('new@example.com')
        ->and($this->user->pending_email)->toBeNull()
        ->and($this->user->email_verified_at)->toBeNull();
});

// --- AC-010: expired token ---
it('returns 422 for expired token', function () {
    $this->user->forceFill(['pending_email' => 'new@example.com'])->save();

    $tokenService = app(EmailChangeTokenPort::class);
    $token = $tokenService->generate($this->user->id, 'new@example.com');

    // Time-travel 61 minutes
    \Carbon\Carbon::setTestNow(\Carbon\Carbon::now()->addMinutes(61));

    $response = $this->postJson('/api/v2/identity/email/confirm-change', [
        'token' => $token,
    ]);

    \Carbon\Carbon::setTestNow();

    $response->assertStatus(422);
});

// --- AC-011: stale token (pending changed) ---
it('returns 422 when token email no longer matches pending_email', function () {
    $this->user->forceFill(['pending_email' => 'first@example.com'])->save();

    $tokenService = app(EmailChangeTokenPort::class);
    $token = $tokenService->generate($this->user->id, 'first@example.com');

    // User changed their mind and requested a different email
    $this->user->forceFill(['pending_email' => 'second@example.com'])->save();

    $response = $this->postJson('/api/v2/identity/email/confirm-change', [
        'token' => $token,
    ]);

    $response->assertStatus(422);
});

// --- AC-014: cancel pending ---
it('cancels pending email change and returns 204', function () {
    $this->user->forceFill(['pending_email' => 'new@example.com'])->save();

    $response = $this->deleteJson('/api/v2/identity/me/email/pending');

    $response->assertStatus(204);

    $this->user->refresh();
    expect($this->user->pending_email)->toBeNull();
});

// --- AC-015: cancel with no pending ---
it('returns 404 when cancelling with no pending email change', function () {
    $response = $this->deleteJson('/api/v2/identity/me/email/pending');

    $response->assertStatus(404);
});

// --- AC-016: unauthenticated cancel ---
it('returns 401 when unauthenticated user tries to cancel', function () {
    // Reset to unauthenticated state
    $this->app['auth']->forgetGuards();

    $response = $this->deleteJson('/api/v2/identity/me/email/pending');

    $response->assertStatus(401);
});

// --- Route middleware verification ---
it('has rate limiting middleware (throttle:3,60) on POST /me/email', function () {
    $route = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutes())
        ->first(fn (\Illuminate\Routing\Route $r) =>
            $r->uri() === 'api/v2/identity/me/email' && in_array('POST', $r->methods())
        );

    expect($route)->not->toBeNull();
    $middleware = $route->middleware();
    expect($middleware)->toContain('throttle:3,60');
});
