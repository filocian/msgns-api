<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Src\Identity\Domain\Events\PasswordResetRequested;
use Src\Identity\Domain\Ports\PasswordResetTokenPort;

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->artisan('rbac:reconcile')->assertExitCode(0);
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->admin = $this->create_user(['email' => 'admin@example.com']);
    $adminRole = \Spatie\Permission\Models\Role::where('name', 'developer')->where('guard_name', 'stateful-api')->first();
    $this->admin->assignRole($adminRole);
    $this->actingAs($this->admin, 'stateful-api');
});

// --- AC-001: Happy path - trigger password reset for multiple users ---
it('triggers password reset for multiple users', function () {
    $user1 = $this->create_user(['email' => 'user1@example.com']);
    $user2 = $this->create_user(['email' => 'user2@example.com']);

    Event::fake([PasswordResetRequested::class]);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/password-reset', [
        'user_ids' => [$user1->id, $user2->id],
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['summary', 'results']])
        ->assertJsonPath('data.summary.requested', 2)
        ->assertJsonPath('data.summary.succeeded', 2)
        ->assertJsonPath('data.summary.failed', 0);

    // Verify per-user results
    $results = $response->json('data.results');
    expect($results[0]['status'])->toBe('updated')
        ->and($results[1]['status'])->toBe('updated');

    // Verify events were dispatched for each user
    Event::assertDispatched(PasswordResetRequested::class, function ($event) use ($user1, $user2) {
        return in_array($event->email, [$user1->email, $user2->email], true);
    });

    // Should have 2 events total
    Event::assertDispatched(PasswordResetRequested::class, 2);
});

// --- AC-002: Mixed results - continue on failure ---
it('continues processing when some users do not exist', function () {
    $existingUser = $this->create_user(['email' => 'existing@example.com']);
    $nonExistentId = 999999;

    Event::fake([PasswordResetRequested::class]);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/password-reset', [
        'user_ids' => [$existingUser->id, $nonExistentId],
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.summary.requested', 2)
        ->assertJsonPath('data.summary.succeeded', 1)
        ->assertJsonPath('data.summary.failed', 1);

    $results = $response->json('data.results');

    // Existing user should be updated
    $existingResult = collect($results)->firstWhere('userId', $existingUser->id);
    expect($existingResult['status'])->toBe('updated');

    // Non-existent user should fail
    $missingResult = collect($results)->firstWhere('userId', $nonExistentId);
    expect($missingResult['status'])->toBe('failed')
        ->and($missingResult['code'])->toBe('user_not_found');

    // Verify event was only dispatched for existing user
    Event::assertDispatched(PasswordResetRequested::class, 1);
    Event::assertDispatched(PasswordResetRequested::class, function ($event) use ($existingUser) {
        return $event->email === $existingUser->email;
    });
});

// --- AC-003: Reuses standard password reset flow ---
it('reuses standard password reset flow with token generation', function () {
    $user = $this->create_user(['email' => 'user@example.com']);

    // Mock the token port to verify it's called
    $tokenPortMock = Mockery::mock(PasswordResetTokenPort::class);
    $tokenPortMock->shouldReceive('generate')
        ->once()
        ->with($user->email)
        ->andReturn('test-token-123');
    $this->app->instance(PasswordResetTokenPort::class, $tokenPortMock);

    Event::fake([PasswordResetRequested::class]);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/password-reset', [
        'user_ids' => [$user->id],
    ]);

    $response->assertStatus(200);

    // Verify event was dispatched with the token
    Event::assertDispatched(PasswordResetRequested::class, function ($event) {
        return $event->token === 'test-token-123';
    });
});

// --- AC-004: Idempotency - allows multiple reset requests ---
it('allows multiple password reset requests for same user', function () {
    $user = $this->create_user(['email' => 'user@example.com']);

    Event::fake([PasswordResetRequested::class]);

    // First request
    $response1 = $this->postJson('/api/v2/identity/admin/users/bulk/password-reset', [
        'user_ids' => [$user->id],
    ]);
    $response1->assertStatus(200);

    // Second request (should also succeed)
    $response2 = $this->postJson('/api/v2/identity/admin/users/bulk/password-reset', [
        'user_ids' => [$user->id],
    ]);
    $response2->assertStatus(200);

    // Both should report updated (new token generated each time)
    expect($response1->json('data.results')[0]['status'])->toBe('updated')
        ->and($response2->json('data.results')[0]['status'])->toBe('updated');

    // Two events should be dispatched
    Event::assertDispatched(PasswordResetRequested::class, 2);
});

