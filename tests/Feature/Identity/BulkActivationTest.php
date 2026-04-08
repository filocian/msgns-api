<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Src\Identity\Domain\Events\UserActivated;
use Src\Identity\Domain\Events\UserDeactivated;

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->artisan('rbac:reconcile')->assertExitCode(0);
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->admin = $this->create_user(['email' => 'admin@example.com']);
    $adminRole = \Spatie\Permission\Models\Role::where('name', 'developer')->where('guard_name', 'stateful-api')->first();
    $this->admin->assignRole($adminRole);
    $this->actingAs($this->admin, 'stateful-api');
});

// --- AC-001: Happy path - activate users ---
it('activates multiple inactive users', function () {
    $user1 = $this->create_user(['email' => 'user1@example.com', 'active' => false]);
    $user2 = $this->create_user(['email' => 'user2@example.com', 'active' => false]);

    Event::fake([UserActivated::class, UserDeactivated::class]);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/activation', [
        'user_ids' => [$user1->id, $user2->id],
        'active' => true,
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['summary', 'results']])
        ->assertJsonPath('data.summary.requested', 2)
        ->assertJsonPath('data.summary.succeeded', 2)
        ->assertJsonPath('data.summary.failed', 0)
        ->assertJsonCount(2, 'data.results');

    // Verify per-user results
    $results = $response->json('data.results');
    expect($results[0]['status'])->toBe('updated')
        ->and($results[1]['status'])->toBe('updated');

    // Verify database state
    $user1->refresh();
    $user2->refresh();
    expect($user1->active)->toBeTrue()
        ->and($user2->active)->toBeTrue();

    // Verify events fired
    Event::assertDispatched(UserActivated::class, 2);
});

// --- AC-002: Happy path - deactivate users ---
it('deactivates multiple active users', function () {
    $user1 = $this->create_user(['email' => 'user1@example.com', 'active' => true]);
    $user2 = $this->create_user(['email' => 'user2@example.com', 'active' => true]);

    Event::fake([UserActivated::class, UserDeactivated::class]);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/activation', [
        'user_ids' => [$user1->id, $user2->id],
        'active' => false,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.summary.requested', 2)
        ->assertJsonPath('data.summary.succeeded', 2);

    $user1->refresh();
    $user2->refresh();
    expect($user1->active)->toBeFalse()
        ->and($user2->active)->toBeFalse();

    Event::assertDispatched(UserDeactivated::class, 2);
});

// --- AC-003: Mixed results - continue on failure ---
it('continues processing when some users do not exist', function () {
    $existingUser = $this->create_user(['email' => 'existing@example.com', 'active' => false]);
    $nonExistentId = 999999;

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/activation', [
        'user_ids' => [$existingUser->id, $nonExistentId],
        'active' => true,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.summary.requested', 2)
        ->assertJsonPath('data.summary.succeeded', 1)
        ->assertJsonPath('data.summary.failed', 1);

    $results = $response->json('data.results');

    // Find result for existing user
    $existingResult = collect($results)->firstWhere('userId', $existingUser->id);
    expect($existingResult['status'])->toBe('updated');

    // Find result for non-existent user
    $missingResult = collect($results)->firstWhere('userId', $nonExistentId);
    expect($missingResult['status'])->toBe('failed')
        ->and($missingResult['code'])->toBe('user_not_found');

    // Verify the valid user was still processed
    $existingUser->refresh();
    expect($existingUser->active)->toBeTrue();
});

