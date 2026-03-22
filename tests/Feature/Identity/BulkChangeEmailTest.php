<?php

declare(strict_types=1);

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->admin = $this->create_user(['email' => 'admin@example.com']);
    $adminRole = $this->createRole('developer');
    $this->admin->assignRole($adminRole);
    $this->actingAs($this->admin, 'stateful-api');
});

// --- AC-001: Happy path - bulk email change ---
it('changes emails for multiple users atomically', function () {
    $user1 = $this->create_user(['email' => 'user1@example.com']);
    $user2 = $this->create_user(['email' => 'user2@example.com']);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/email', [
        'updates' => [
            ['user_id' => $user1->id, 'email' => 'new1@example.com'],
            ['user_id' => $user2->id, 'email' => 'new2@example.com'],
        ],
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['summary', 'results']])
        ->assertJsonPath('data.summary.requested', 2)
        ->assertJsonPath('data.summary.succeeded', 2)
        ->assertJsonPath('data.summary.failed', 0);

    // Verify database state
    $user1->refresh();
    $user2->refresh();
    expect($user1->email)->toBe('new1@example.com')
        ->and($user2->email)->toBe('new2@example.com')
        ->and($user1->pending_email)->toBeNull()
        ->and($user2->pending_email)->toBeNull();
});

// --- AC-002: Unchanged when email is the same ---
it('returns unchanged when email equals current email', function () {
    $user = $this->create_user(['email' => 'user@example.com']);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/email', [
        'updates' => [
            ['user_id' => $user->id, 'email' => 'user@example.com'], // Same email
        ],
    ]);

    $response->assertStatus(200);
    $result = $response->json('data.results')[0];
    expect($result['status'])->toBe('unchanged')
        ->and($result['message'])->toContain('same');
});

// --- AC-003: Atomic rollback - duplicate emails in request ---
it('returns 422 and rolls back when duplicate emails in request', function () {
    $user1 = $this->create_user(['email' => 'user1@example.com']);
    $user2 = $this->create_user(['email' => 'user2@example.com']);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/email', [
        'updates' => [
            ['user_id' => $user1->id, 'email' => 'duplicate@example.com'],
            ['user_id' => $user2->id, 'email' => 'duplicate@example.com'], // Duplicate!
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'duplicate_emails_in_request');

    // Verify no changes were persisted (atomic rollback)
    $user1->refresh();
    $user2->refresh();
    expect($user1->email)->toBe('user1@example.com')
        ->and($user2->email)->toBe('user2@example.com');
});

// --- AC-004: FormRequest validation - duplicate user_ids in request ---
it('returns 400 when duplicate user_ids in request', function () {
    $user = $this->create_user(['email' => 'user@example.com']);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/email', [
        'updates' => [
            ['user_id' => $user->id, 'email' => 'first@example.com'],
            ['user_id' => $user->id, 'email' => 'second@example.com'], // Same user ID, different email
        ],
    ]);

    // FormRequest validation rejects duplicate user_ids with 400
    $response->assertStatus(400)
        ->assertJsonPath('error.code', 'validation_error');

    // Verify no changes were persisted
    $user->refresh();
    expect($user->email)->toBe('user@example.com');
});

// --- AC-005: Atomic rollback - email already exists in DB ---
it('returns 422 and rolls back when target email already exists', function () {
    $user1 = $this->create_user(['email' => 'existing@example.com']);
    $user2 = $this->create_user(['email' => 'user2@example.com']);

    // Try to change user2's email to user1's email
    $response = $this->postJson('/api/v2/identity/admin/users/bulk/email', [
        'updates' => [
            ['user_id' => $user2->id, 'email' => 'existing@example.com'],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'email_already_exists');

    // Verify no changes were persisted (atomic rollback)
    $user2->refresh();
    expect($user2->email)->toBe('user2@example.com');
});

// --- AC-006: Atomic rollback - email in pending_email column ---
it('returns 422 when target email is in another user pending_email', function () {
    $user1 = $this->create_user(['email' => 'user1@example.com']);
    $user2 = $this->create_user(['email' => 'user2@example.com']);

    // Set user1's pending_email
    $user1->forceFill(['pending_email' => 'pending@example.com'])->save();

    // Try to change user2's email to that pending email
    $response = $this->postJson('/api/v2/identity/admin/users/bulk/email', [
        'updates' => [
            ['user_id' => $user2->id, 'email' => 'pending@example.com'],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'email_already_exists');

    $user2->refresh();
    expect($user2->email)->toBe('user2@example.com');
});

// --- AC-007: Email normalization ---
it('normalizes emails with trim and lowercase', function () {
    $user = $this->create_user(['email' => 'user@example.com']);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/email', [
        'updates' => [
            ['user_id' => $user->id, 'email' => '  NEWEMAIL@EXAMPLE.COM  '],
        ],
    ]);

    $response->assertStatus(200);

    $user->refresh();
    expect($user->email)->toBe('newemail@example.com');
});

// --- AC-008: 401 unauthenticated ---
it('returns 401 when unauthenticated', function () {
    $this->app['auth']->forgetGuards();

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/email', [
        'updates' => [
            ['user_id' => 1, 'email' => 'new@example.com'],
        ],
    ]);

    $response->assertStatus(401);
});