// --- AC-005: 401 unauthenticated ---
it('returns 401 when unauthenticated', function () {
    $this->app['auth']->forgetGuards();

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/password-reset', [
        'user_ids' => [1, 2],
    ]);

    $response->assertStatus(401);
});

// --- AC-006: 403 forbidden - non-admin user ---
it('returns 403 for non-admin users', function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $regularUser = $this->create_user(['email' => 'regular@example.com']);
    $this->actingAs($regularUser, 'stateful-api');

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/password-reset', [
        'user_ids' => [1, 2],
    ]);

    $response->assertStatus(403);
});

// --- AC-007: 400 validation - batch size limit ---
it('returns 400 when batch exceeds 100 users', function () {
    $userIds = range(1, 101);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/password-reset', [
        'user_ids' => $userIds,
    ]);

    $response->assertStatus(400);
});

// --- AC-008: 400 validation - empty user_ids ---
it('returns 400 when user_ids is empty', function () {
    $response = $this->postJson('/api/v2/identity/admin/users/bulk/password-reset', [
        'user_ids' => [],
    ]);

    $response->assertStatus(400);
});

// --- AC-009: 400 validation - duplicate user_ids ---
it('returns 400 when user_ids contains duplicates', function () {
    $user = $this->create_user(['email' => 'user@example.com']);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/password-reset', [
        'user_ids' => [$user->id, $user->id],
    ]);

    $response->assertStatus(400);
});

// --- AC-010: 400 validation - non-integer user_ids ---
it('returns 400 when user_ids contains non-integers', function () {
    $response = $this->postJson('/api/v2/identity/admin/users/bulk/password-reset', [
        'user_ids' => ['not-an-integer'],
    ]);

    $response->assertStatus(400);
});

// --- AC-011: Contract compliance - exact envelope structure ---
it('returns contract-compliant envelope with all required fields', function () {
    $user = $this->create_user(['email' => 'user@example.com']);

    Event::fake([PasswordResetRequested::class]);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/password-reset', [
        'user_ids' => [$user->id],
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'summary' => [
                    'requested',
                    'succeeded',
                    'failed',
                ],
                'results' => [
                    '*' => [
                        'userId',
                        'status',
                        'message',
                    ],
                ],
            ],
        ]);

    // Verify status vocabulary matches spec
    $result = $response->json('data.results')[0];
    expect($result['status'])->toBeIn(['updated', 'unchanged', 'failed']);
});

// --- AC-012: All non-existent users ---
it('handles all non-existent users gracefully', function () {
    Event::fake([PasswordResetRequested::class]);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/password-reset', [
        'user_ids' => [999997, 999998, 999999],
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.summary.requested', 3)
        ->assertJsonPath('data.summary.succeeded', 0)
        ->assertJsonPath('data.summary.failed', 3);

    $results = $response->json('data.results');
    foreach ($results as $result) {
        expect($result['status'])->toBe('failed')
            ->and($result['code'])->toBe('user_not_found');
    }

    // No events should be dispatched
    Event::assertNotDispatched(PasswordResetRequested::class);
});

// --- AC-013: Complex mixed scenario ---
it('handles complex mixed scenario with success and failures', function () {
    $user1 = $this->create_user(['email' => 'user1@example.com']);
    $user2 = $this->create_user(['email' => 'user2@example.com']);

    Event::fake([PasswordResetRequested::class]);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/password-reset', [
        'user_ids' => [$user1->id, 999999, $user2->id, 888888],
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.summary.requested', 4)
        ->assertJsonPath('data.summary.succeeded', 2)
        ->assertJsonPath('data.summary.failed', 2);

    // Should dispatch events only for existing users
    Event::assertDispatched(PasswordResetRequested::class, 2);
});

afterEach(fn () => Mockery::close());
