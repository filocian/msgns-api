<?php

declare(strict_types=1);

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->admin = $this->create_user(['email' => 'admin@example.com']);
    $adminRole = $this->createRole('developer');
    $this->admin->assignRole($adminRole);
    $this->actingAs($this->admin, 'stateful-api');
});

// --- AC-001: Happy path - assign roles to multiple users ---
it('assigns roles to multiple users with replace semantics', function () {
    $user1 = $this->create_user(['email' => 'user1@example.com']);
    $user2 = $this->create_user(['email' => 'user2@example.com']);

    // Ensure roles exist
    $this->createRole('designer');
    $this->createRole('marketing');

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/roles', [
        'user_ids' => [$user1->id, $user2->id],
        'roles' => ['designer', 'marketing'],
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

    // Verify database state - roles were replaced
    $user1->refresh();
    $user2->refresh();
    expect($user1->hasRole(['designer', 'marketing']))->toBeTrue()
        ->and($user2->hasRole(['designer', 'marketing']))->toBeTrue()
        ->and($user1->roles)->toHaveCount(2)
        ->and($user2->roles)->toHaveCount(2);
});

// --- AC-002: Replace semantics - removes omitted roles ---
it('replaces existing roles with new role set', function () {
    $user = $this->create_user(['email' => 'user@example.com']);

    // Give user initial roles
    $developerRole = $this->createRole('developer');
    $backofficeRole = $this->createRole('backoffice');
    $designerRole = $this->createRole('designer');

    $user->assignRole($developerRole);
    $user->assignRole($backofficeRole);

    // Verify initial state
    expect($user->hasRole(['developer', 'backoffice']))->toBeTrue()
        ->and($user->roles)->toHaveCount(2);

    // Replace with only designer role
    $response = $this->postJson('/api/v2/identity/admin/users/bulk/roles', [
        'user_ids' => [$user->id],
        'roles' => ['designer'],
    ]);

    $response->assertStatus(200);

    // Verify old roles removed, new role added
    $user->refresh();
    expect($user->hasRole('designer'))->toBeTrue()
        ->and($user->hasRole('developer'))->toBeFalse()
        ->and($user->hasRole('backoffice'))->toBeFalse()
        ->and($user->roles)->toHaveCount(1);
});

// --- AC-003: Replace semantics - allows empty role array to clear roles ---
it('allows empty roles array to clear all roles', function () {
    $user = $this->create_user(['email' => 'user@example.com']);

    // Give user initial roles
    $developerRole = $this->createRole('developer');
    $user->assignRole($developerRole);

    expect($user->roles)->toHaveCount(1);

    // Replace with empty role set - handler should support this
    $response = $this->postJson('/api/v2/identity/admin/users/bulk/roles', [
        'user_ids' => [$user->id],
        'roles' => [],
    ]);

    $response->assertStatus(200);

    // Verify all roles removed
    $user->refresh();
    expect($user->roles)->toHaveCount(0);
});

// --- AC-004: Unchanged status when role set matches ---
it('returns unchanged when user already has exact role set', function () {
    $user = $this->create_user(['email' => 'user@example.com']);

    $developerRole = $this->createRole('developer');
    $backofficeRole = $this->createRole('backoffice');
    $user->assignRole($developerRole);
    $user->assignRole($backofficeRole);

    // Request the same roles
    $response = $this->postJson('/api/v2/identity/admin/users/bulk/roles', [
        'user_ids' => [$user->id],
        'roles' => ['developer', 'backoffice'],
    ]);

    $response->assertStatus(200);
    $result = $response->json('data.results')[0];
    expect($result['status'])->toBe('unchanged')
        ->and($result['message'])->toContain('match');
});

// --- AC-005: Mixed results - continue on failure ---
it('continues processing when some users do not exist', function () {
    $existingUser = $this->create_user(['email' => 'existing@example.com']);
    $nonExistentId = 999999;

    $this->createRole('designer');

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/roles', [
        'user_ids' => [$existingUser->id, $nonExistentId],
        'roles' => ['designer'],
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
    expect($existingUser->hasRole('designer'))->toBeTrue();
});

// --- AC-006: 400 validation - invalid role slug ---
it('returns 400 when invalid role slug provided', function () {
    $user = $this->create_user(['email' => 'user@example.com']);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/roles', [
        'user_ids' => [$user->id],
        'roles' => ['invalid-role-slug'],
    ]);

    $response->assertStatus(400);

    // Verify no changes were made
    $user->refresh();
    expect($user->roles)->toHaveCount(0);
});

// --- AC-007: 400 validation - batch size limit ---
it('returns 400 when batch exceeds 100 users', function () {
    $userIds = range(1, 101);
    $this->createRole('designer');

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/roles', [
        'user_ids' => $userIds,
        'roles' => ['designer'],
    ]);

    $response->assertStatus(400);
});

// --- AC-008: 400 validation - empty user_ids ---
it('returns 400 when user_ids is empty', function () {
    $this->createRole('designer');

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/roles', [
        'user_ids' => [],
        'roles' => ['designer'],
    ]);

    $response->assertStatus(400);
});

// --- AC-009: 400 validation - missing roles field ---
it('returns 400 when roles field is missing', function () {
    $user = $this->create_user(['email' => 'user@example.com']);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/roles', [
        'user_ids' => [$user->id],
    ]);

    $response->assertStatus(400);
});

// --- AC-010: 401 unauthenticated ---
it('returns 401 when unauthenticated', function () {
    $this->app['auth']->forgetGuards();

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/roles', [
        'user_ids' => [1, 2],
        'roles' => ['designer'],
    ]);

    $response->assertStatus(401);
});

// --- AC-011: 403 forbidden - non-admin user ---
it('returns 403 for non-admin users', function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $regularUser = $this->create_user(['email' => 'regular@example.com']);
    $this->actingAs($regularUser, 'stateful-api');

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/roles', [
        'user_ids' => [1, 2],
        'roles' => ['designer'],
    ]);

    $response->assertStatus(403);
});

// --- AC-012: Contract compliance - exact envelope structure ---
it('returns contract-compliant envelope with all required fields', function () {
    $user = $this->create_user(['email' => 'user@example.com']);
    $this->createRole('designer');

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/roles', [
        'user_ids' => [$user->id],
        'roles' => ['designer'],
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

// --- AC-013: Validates against RbacCatalog role slugs ---
it('validates roles against canonical RbacCatalog slugs', function () {
    $user = $this->create_user(['email' => 'user@example.com']);

    // These are the valid slugs from RbacCatalog: developer, backoffice, designer, marketing, user
    $this->createRole('developer');
    $this->createRole('backoffice');
    $this->createRole('marketing');

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/roles', [
        'user_ids' => [$user->id],
        'roles' => ['developer', 'backoffice', 'marketing'],
    ]);

    $response->assertStatus(200);

    $user->refresh();
    expect($user->hasRole(['developer', 'backoffice', 'marketing']))->toBeTrue()
        ->and($user->roles)->toHaveCount(3);
});

// --- AC-014: All canonical roles accepted ---
it('accepts all canonical RbacCatalog role slugs', function () {
    $user = $this->create_user(['email' => 'user@example.com']);

    // Create all canonical roles
    $this->createRole('developer');
    $this->createRole('backoffice');
    $this->createRole('designer');
    $this->createRole('marketing');
    $this->createRole('user');

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/roles', [
        'user_ids' => [$user->id],
        'roles' => ['developer', 'backoffice', 'designer', 'marketing', 'user'],
    ]);

    $response->assertStatus(200);

    $user->refresh();
    expect($user->roles)->toHaveCount(5);
});