// --- AC-009: 403 forbidden - non-admin user ---
it('returns 403 for non-admin users', function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $regularUser = $this->create_user(['email' => 'regular@example.com']);
    $this->actingAs($regularUser, 'stateful-api');

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/email', [
        'updates' => [
            ['user_id' => 1, 'email' => 'new@example.com'],
        ],
    ]);

    $response->assertStatus(403);
});

// --- AC-010: 400 validation - batch size limit ---
it('returns 400 when batch exceeds 100 users', function () {
    $updates = [];
    for ($i = 1; $i <= 101; $i++) {
        $updates[] = ['user_id' => $i, 'email' => "user{$i}@example.com"];
    }

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/email', [
        'updates' => $updates,
    ]);

    $response->assertStatus(400);
});

// --- AC-011: 400 validation - empty updates ---
it('returns 400 when updates is empty', function () {
    $response = $this->postJson('/api/v2/identity/admin/users/bulk/email', [
        'updates' => [],
    ]);

    $response->assertStatus(400);
});

// --- AC-012: 400 validation - malformed email ---
it('returns 400 when email format is invalid', function () {
    $user = $this->create_user(['email' => 'user@example.com']);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/email', [
        'updates' => [
            ['user_id' => $user->id, 'email' => 'not-an-email'],
        ],
    ]);

    $response->assertStatus(400);
});

// --- AC-013: 400 validation - missing email field ---
it('returns 400 when email field is missing', function () {
    $user = $this->create_user(['email' => 'user@example.com']);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/email', [
        'updates' => [
            ['user_id' => $user->id], // No email
        ],
    ]);

    $response->assertStatus(400);
});

// --- AC-014: 400 validation - missing user_id field ---
it('returns 400 when user_id field is missing', function () {
    $response = $this->postJson('/api/v2/identity/admin/users/bulk/email', [
        'updates' => [
            ['email' => 'new@example.com'], // No user_id
        ],
    ]);

    $response->assertStatus(400);
});

// --- AC-015: Contract compliance - exact envelope structure ---
it('returns contract-compliant envelope with all required fields', function () {
    $user = $this->create_user(['email' => 'user@example.com']);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/email', [
        'updates' => [
            ['user_id' => $user->id, 'email' => 'new@example.com'],
        ],
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

// --- AC-016: User not found in batch fails entire request ---
it('returns 422 when any user in batch does not exist', function () {
    $existingUser = $this->create_user(['email' => 'existing@example.com']);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/email', [
        'updates' => [
            ['user_id' => $existingUser->id, 'email' => 'new1@example.com'],
            ['user_id' => 999999, 'email' => 'new2@example.com'],
        ],
    ]);

    // Atomic - entire request fails
    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'user_not_found');

    // Verify no partial changes
    $existingUser->refresh();
    expect($existingUser->email)->toBe('existing@example.com');
});

// --- AC-017: Clears email_verified_at on email change ---
it('clears email verification when email is changed', function () {
    $user = $this->create_user([
        'email' => 'user@example.com',
        'email_verified_at' => now(),
    ]);

    $response = $this->postJson('/api/v2/identity/admin/users/bulk/email', [
        'updates' => [
            ['user_id' => $user->id, 'email' => 'new@example.com'],
        ],
    ]);

    $response->assertStatus(200);

    $user->refresh();
    expect($user->email)->toBe('new@example.com')
        ->and($user->email_verified_at)->toBeNull();
});
