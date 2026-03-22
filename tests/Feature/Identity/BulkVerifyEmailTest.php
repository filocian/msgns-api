<?php

declare(strict_types=1);

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->admin = $this->create_user(['email' => 'admin@example.com']);
    $adminRole = $this->createRole('developer');
    $this->admin->assignRole($adminRole);
    $this->actingAs($this->admin, 'stateful-api');
});

// --- AC-001: Happy path - verify multiple emails ---
it('verifies multiple unverified users', function () {
    $user1 = $this->create_user([
        'email' => 'user1@example.com',
        'email_verified_at' => null,
    ]);
    $user2 = $this->create_user([
        'email' => 'user2@example.com',
        'email_verified_at' => null,
    ]);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/verify-email', [
        'user_ids' => [$user1->id, $user2->id],
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
    expect($user1->email_verified_at)->not->toBeNull()
        ->and($user2->email_verified_at)->not->toBeNull();
});

// --- AC-002: Mixed results - some users not found ---
it('continues processing with mixed results when some users not found', function () {
    $existingUser = $this->create_user([
        'email' => 'existing@example.com',
        'email_verified_at' => null,
    ]);
    $nonExistentId = 999999;

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/verify-email', [
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

    // Verify existing user was still processed
    $existingUser->refresh();
    expect($existingUser->email_verified_at)->not->toBeNull();
});

// --- AC-003: Idempotency - already verified users return unchanged ---
it('returns unchanged status for already verified users', function () {
    $user = $this->create_user([
        'email' => 'verified@example.com',
        'email_verified_at' => now(),
    ]);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/verify-email', [
        'user_ids' => [$user->id],
    ]);

    $response->assertStatus(200);

    $result = $response->json('data.results')[0];
    expect($result['status'])->toBe('unchanged')
        ->and($result['message'])->toContain('already');
});

// --- AC-004: Mixed batch with verified, unverified, and missing ---
it('handles batch with verified, unverified, and missing users', function () {
    $verified = $this->create_user([
        'email' => 'verified@example.com',
        'email_verified_at' => now(),
    ]);
    $unverified = $this->create_user([
        'email' => 'unverified@example.com',
        'email_verified_at' => null,
    ]);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/verify-email', [
        'user_ids' => [$verified->id, $unverified->id, 999999],
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.summary.requested', 3);

    $results = $response->json('data.results');

    // Check each result type
    $verifiedResult = collect($results)->firstWhere('userId', $verified->id);
    $unverifiedResult = collect($results)->firstWhere('userId', $unverified->id);
    $missingResult = collect($results)->firstWhere('userId', 999999);

    expect($verifiedResult['status'])->toBe('unchanged')
        ->and($unverifiedResult['status'])->toBe('updated')
        ->and($missingResult['status'])->toBe('failed');

    // Verify counts
    $unchangedCount = collect($results)->where('status', 'unchanged')->count();
    $updatedCount = collect($results)->where('status', 'updated')->count();
    $failedCount = collect($results)->where('status', 'failed')->count();

    expect($unchangedCount)->toBe(1)
        ->and($updatedCount)->toBe(1)
        ->and($failedCount)->toBe(1);
});

// --- AC-005: 401 unauthenticated ---
it('returns 401 when unauthenticated', function () {
    $this->app['auth']->forgetGuards();

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/verify-email', [
        'user_ids' => [1, 2],
    ]);

    $response->assertStatus(401);
});

// --- AC-006: 403 forbidden - non-admin user ---
it('returns 403 for non-admin users', function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $regularUser = $this->create_user(['email' => 'regular@example.com']);
    $this->actingAs($regularUser, 'stateful-api');

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/verify-email', [
        'user_ids' => [1, 2],
    ]);

    $response->assertStatus(403);
});

// --- AC-007: 400 validation - batch size limit ---
it('returns 400 when batch exceeds 100 users', function () {
    $userIds = range(1, 101);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/verify-email', [
        'user_ids' => $userIds,
    ]);

    $response->assertStatus(400);
});

// --- AC-008: 400 validation - empty user_ids ---
it('returns 400 when user_ids is empty', function () {
    $response = $this->postJson('/api/v2/identity/admin/users/bulk/verify-email', [
        'user_ids' => [],
    ]);

    $response->assertStatus(400);
});

// --- AC-009: 400 validation - duplicate user_ids ---
it('returns 400 when user_ids contains duplicates', function () {
    $user = $this->create_user(['email' => 'user@example.com']);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/verify-email', [
        'user_ids' => [$user->id, $user->id],
    ]);

    $response->assertStatus(400);
});

// --- AC-010: 400 validation - non-integer user_ids ---
it('returns 400 when user_ids contains non-integers', function () {
    $response = $this->postJson('/api/v2/identity/admin/users/bulk/verify-email', [
        'user_ids' => ['not-an-integer'],
    ]);

    $response->assertStatus(400);
});

// --- AC-011: Contract compliance - exact envelope structure ---
it('returns contract-compliant envelope with all required fields', function () {
    $user = $this->create_user([
        'email' => 'user@example.com',
        'email_verified_at' => null,
    ]);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/verify-email', [
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
    $response = $this->postJson('/api/v2/identity/admin/users/bulk/verify-email', [
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
});