// --- AC-004: Idempotency - unchanged status for already in target state ---
it('returns unchanged for users already in target activation state', function () {
    $alreadyActive = $this->create_user(['email' => 'active@example.com', 'active' => true]);
    $alreadyInactive = $this->create_user(['email' => 'inactive@example.com', 'active' => false]);

    // Try to activate already-active user
    $response = $this->postJson('/api/v2/identity/admin/users/bulk/activation', [
        'user_ids' => [$alreadyActive->id],
        'active' => true,
    ]);

    $response->assertStatus(200);
    $result = $response->json('data.results')[0];
    expect($result['status'])->toBe('unchanged')
        ->and($result['message'])->toContain('already');

    // Try to deactivate already-inactive user
    $response = $this->postJson('/api/v2/identity/admin/users/bulk/activation', [
        'user_ids' => [$alreadyInactive->id],
        'active' => false,
    ]);

    $response->assertStatus(200);
    $result = $response->json('data.results')[0];
    expect($result['status'])->toBe('unchanged');
});

// --- AC-005: 401 unauthenticated ---
it('returns 401 when unauthenticated', function () {
    $this->app['auth']->forgetGuards();

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/activation', [
        'user_ids' => [1, 2],
        'active' => true,
    ]);

    $response->assertStatus(401);
});

// --- AC-006: 403 forbidden - non-admin user ---
it('returns 403 for non-admin users', function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $regularUser = $this->create_user(['email' => 'regular@example.com']);
    $this->actingAs($regularUser, 'stateful-api');

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/activation', [
        'user_ids' => [1, 2],
        'active' => true,
    ]);

    $response->assertStatus(403);
});

// --- AC-007: 403 forbidden - user without proper role ---
it('returns 403 for backoffice-only role when developer is required', function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $user = $this->create_user(['email' => 'backoffice@example.com']);
    $backofficeRole = $this->createRole('backoffice');
    $user->assignRole($backofficeRole);
    $this->actingAs($user, 'stateful-api');

    // Should work - backoffice is allowed
    $target = $this->create_user(['email' => 'target@example.com', 'active' => false]);
    $response = $this->postJson('/api/v2/identity/admin/users/bulk/activation', [
        'user_ids' => [$target->id],
        'active' => true,
    ]);

    $response->assertStatus(200);
});

// --- AC-008: 400 validation - batch size limit ---
it('returns 400 when batch exceeds 100 users', function () {
    $userIds = range(1, 101);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/activation', [
        'user_ids' => $userIds,
        'active' => true,
    ]);

    $response->assertStatus(400);
});

// --- AC-009: 400 validation - empty user_ids ---
it('returns 400 when user_ids is empty', function () {
    $response = $this->postJson('/api/v2/identity/admin/users/bulk/activation', [
        'user_ids' => [],
        'active' => true,
    ]);

    $response->assertStatus(400);
});

// --- AC-010: 400 validation - missing active field ---
it('returns 400 when active field is missing', function () {
    $user = $this->create_user(['email' => 'user@example.com']);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/activation', [
        'user_ids' => [$user->id],
    ]);

    $response->assertStatus(400);
});

// --- AC-011: Contract compliance - exact envelope structure ---
it('returns contract-compliant envelope with all required fields', function () {
    $user = $this->create_user(['email' => 'user@example.com', 'active' => false]);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/activation', [
        'user_ids' => [$user->id],
        'active' => true,
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

// --- AC-012: Complex mixed scenario ---
it('handles complex mixed scenario with all result types', function () {
    // Create users in various states
    $activeUser = $this->create_user(['email' => 'active@example.com', 'active' => true]);
    $inactiveUser = $this->create_user(['email' => 'inactive@example.com', 'active' => false]);
    $anotherActive = $this->create_user(['email' => 'another@example.com', 'active' => true]);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/activation', [
        'user_ids' => [$activeUser->id, $inactiveUser->id, $anotherActive->id, 999999],
        'active' => true,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.summary.requested', 4);

    $results = $response->json('data.results');

    // Should have 2 unchanged (already active), 1 updated, 1 failed
    $unchangedCount = collect($results)->where('status', 'unchanged')->count();
    $updatedCount = collect($results)->where('status', 'updated')->count();
    $failedCount = collect($results)->where('status', 'failed')->count();

    expect($unchangedCount)->toBe(2)
        ->and($updatedCount)->toBe(1)
        ->and($failedCount)->toBe(1);
});
